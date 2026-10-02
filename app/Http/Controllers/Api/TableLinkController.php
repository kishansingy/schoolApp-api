<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppRecord;
use App\Models\AppTable;
use App\Models\TableLink;
use App\Models\TableLinkQueue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Table Link System
 *
 * Allows pulling records from a source table into a target table,
 * with optional line-item support and quantity tracking.
 *
 * Flow:
 *   1. Admin creates a TableLink config (source → target, field maps, qty settings)
 *   2. When a source record is saved, a queue entry is auto-created (status=pending)
 *   3. In the target form, user clicks "Pull from Orders" → sees pending list
 *   4. User selects one or more → confirms → data is copied into target
 *   5. Queue entries update to partial/done; source record status is stamped
 */
class TableLinkController extends Controller
{
    // ── Config CRUD (admin) ───────────────────────────────────────────────────

    public function index()
    {
        return TableLink::with(['sourceTable:id,name,label', 'targetTable:id,name,label'])
            ->where('active', true)->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                       => 'required|string',
            'source_table_id'            => 'required|exists:app_tables,id',
            'target_table_id'            => 'required|exists:app_tables,id',
            'header_field_map'           => 'nullable|array',
            'line_field_map'             => 'nullable|array',
            'source_detail_table_id'     => 'nullable|exists:app_tables,id',
            'target_detail_table_id'     => 'nullable|exists:app_tables,id',
            'qty_field'                  => 'nullable|string',
            'track_qty'                  => 'boolean',
            'source_status_field'        => 'nullable|string',
            'source_status_done_value'   => 'nullable|string',
            'source_status_partial_value'=> 'nullable|string',
            'source_filter_field'        => 'nullable|string',
            'source_filter_value'        => 'nullable|string',
            'pull_button_label'          => 'nullable|string',
            'allow_multi_select'         => 'boolean',
        ]);
        return response()->json(TableLink::create($data), 201);
    }

    public function show(TableLink $tableLink)
    {
        return $tableLink->load(['sourceTable', 'targetTable', 'sourceDetailTable', 'targetDetailTable']);
    }

    public function update(Request $request, TableLink $tableLink)
    {
        $tableLink->update($request->all());
        return $tableLink->fresh();
    }

    public function destroy(TableLink $tableLink)
    {
        $tableLink->delete();
        return response()->noContent();
    }

    // ── Queue management ──────────────────────────────────────────────────────

    /**
     * POST /table-links/{tableLink}/enqueue/{sourceRecord}
     * Manually enqueue a source record (also called automatically on record save).
     */
    public function enqueue(TableLink $tableLink, AppRecord $sourceRecord)
    {
        $this->enqueueRecord($tableLink, $sourceRecord);
        return response()->json(['message' => 'Enqueued.']);
    }

    /**
     * GET /table-links/{tableLink}/pending
     * List all pending/partial source records for the pull dialog.
     * Returns enriched rows ready for display.
     */
    public function pending(TableLink $tableLink, Request $request)
    {
        $queueRows = TableLinkQueue::where('table_link_id', $tableLink->id)
            ->whereIn('status', ['pending', 'partial'])
            ->with('sourceRecord')
            ->get();

        $sourceTable = $tableLink->sourceTable;

        $rows = $queueRows->map(function ($q) use ($sourceTable, $tableLink) {
            $data = $q->sourceRecord?->data ?? [];

            // Build a display label from source data
            $label = $this->buildLabel($sourceTable, $data, $q->source_record_id);

            return [
                'queue_id'         => $q->id,
                'source_record_id' => $q->source_record_id,
                'label'            => $label,
                'status'           => $q->status,
                'qty_original'     => $q->qty_original,
                'qty_transferred'  => $q->qty_transferred,
                'qty_pending'      => $q->qty_pending,
                'data'             => $data,
            ];
        });

        return response()->json([
            'link'    => [
                'id'               => $tableLink->id,
                'name'             => $tableLink->name,
                'pull_button_label'=> $tableLink->pull_button_label,
                'allow_multi_select' => $tableLink->allow_multi_select,
                'track_qty'        => $tableLink->track_qty,
                'qty_field'        => $tableLink->qty_field,
            ],
            'pending' => $rows,
            'total'   => $rows->count(),
        ]);
    }

    /**
     * POST /table-links/{tableLink}/pull
     * Pull selected source records into a target record.
     *
     * Body:
     * {
     *   "target_record_id": 123,       // existing target record to attach to (optional)
     *   "create_target": true,         // create a new target record
     *   "target_data": {...},          // extra data for new target record
     *   "items": [
     *     { "queue_id": 1, "qty": 10 },  // qty optional, defaults to full pending qty
     *     { "queue_id": 2 }
     *   ]
     * }
     */
    public function pull(Request $request, TableLink $tableLink)
    {
        $request->validate([
            'items'            => 'required|array|min:1',
            'items.*.queue_id' => 'required|exists:table_link_queue,id',
            'items.*.qty'      => 'nullable|numeric|min:0.0001',
            'target_record_id' => 'nullable|exists:app_records,id',
            'create_target'    => 'boolean',
            'target_data'      => 'nullable|array',
        ]);

        $targetTable = $tableLink->targetTable;
        $queueItems  = collect($request->input('items'));

        // Load & validate queue rows belong to this link and are pending/partial
        $queueIds = $queueItems->pluck('queue_id');
        $queues   = TableLinkQueue::whereIn('id', $queueIds)
            ->where('table_link_id', $tableLink->id)
            ->whereIn('status', ['pending', 'partial'])
            ->get()
            ->keyBy('id');

        if ($queues->count() !== $queueIds->count()) {
            return response()->json(['error' => 'Some items are invalid or already done.'], 422);
        }

        DB::beginTransaction();
        try {
            // ── Create or load target record ──────────────────────────────────
            $targetRecord = null;

            if ($request->boolean('create_target')) {
                // Build header data from first source record
                $firstQueue  = $queues->first();
                $sourceData  = $firstQueue->sourceRecord?->data ?? [];
                $headerData  = $this->applyFieldMap($tableLink->header_field_map ?? [], $sourceData);
                $extraData   = $request->input('target_data', []);
                $merged      = array_merge($headerData, $extraData);

                // Auto-number
                if ($targetTable->auto_number && $targetTable->auto_number_field) {
                    $f = $targetTable->auto_number_field;
                    if (empty($merged[$f])) $merged[$f] = $targetTable->nextAutoNumber();
                }

                $targetRecord = AppRecord::create([
                    'app_table_id' => $targetTable->id,
                    'data'         => $merged,
                ]);
            } elseif ($request->input('target_record_id')) {
                $targetRecord = AppRecord::findOrFail($request->input('target_record_id'));
            } else {
                return response()->json(['error' => 'Provide target_record_id or set create_target=true.'], 422);
            }

            $pulledLines = [];

            foreach ($queueItems as $item) {
                $queue      = $queues[$item['queue_id']];
                $sourceData = $queue->sourceRecord?->data ?? [];

                // Determine qty to transfer
                $qtyToTransfer = null;
                if ($tableLink->track_qty && $tableLink->qty_field) {
                    $requested     = isset($item['qty']) ? (float)$item['qty'] : null;
                    $qtyToTransfer = $requested ?? $queue->qty_pending;
                    $qtyToTransfer = min($qtyToTransfer, $queue->qty_pending); // can't exceed pending
                }

                // ── Copy line items if detail tables configured ───────────────
                if ($tableLink->source_detail_table_id && $tableLink->target_detail_table_id) {
                    $sourceDetailTable = $tableLink->sourceDetailTable;
                    $targetDetailTable = $tableLink->targetDetailTable;

                    $detailRows = AppRecord::where('app_table_id', $sourceDetailTable->id)
                        ->where('parent_record_id', $queue->source_record_id)
                        ->get();

                    foreach ($detailRows as $detailRow) {
                        $lineData = $this->applyFieldMap(
                            $tableLink->line_field_map ?? [],
                            $detailRow->data ?? []
                        );

                        // Qty adjustment for partial pulls
                        if ($tableLink->track_qty && $tableLink->qty_field && $qtyToTransfer !== null) {
                            $lineData[$tableLink->qty_field] = $qtyToTransfer;
                        }

                        // Link to target header
                        $lineData['_source_record_id'] = $queue->source_record_id;

                        $newLine = AppRecord::create([
                            'app_table_id'     => $targetDetailTable->id,
                            'parent_record_id' => $targetRecord->id,
                            'data'             => $lineData,
                        ]);
                        $pulledLines[] = $newLine->id;
                    }
                } else {
                    // No detail table — copy header-level data as a line
                    $lineData = $this->applyFieldMap(
                        $tableLink->line_field_map ?? $tableLink->header_field_map ?? [],
                        $sourceData
                    );
                    if ($tableLink->track_qty && $tableLink->qty_field && $qtyToTransfer !== null) {
                        $lineData[$tableLink->qty_field] = $qtyToTransfer;
                    }
                    $lineData['_source_record_id'] = $queue->source_record_id;

                    $newLine = AppRecord::create([
                        'app_table_id'     => $tableLink->target_detail_table_id ?? $targetTable->id,
                        'parent_record_id' => $targetRecord->id,
                        'data'             => $lineData,
                    ]);
                    $pulledLines[] = $newLine->id;
                }

                // ── Update queue entry ────────────────────────────────────────
                if ($tableLink->track_qty && $tableLink->qty_field) {
                    $newTransferred = $queue->qty_transferred + $qtyToTransfer;
                    $newPending     = max(0, $queue->qty_original - $newTransferred);
                    $newStatus      = $newPending <= 0 ? 'done' : 'partial';

                    $queue->update([
                        'qty_transferred'      => $newTransferred,
                        'qty_pending'          => $newPending,
                        'status'               => $newStatus,
                        'last_target_record_id'=> $targetRecord->id,
                    ]);
                } else {
                    $queue->update([
                        'status'               => 'done',
                        'qty_transferred'      => $queue->qty_original,
                        'qty_pending'          => 0,
                        'last_target_record_id'=> $targetRecord->id,
                    ]);
                }

                // ── Stamp source record status ────────────────────────────────
                $this->stampSourceStatus($tableLink, $queue->sourceRecord, $queue->status);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json([
            'message'          => 'Pulled successfully.',
            'target_record_id' => $targetRecord->id,
            'pulled_lines'     => count($pulledLines),
            'target_data'      => $targetRecord->fresh()->data,
        ], 201);
    }

    /**
     * POST /table-links/auto-enqueue
     * Called internally when a source record is created/updated.
     * Finds all active links for the source table and enqueues.
     */
    public function autoEnqueue(Request $request)
    {
        $recordId = $request->input('record_id');
        $tableId  = $request->input('table_id');

        $record = AppRecord::find($recordId);
        if (!$record) return response()->json(['enqueued' => 0]);

        $links = TableLink::where('source_table_id', $tableId)->where('active', true)->get();
        $count = 0;

        foreach ($links as $link) {
            // Check source filter (e.g. only enqueue if status=confirmed)
            if ($link->source_filter_field) {
                $val = $record->data[$link->source_filter_field] ?? null;
                if ($val !== $link->source_filter_value) continue;
            }
            $this->enqueueRecord($link, $record);
            $count++;
        }

        return response()->json(['enqueued' => $count]);
    }

    /**
     * GET /table-links/for-target/{appTable}
     * Returns all active links where this table is the TARGET.
     * Used by the UI to show "Pull" buttons on the target form.
     */
    public function forTarget(AppTable $appTable)
    {
        $links = TableLink::where('target_table_id', $appTable->id)
            ->where('active', true)
            ->with(['sourceTable:id,name,label'])
            ->get();

        return response()->json($links);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function enqueueRecord(TableLink $link, AppRecord $record): void
    {
        // Check if already queued
        $existing = TableLinkQueue::where('table_link_id', $link->id)
            ->where('source_record_id', $record->id)
            ->whereNull('source_detail_record_id')
            ->first();

        $qty = 0;
        if ($link->track_qty && $link->qty_field) {
            $qty = (float)($record->data[$link->qty_field] ?? 0);
        }

        if ($existing) {
            // Re-enqueue only if it was done and qty changed (e.g. order edited)
            if ($existing->status === 'done') return;
            $existing->update([
                'qty_original' => $qty,
                'qty_pending'  => max(0, $qty - $existing->qty_transferred),
            ]);
            return;
        }

        TableLinkQueue::create([
            'table_link_id'    => $link->id,
            'source_record_id' => $record->id,
            'qty_original'     => $qty,
            'qty_transferred'  => 0,
            'qty_pending'      => $qty,
            'status'           => 'pending',
        ]);
    }

    private function applyFieldMap(array $map, array $sourceData): array
    {
        $result      = [];
        $hasWildcard = collect($map)->contains(fn($m) => ($m['from'] ?? '') === '*');

        if ($hasWildcard || empty($map)) {
            foreach ($sourceData as $k => $v) {
                if (!str_starts_with($k, '_')) $result[$k] = $v;
            }
        }

        foreach ($map as $entry) {
            $from = $entry['from'] ?? null;
            $to   = $entry['to']   ?? null;
            if (!$from || !$to || $from === '*') continue;

            if ($to === '_skip') { unset($result[$from]); continue; }

            if (array_key_exists($from, $sourceData)) {
                $result[$to] = $sourceData[$from];
                if ($to !== $from) unset($result[$from]);
            }
        }

        return $result;
    }

    private function stampSourceStatus(TableLink $link, ?AppRecord $record, string $queueStatus): void
    {
        if (!$record || !$link->source_status_field) return;

        $data  = $record->data ?? [];
        $field = $link->source_status_field;

        $newStatus = match($queueStatus) {
            'done'    => $link->source_status_done_value    ?? 'done',
            'partial' => $link->source_status_partial_value ?? 'partial',
            default   => null,
        };

        if ($newStatus && ($data[$field] ?? null) !== $newStatus) {
            $data[$field] = $newStatus;
            $record->update(['data' => $data]);
        }
    }

    private function buildLabel(AppTable $table, array $data, int $id): string
    {
        // Try common label fields
        foreach (['order_no', 'invoice_no', 'receipt_no', 'name', 'title', 'number'] as $f) {
            if (!empty($data[$f])) return $data[$f];
        }
        $name = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));
        if ($name) return $name;
        return $table->label . ' #' . $id;
    }
}

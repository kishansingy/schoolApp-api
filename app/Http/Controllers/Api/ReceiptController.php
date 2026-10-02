<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppRecord;
use App\Models\AppTable;
use App\Models\ReceiptTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReceiptController extends Controller
{
    // ── Template CRUD ─────────────────────────────────────────────────────────

    public function index()
    {
        return ReceiptTemplate::with('appTable:id,name,label')
            ->orderBy('name')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:200',
            'description'    => 'nullable|string',
            'app_table_id'   => 'nullable|exists:app_tables,id',
            'html_template'  => 'required|string',
            'sql_query'      => 'nullable|string',
            'params'         => 'nullable|array',
            'query_mode'     => 'in:record,sql',
            'paper_size'     => 'in:A4,A5,Letter',
            'orientation'    => 'in:portrait,landscape',
            'related_tables' => 'nullable|array',
        ]);
        return response()->json(ReceiptTemplate::create($data), 201);
    }

    public function show(ReceiptTemplate $receiptTemplate)
    {
        return $receiptTemplate->load('appTable:id,name,label');
    }

    public function update(Request $request, ReceiptTemplate $receiptTemplate)
    {
        $receiptTemplate->update($request->only([
            'name', 'description', 'app_table_id', 'related_tables',
            'html_template', 'sql_query', 'params', 'query_mode',
            'paper_size', 'orientation', 'active',
        ]));
        return $receiptTemplate->fresh();
    }

    public function destroy(ReceiptTemplate $receiptTemplate)
    {
        $receiptTemplate->delete();
        return response()->noContent();
    }

    // ── Render ────────────────────────────────────────────────────────────────

    public function render(Request $request, ReceiptTemplate $receiptTemplate)
    {
        $inputParams = $request->input('params', []);   // user-supplied param values
        $data        = [];
        $rowSets     = [];

        // ── MODE: SQL query ───────────────────────────────────────────────────
        if ($receiptTemplate->query_mode === 'sql' && $receiptTemplate->sql_query) {
            $result = $this->runSqlQuery($receiptTemplate->sql_query, $inputParams);

            if (isset($result['error'])) {
                return response()->json(['error' => $result['error']], 422);
            }

            $rows = $result['rows'];

            // First row → flat data (for header fields like {{first_name}})
            if (!empty($rows)) {
                $data = (array) $rows[0];
            }

            // All rows → available as {{#each _rows}} for repeating sections
            $rowSets['_rows'] = array_map(fn($r) => (array)$r, $rows);

            // Also expose named row sets if template defines them
            // e.g. sql returns column "_set" to group rows
            $namedSets = [];
            foreach ($rows as $row) {
                $r = (array)$row;
                $setKey = $r['_set'] ?? null;
                if ($setKey) {
                    $namedSets[$setKey][] = $r;
                }
            }
            foreach ($namedSets as $k => $v) {
                $rowSets[$k] = $v;
            }
        }

        // ── MODE: Record-based (original) ─────────────────────────────────────
        else {
            $recordId = $request->input('record_id') ?? ($inputParams['record_id'] ?? null);
            $tableId  = $receiptTemplate->app_table_id ?? $request->input('table_id');

            if ($recordId && $tableId) {
                $record = AppRecord::where('app_table_id', $tableId)->find($recordId);
                if ($record) {
                    $data        = $record->data ?? [];
                    $data['_id'] = $record->id;
                    $appTable    = AppTable::find($tableId);
                    if ($appTable) {
                        $data = $this->resolveReferences($appTable, $data);
                    }
                }
            }

            // Load related table rows
            foreach ($receiptTemplate->related_tables ?? [] as $rel) {
                $relTableName = $rel['table']      ?? null;
                $asName       = $rel['as']         ?? $relTableName;
                $linkField    = $rel['link_field'] ?? null;
                $linkValueKey = $rel['link_value'] ?? '_id';
                if (!$relTableName || !$linkField) continue;

                $relTable  = AppTable::where('name', $relTableName)->first();
                if (!$relTable) continue;

                $linkValue = $linkValueKey === '_id'
                    ? ($data['_id'] ?? null)
                    : ($data[$linkValueKey] ?? null);
                if (!$linkValue) continue;

                $rows = AppRecord::where('app_table_id', $relTable->id)
                    ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.\"{$linkField}\"')) = ?", [$linkValue])
                    ->get();

                $resolved = [];
                foreach ($rows as $row) {
                    $rd        = $row->data ?? [];
                    $rd['_id'] = $row->id;
                    $rd        = $this->resolveReferences($relTable, $rd);
                    $resolved[] = $rd;
                }
                $rowSets[$asName] = $resolved;
            }
        }

        // ── System vars + extra ───────────────────────────────────────────────
        $data = array_merge($data, $request->input('data', []));
        $data['_date']     = now()->format('d/m/Y');
        $data['_time']     = now()->format('H:i');
        $data['_datetime'] = now()->format('d/m/Y H:i');
        $data['_school']   = 'School Management System';

        $html = $this->renderTemplate($receiptTemplate->html_template, $data, $rowSets);

        return response()->json([
            'html'        => $html,
            'paper_size'  => $receiptTemplate->paper_size,
            'orientation' => $receiptTemplate->orientation,
            'data'        => $data,
            'row_sets'    => array_map('count', $rowSets),
        ]);
    }

    // ── SQL query runner ──────────────────────────────────────────────────────

    private function runSqlQuery(string $sql, array $params): array
    {
        // Substitute {{table:name}} → actual table id
        $sql = preg_replace_callback('/\{\{table:(\w+)\}\}/', function ($m) {
            return AppTable::where('name', $m[1])->value('id') ?? 0;
        }, $sql);

        // Strip optional AND clauses when param is empty
        $sql = preg_replace_callback(
            '/\s+AND\s*\(\s*:([a-zA-Z_]+)\s+IS\s+NULL\s+OR[^)]+\)/i',
            function ($m) use ($params) {
                $val = $params[$m[1]] ?? null;
                return ($val === null || $val === '') ? '' : $m[0];
            },
            $sql
        );

        // Convert :name → positional ?
        $positional = [];
        $sql = preg_replace_callback('/:([a-zA-Z_]+)/', function ($m) use ($params, &$positional) {
            $val = $params[$m[1]] ?? null;
            $positional[] = ($val === '') ? null : $val;
            return '?';
        }, $sql);

        try {
            $rows = DB::select($sql, $positional);
            return ['rows' => $rows];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    // ── Template engine ───────────────────────────────────────────────────────

    private function renderTemplate(string $template, array $data, array $rowSets = []): string
    {
        // ── Process {{#each rowSetName}} ... {{/each}} blocks ─────────────────
        $template = preg_replace_callback(
            '/\{\{#each\s+(\w+)\}\}(.*?)\{\{\/each\}\}/s',
            function ($m) use ($rowSets, $data) {
                $setName  = $m[1];
                $inner    = $m[2];
                $rows     = $rowSets[$setName] ?? [];
                $output   = '';
                foreach ($rows as $i => $row) {
                    $merged  = array_merge($data, $row, ['_index' => $i + 1]);
                    $output .= $this->replacePlaceholders($inner, $merged);
                }
                return $output;
            },
            $template
        );

        // ── Process {{#if field}} ... {{/if}} blocks ──────────────────────────
        $template = preg_replace_callback(
            '/\{\{#if\s+(\w+)\}\}(.*?)\{\{\/if\}\}/s',
            function ($m) use ($data) {
                $key   = $m[1];
                $inner = $m[2];
                return !empty($data[$key]) ? $this->replacePlaceholders($inner, $data) : '';
            },
            $template
        );

        // ── Replace remaining {{field}} placeholders ──────────────────────────
        return $this->replacePlaceholders($template, $data);
    }

    private function replacePlaceholders(string $text, array $data): string
    {
        return preg_replace_callback('/\{\{([^#\/][^}]*)\}\}/', function ($m) use ($data) {
            $key = trim($m[1]);
            $val = $data[$key] ?? '';
            return htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
        }, $text);
    }

    private function resolveReferences(AppTable $appTable, array $data): array
    {
        foreach ($appTable->allFields() as $field) {
            if ($field->type !== 'reference' || !$field->reference_table_id) continue;
            if (!isset($data[$field->name]) || !$data[$field->name]) continue;

            $refRecord = AppRecord::find($data[$field->name]);
            if (!$refRecord) continue;

            $rd    = $refRecord->data ?? [];
            $label = trim(($rd['first_name'] ?? '') . ' ' . ($rd['last_name'] ?? ''));
            if (!$label) $label = $rd['name'] ?? $rd['title'] ?? "#{$data[$field->name]}";

            // {{field_name_name}} → resolved label
            $data[$field->name . '_name'] = $label;

            // {{field_name.sub_field}} → any sub-field of the referenced record
            foreach ($rd as $k => $v) {
                $data[$field->name . '.' . $k] = $v;
            }
        }
        return $data;
    }
}

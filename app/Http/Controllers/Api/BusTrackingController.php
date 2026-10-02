<?php

namespace App\Http\Controllers\Api;

use App\Contracts\BusTrackingServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\Bus;
use App\Models\BusAssignment;
use App\Models\BusLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BusTrackingController extends Controller
{
    public function __construct(
        private readonly BusTrackingServiceInterface $busService
    ) {}

    // ── Admin: manage buses ───────────────────────────────────────────────────

    public function index(): JsonResponse
    {
        return response()->json(Bus::with('location')->where('is_active', true)->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'           => 'required|string',
            'number_plate'   => 'nullable|string',
            'driver_name'    => 'nullable|string',
            'driver_phone'   => 'nullable|string',
            'driver_user_id' => 'nullable|integer|exists:users,id',
        ]);
        return response()->json(Bus::create($data), 201);
    }

    public function update(Request $request, Bus $bus): JsonResponse
    {
        $data = $request->validate([
            'name'           => 'sometimes|string',
            'number_plate'   => 'nullable|string',
            'driver_name'    => 'nullable|string',
            'driver_phone'   => 'nullable|string',
            'driver_user_id' => 'nullable|integer|exists:users,id',
            'is_active'      => 'sometimes|boolean',
        ]);
        $bus->update($data);
        return response()->json($bus);
    }

    public function destroy(Bus $bus): \Illuminate\Http\Response
    {
        $bus->delete();
        return response()->noContent();
    }

    // ── Admin: assignments ────────────────────────────────────────────────────

    public function assignments(Bus $bus): JsonResponse
    {
        return response()->json($bus->assignments()->get());
    }

    public function assign(Request $request, Bus $bus): JsonResponse
    {
        $data = $request->validate([
            'student_record_id' => 'required|integer',
            'pickup_stop'       => 'nullable|string',
        ]);
        $assignment = $bus->assignments()->updateOrCreate(
            ['student_record_id' => $data['student_record_id']],
            ['pickup_stop' => $data['pickup_stop'] ?? null]
        );
        return response()->json($assignment);
    }

    public function unassign(Bus $bus, int $studentRecordId): \Illuminate\Http\Response
    {
        $bus->assignments()->where('student_record_id', $studentRecordId)->delete();
        return response()->noContent();
    }

    // ── Driver: update location ───────────────────────────────────────────────

    public function updateLocation(Request $request): JsonResponse
    {
        $data = $request->validate([
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'speed'     => 'nullable|numeric',
            'heading'   => 'nullable|numeric',
            'is_active' => 'sometimes|boolean',
        ]);

        $bus = Bus::where('driver_user_id', $request->user()->id)->where('is_active', true)->first();
        if (!$bus) return response()->json(['message' => 'No active bus assigned to you.'], 404);

        $location = BusLocation::updateOrCreate(
            ['bus_id' => $bus->id],
            array_merge($data, ['located_at' => now()])
        );

        return response()->json(['bus' => $bus->only(['id', 'name', 'number_plate']), 'location' => $location]);
    }

    public function stopSharing(Request $request): JsonResponse
    {
        $bus = Bus::where('driver_user_id', $request->user()->id)->first();
        if ($bus) BusLocation::where('bus_id', $bus->id)->update(['is_active' => false]);
        return response()->json(['message' => 'Location sharing stopped.']);
    }

    // ── Parent/Student/Teacher: view bus ─────────────────────────────────────

    public function myBus(Request $request): JsonResponse
    {
        $user  = $request->user();
        $roles = $user->roles->pluck('name')->toArray();

        if (in_array('teacher', $roles) || in_array('admin', $roles)) {
            return response()->json(['buses' => Bus::with('location')->where('is_active', true)->get()]);
        }

        $studentRecordIds = $this->busService->getStudentRecordIds($user);
        if (empty($studentRecordIds)) return response()->json(['message' => 'No student record found.'], 404);

        $assignments = BusAssignment::whereIn('student_record_id', $studentRecordIds)->with('bus.location')->get();
        if ($assignments->isEmpty()) return response()->json(['message' => 'No bus assigned.'], 404);

        return response()->json([
            'buses' => $assignments->map(fn($a) => ['bus' => $a->bus, 'location' => $a->bus->location, 'pickup_stop' => $a->pickup_stop]),
        ]);
    }

    public function busLocation(Bus $bus): JsonResponse
    {
        $location = $bus->location;
        if (!$location || !$location->is_active) return response()->json(['message' => 'Bus is not sharing location.'], 404);
        return response()->json(['bus' => $bus->only(['id', 'name', 'number_plate', 'driver_name', 'driver_phone']), 'location' => $location]);
    }
}

class BusTrackingController extends Controller
{
    // ── Admin: manage buses ───────────────────────────────────────────────────

    public function index()
    {
        return Bus::with('location')->where('is_active', true)->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'           => 'required|string',
            'number_plate'   => 'nullable|string',
            'driver_name'    => 'nullable|string',
            'driver_phone'   => 'nullable|string',
            'driver_user_id' => 'nullable|integer|exists:users,id',
        ]);
        return Bus::create($data);
    }

    public function update(Request $request, Bus $bus)
    {
        $data = $request->validate([
            'name'           => 'sometimes|string',
            'number_plate'   => 'nullable|string',
            'driver_name'    => 'nullable|string',
            'driver_phone'   => 'nullable|string',
            'driver_user_id' => 'nullable|integer|exists:users,id',
            'is_active'      => 'sometimes|boolean',
        ]);
        $bus->update($data);
        return $bus;
    }

    public function destroy(Bus $bus)
    {
        $bus->delete();
        return response()->noContent();
    }

    // ── Admin: assignments ────────────────────────────────────────────────────

    public function assignments(Bus $bus)
    {
        return $bus->assignments()->get();
    }

    public function assign(Request $request, Bus $bus)
    {
        $data = $request->validate([
            'student_record_id' => 'required|integer',
            'pickup_stop'       => 'nullable|string',
        ]);
        $assignment = $bus->assignments()->updateOrCreate(
            ['student_record_id' => $data['student_record_id']],
            ['pickup_stop' => $data['pickup_stop'] ?? null]
        );
        return $assignment;
    }

    public function unassign(Bus $bus, $studentRecordId)
    {
        $bus->assignments()->where('student_record_id', $studentRecordId)->delete();
        return response()->noContent();
    }

    // ── Driver: update own bus location ──────────────────────────────────────

    public function updateLocation(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'speed'     => 'nullable|numeric',
            'heading'   => 'nullable|numeric',
            'is_active' => 'sometimes|boolean',
        ]);

        // Find bus assigned to this driver
        $bus = Bus::where('driver_user_id', $user->id)->where('is_active', true)->first();

        if (!$bus) {
            return response()->json(['message' => 'No active bus assigned to you.'], 404);
        }

        $location = BusLocation::updateOrCreate(
            ['bus_id' => $bus->id],
            array_merge($data, ['located_at' => now()])
        );

        return response()->json(['bus' => $bus->only(['id', 'name', 'number_plate']), 'location' => $location]);
    }

    public function stopSharing(Request $request)
    {
        $user = $request->user();
        $bus  = Bus::where('driver_user_id', $user->id)->first();

        if ($bus) {
            BusLocation::where('bus_id', $bus->id)->update(['is_active' => false]);
        }

        return response()->json(['message' => 'Location sharing stopped.']);
    }

    // ── Parent/Student/Teacher: get bus location ──────────────────────────────

    /**
     * Get the bus assigned to the current user's student record.
     * For parents: finds child's bus. For students: finds own bus.
     * For teachers: returns all active buses.
     */
    public function myBus(Request $request)
    {
        $user  = $request->user();
        $roles = $user->roles->pluck('name')->toArray();

        if (in_array('teacher', $roles) || in_array('admin', $roles)) {
            // Teachers/admins see all active buses
            $buses = Bus::with('location')->where('is_active', true)->get();
            return response()->json(['buses' => $buses]);
        }

        // Find student record id for this user (student or parent)
        $studentRecordIds = $this->getStudentRecordIds($user, $roles);

        if (empty($studentRecordIds)) {
            return response()->json(['message' => 'No student record found.'], 404);
        }

        $assignments = BusAssignment::whereIn('student_record_id', $studentRecordIds)
            ->with('bus.location')
            ->get();

        if ($assignments->isEmpty()) {
            return response()->json(['message' => 'No bus assigned.'], 404);
        }

        $buses = $assignments->map(function ($a) {
            return [
                'bus'         => $a->bus,
                'location'    => $a->bus->location,
                'pickup_stop' => $a->pickup_stop,
            ];
        });

        return response()->json(['buses' => $buses]);
    }

    public function busLocation(Bus $bus)
    {
        $location = $bus->location;
        if (!$location || !$location->is_active) {
            return response()->json(['message' => 'Bus is not sharing location.'], 404);
        }
        return response()->json([
            'bus'      => $bus->only(['id', 'name', 'number_plate', 'driver_name', 'driver_phone']),
            'location' => $location,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function getStudentRecordIds($user, array $roles): array
    {
        if (in_array('student', $roles)) {
            // student's own record — find via app_records where user_id matches
            $records = DB::table('app_records')
                ->join('app_tables', 'app_records.app_table_id', '=', 'app_tables.id')
                ->where('app_tables.name', 'students')
                ->where(function ($q) use ($user) {
                    $q->whereRaw("JSON_EXTRACT(app_records.data, '$.user_id') = ?", [$user->id])
                      ->orWhereRaw("JSON_EXTRACT(app_records.data, '$.email') = ?", [$user->email]);
                })
                ->pluck('app_records.id')
                ->toArray();
            return $records;
        }

        if (in_array('parent', $roles)) {
            // parent's children — find via parent_students link table or app_records
            $records = DB::table('app_records')
                ->join('app_tables', 'app_records.app_table_id', '=', 'app_tables.id')
                ->where('app_tables.name', 'parent_students')
                ->whereRaw("JSON_EXTRACT(app_records.data, '$.parent_user_id') = ?", [$user->id])
                ->pluck(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(app_records.data, '$.student_id'))"))
                ->toArray();

            if (empty($records)) {
                // fallback: find by parent email in parent_students
                $records = DB::table('app_records as pr')
                    ->join('app_tables as pt', 'pr.app_table_id', '=', 'pt.id')
                    ->where('pt.name', 'parent_students')
                    ->whereRaw("JSON_EXTRACT(pr.data, '$.parent_email') = ?", [$user->email])
                    ->pluck(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(pr.data, '$.student_id'))"))
                    ->toArray();
            }
            return array_map('intval', $records);
        }

        return [];
    }
}

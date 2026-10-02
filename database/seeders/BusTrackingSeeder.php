<?php

namespace Database\Seeders;

use App\Models\Bus;
use App\Models\BusAssignment;
use App\Models\BusLocation;
use App\Models\AppRecord;
use App\Models\AppTable;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class BusTrackingSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Ensure 'driver' role exists ────────────────────────────────────
        $driverRole = Role::firstOrCreate(['name' => 'driver', 'guard_name' => 'web']);

        // ── 2. Create driver users ────────────────────────────────────────────
        $driversData = [
            ['name' => 'Raju Yadav',   'email' => 'driver1@school.com', 'phone' => '9100000001'],
            ['name' => 'Suresh Babu',  'email' => 'driver2@school.com', 'phone' => '9100000002'],
            ['name' => 'Mahesh Kumar', 'email' => 'driver3@school.com', 'phone' => '9100000003'],
        ];

        $driverUsers = [];
        foreach ($driversData as $d) {
            $user = User::firstOrCreate(
                ['email' => $d['email']],
                ['name' => $d['name'], 'password' => Hash::make('password')]
            );
            $user->syncRoles([$driverRole]);
            $driverUsers[] = ['user' => $user, 'phone' => $d['phone'], 'name' => $d['name']];
        }

        // ── 3. Create buses linked to drivers ─────────────────────────────────
        // Coordinates near Hyderabad for realistic live tracking test
        $busesData = [
            [
                'name'         => 'Route A – Kukatpally',
                'number_plate' => 'TS 09 EA 1234',
                'driver'       => $driverUsers[0],
                'location'     => ['lat' => 17.4947, 'lng' => 78.3996],
                'stops'        => ['Kukatpally Bus Stop', 'KPHB Colony', 'Miyapur', 'School Gate'],
            ],
            [
                'name'         => 'Route B – Dilsukhnagar',
                'number_plate' => 'TS 09 EB 5678',
                'driver'       => $driverUsers[1],
                'location'     => ['lat' => 17.3688, 'lng' => 78.5247],
                'stops'        => ['Dilsukhnagar', 'LB Nagar', 'Vanasthalipuram', 'School Gate'],
            ],
            [
                'name'         => 'Route C – Secunderabad',
                'number_plate' => 'TS 09 EC 9012',
                'driver'       => $driverUsers[2],
                'location'     => ['lat' => 17.4399, 'lng' => 78.4983],
                'stops'        => ['Secunderabad Station', 'Paradise', 'Begumpet', 'School Gate'],
            ],
        ];

        $buses = [];
        foreach ($busesData as $bd) {
            $bus = Bus::updateOrCreate(
                ['number_plate' => $bd['number_plate']],
                [
                    'name'           => $bd['name'],
                    'driver_name'    => $bd['driver']['name'],
                    'driver_phone'   => $bd['driver']['phone'],
                    'driver_user_id' => $bd['driver']['user']->id,
                    'is_active'      => true,
                ]
            );

            // Seed a live location so map shows immediately
            BusLocation::updateOrCreate(
                ['bus_id' => $bus->id],
                [
                    'latitude'   => $bd['location']['lat'],
                    'longitude'  => $bd['location']['lng'],
                    'speed'      => rand(20, 45),
                    'heading'    => rand(0, 359),
                    'is_active'  => true,
                    'located_at' => now(),
                ]
            );

            $buses[] = ['bus' => $bus, 'stops' => $bd['stops']];
        }

        // ── 4. Assign students to buses ───────────────────────────────────────
        $studentsTable = AppTable::where('name', 'students')->first();
        if (!$studentsTable) {
            $this->command->warn('Students table not found — skipping student assignments.');
            return;
        }

        $students = AppRecord::where('app_table_id', $studentsTable->id)->get();

        if ($students->isEmpty()) {
            $this->command->warn('No students found — skipping student assignments.');
            return;
        }

        // Distribute students across buses round-robin
        foreach ($students as $i => $student) {
            $busIndex  = $i % count($buses);
            $busEntry  = $buses[$busIndex];
            $stopIndex = $i % (count($busEntry['stops']) - 1); // exclude "School Gate"

            BusAssignment::updateOrCreate(
                ['student_record_id' => $student->id],
                [
                    'bus_id'      => $busEntry['bus']->id,
                    'pickup_stop' => $busEntry['stops'][$stopIndex],
                ]
            );
        }

        $this->command->info('Bus tracking seeded:');
        $this->command->info('  • 3 driver users (password: password)');
        $this->command->info('  • 3 buses with live locations');
        $this->command->info('  • ' . $students->count() . ' students assigned to buses');
        $this->command->info('');
        $this->command->info('Driver logins:');
        foreach ($driverUsers as $d) {
            $this->command->info('  ' . $d['user']->email . ' / password');
        }
    }
}

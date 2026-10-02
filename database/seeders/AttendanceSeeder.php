<?php
namespace Database\Seeders;
use App\Models\AppRecord;
use App\Models\AppTable;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $sat = AppTable::where('name','student_attendance')->first();
        $fat = AppTable::where('name','staff_attendance')->first();

        $studentIds = AppRecord::whereHas('appTable',fn($q)=>$q->where('name','students'))->pluck('id')->toArray();
        $staffIds   = AppRecord::whereHas('appTable',fn($q)=>$q->where('name','staff'))->pluck('id')->toArray();

        $dates   = ['2025-03-17','2025-03-18','2025-03-19','2025-03-20','2025-03-21'];
        $statuses = ['present','present','present','absent','late'];

        if($sat){
            foreach($studentIds as $i => $sid){
                foreach($dates as $j => $date){
                    AppRecord::create(['app_table_id'=>$sat->id,'data'=>[
                        'student_id'=>$sid,'date'=>$date,'status'=>$statuses[($i+$j)%5],
                    ]]);
                }
            }
        }

        if($fat){
            foreach($staffIds as $i => $sid){
                foreach($dates as $j => $date){
                    AppRecord::create(['app_table_id'=>$fat->id,'data'=>[
                        'staff_id'=>$sid,'date'=>$date,'status'=>$statuses[($i+$j)%5],
                    ]]);
                }
            }
        }
        $this->command->info('Attendance seeded.');
    }
}

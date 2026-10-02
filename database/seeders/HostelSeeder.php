<?php
namespace Database\Seeders;
use App\Models\AppRecord;
use App\Models\AppTable;
use Illuminate\Database\Seeder;

class HostelSeeder extends Seeder
{
    public function run(): void
    {
        $ht = AppTable::where('name','hostels')->first();
        $at = AppTable::where('name','hostel_allotments')->first();

        $wardenId   = AppRecord::whereHas('appTable',fn($q)=>$q->where('name','staff'))->value('id');
        $studentIds = AppRecord::whereHas('appTable',fn($q)=>$q->where('name','students'))->pluck('id')->toArray();

        $hostelIds = [];
        if($ht){
            $hostels = [
                ['name'=>'Boys Hostel A', 'type'=>'boys',  'capacity'=>100,'monthly_fee'=>4000,'warden_staff_id'=>$wardenId],
                ['name'=>'Boys Hostel B', 'type'=>'boys',  'capacity'=>80, 'monthly_fee'=>3500,'warden_staff_id'=>$wardenId],
                ['name'=>'Girls Hostel A','type'=>'girls', 'capacity'=>100,'monthly_fee'=>4000,'warden_staff_id'=>$wardenId],
                ['name'=>'Girls Hostel B','type'=>'girls', 'capacity'=>60, 'monthly_fee'=>3500,'warden_staff_id'=>$wardenId],
                ['name'=>'Mixed Hostel',  'type'=>'mixed', 'capacity'=>50, 'monthly_fee'=>4500,'warden_staff_id'=>$wardenId],
            ];
            foreach($hostels as $h){
                $r = AppRecord::create(['app_table_id'=>$ht->id,'data'=>$h]);
                $hostelIds[] = $r->id;
            }
        }

        if($at && !empty($hostelIds) && !empty($studentIds)){
            foreach($studentIds as $i => $sid){
                AppRecord::create(['app_table_id'=>$at->id,'data'=>[
                    'student_id' => $sid,
                    'hostel_id'  => $hostelIds[$i % count($hostelIds)],
                    'room_no'    => '10'.($i+1),
                    'from_date'  => '2024-06-01',
                    'to_date'    => '2025-04-30',
                    'status'     => 'active',
                ]]);
            }
        }
        $this->command->info('Hostel seeded.');
    }
}

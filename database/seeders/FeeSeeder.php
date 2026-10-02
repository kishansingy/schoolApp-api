<?php
namespace Database\Seeders;
use App\Models\AppRecord;
use App\Models\AppTable;
use Illuminate\Database\Seeder;

class FeeSeeder extends Seeder
{
    public function run(): void
    {
        $fst = AppTable::where('name','fee_structures')->first();
        $fpt = AppTable::where('name','fee_payments')->first();

        $classId    = AppRecord::whereHas('appTable',fn($q)=>$q->where('name','classes'))->value('id');
        $studentIds = AppRecord::whereHas('appTable',fn($q)=>$q->where('name','students'))->pluck('id')->toArray();

        $structures = [];
        if($fst){
            $fees = [
                ['fee_type'=>'Tuition Fee',  'amount'=>3000,'frequency'=>'monthly', 'academic_year'=>'2024-2025','class_id'=>$classId],
                ['fee_type'=>'Transport Fee','amount'=>1500,'frequency'=>'monthly', 'academic_year'=>'2024-2025','class_id'=>$classId],
                ['fee_type'=>'Library Fee',  'amount'=>500, 'frequency'=>'annually','academic_year'=>'2024-2025','class_id'=>$classId],
                ['fee_type'=>'Sports Fee',   'amount'=>800, 'frequency'=>'annually','academic_year'=>'2024-2025','class_id'=>$classId],
                ['fee_type'=>'Exam Fee',     'amount'=>600, 'frequency'=>'quarterly','academic_year'=>'2024-2025','class_id'=>$classId],
            ];
            foreach($fees as $f){
                $r = AppRecord::create(['app_table_id'=>$fst->id,'data'=>$f]);
                $structures[] = $r->id;
            }
        }

        if($fpt && !empty($structures) && !empty($studentIds)){
            $modes = ['cash','online','bank_transfer','cheque','cash'];
            foreach($studentIds as $i => $sid){
                AppRecord::create(['app_table_id'=>$fpt->id,'data'=>[
                    'student_id'       => $sid,
                    'fee_structure_id' => $structures[0],
                    'amount_paid'      => 3000,
                    'discount'         => 0,
                    'fine'             => 0,
                    'payment_date'     => '2025-03-01',
                    'receipt_no'       => 'RCP-'.str_pad(1001+$i,4,'0',STR_PAD_LEFT),
                    'payment_mode'     => $modes[$i % 5],
                    'status'           => 'paid',
                ]]);
            }
        }
        $this->command->info('Fees seeded.');
    }
}

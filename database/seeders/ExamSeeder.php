<?php
namespace Database\Seeders;
use App\Models\AppRecord;
use App\Models\AppTable;
use Illuminate\Database\Seeder;

class ExamSeeder extends Seeder
{
    public function run(): void
    {
        $et = AppTable::where('name','exams')->first(); if(!$et) return;
        $mt = AppTable::where('name','marks')->first();

        $exams = [
            ['name'=>'Unit Test 1',    'academic_year'=>'2024-2025','start_date'=>'2024-07-10','end_date'=>'2024-07-15','status'=>'completed'],
            ['name'=>'Mid Term',       'academic_year'=>'2024-2025','start_date'=>'2024-09-01','end_date'=>'2024-09-10','status'=>'completed'],
            ['name'=>'Unit Test 2',    'academic_year'=>'2024-2025','start_date'=>'2024-11-05','end_date'=>'2024-11-10','status'=>'completed'],
            ['name'=>'Pre-Final',      'academic_year'=>'2024-2025','start_date'=>'2025-01-15','end_date'=>'2025-01-25','status'=>'ongoing'],
            ['name'=>'Annual Exam',    'academic_year'=>'2024-2025','start_date'=>'2025-03-10','end_date'=>'2025-03-25','status'=>'upcoming'],
        ];
        $examIds = [];
        foreach($exams as $e){
            $r = AppRecord::create(['app_table_id'=>$et->id,'data'=>$e]);
            $examIds[] = $r->id;
        }

        if(!$mt) return;
        $studentIds = AppRecord::whereHas('appTable',fn($q)=>$q->where('name','students'))->pluck('id')->toArray();
        $subjectIds = AppRecord::whereHas('appTable',fn($q)=>$q->where('name','subjects'))->pluck('id')->toArray();
        if(empty($studentIds)||empty($subjectIds)) return;

        foreach(array_slice($examIds,0,3) as $eid){
            foreach(array_slice($studentIds,0,5) as $sid){
                $marks = rand(60,98);
                AppRecord::create(['app_table_id'=>$mt->id,'data'=>[
                    'exam_id'=>$eid,'student_id'=>$sid,'subject_id'=>$subjectIds[0],
                    'marks_obtained'=>$marks,'max_marks'=>100,
                    'grade'=>$marks>=90?'A+':($marks>=75?'A':($marks>=60?'B':'C')),
                ]]);
            }
        }
        $this->command->info('Exams & Marks seeded.');
    }
}

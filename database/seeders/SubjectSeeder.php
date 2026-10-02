<?php
namespace Database\Seeders;
use App\Models\AppRecord;
use App\Models\AppTable;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        $t = AppTable::where('name','subjects')->first(); if(!$t) return;
        $classId  = AppRecord::whereHas('appTable',fn($q)=>$q->where('name','classes'))->value('id');
        $staffId  = AppRecord::whereHas('appTable',fn($q)=>$q->where('name','staff'))->value('id');
        $rows = [
            ['name'=>'Mathematics', 'code'=>'MATH','type'=>'theory',    'class_id'=>$classId,'teacher_id'=>$staffId],
            ['name'=>'Science',     'code'=>'SCI', 'type'=>'both',      'class_id'=>$classId,'teacher_id'=>$staffId],
            ['name'=>'English',     'code'=>'ENG', 'type'=>'theory',    'class_id'=>$classId,'teacher_id'=>$staffId],
            ['name'=>'Social',      'code'=>'SOC', 'type'=>'theory',    'class_id'=>$classId,'teacher_id'=>$staffId],
            ['name'=>'Hindi',       'code'=>'HIN', 'type'=>'theory',    'class_id'=>$classId,'teacher_id'=>$staffId],
        ];
        foreach($rows as $d) AppRecord::create(['app_table_id'=>$t->id,'data'=>$d]);
        $this->command->info('Subjects seeded.');
    }
}

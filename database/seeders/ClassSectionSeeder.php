<?php
namespace Database\Seeders;
use App\Models\AppRecord;
use App\Models\AppTable;
use Illuminate\Database\Seeder;

class ClassSectionSeeder extends Seeder
{
    public function run(): void
    {
        $ct = AppTable::where('name','classes')->first(); if(!$ct) return;
        $st = AppTable::where('name','sections')->first();
        $classes = [
            ['name'=>'Class 1','order'=>1],['name'=>'Class 2','order'=>2],
            ['name'=>'Class 3','order'=>3],['name'=>'Class 4','order'=>4],
            ['name'=>'Class 5','order'=>5],
        ];
        $classIds = [];
        foreach($classes as $c){
            $r = AppRecord::create(['app_table_id'=>$ct->id,'data'=>$c]);
            $classIds[] = $r->id;
        }
        if(!$st) return;
        foreach($classIds as $cid){
            foreach(['A','B','C','D','E'] as $sn){
                AppRecord::create(['app_table_id'=>$st->id,'data'=>['class_id'=>$cid,'name'=>'Section '.$sn,'capacity'=>40]]);
            }
        }
        $this->command->info('Classes & Sections seeded.');
    }
}

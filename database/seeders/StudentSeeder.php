<?php
namespace Database\Seeders;
use App\Models\AppRecord;
use App\Models\AppTable;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $t = AppTable::where('name','students')->first(); if(!$t) return;

        $classIds   = AppRecord::whereHas('appTable',fn($q)=>$q->where('name','classes'))->pluck('id')->toArray();
        $sectionIds = AppRecord::whereHas('appTable',fn($q)=>$q->where('name','sections'))->pluck('id')->toArray();
        $parentIds  = AppRecord::whereHas('appTable',fn($q)=>$q->where('name','parents'))->pluck('id')->toArray();

        $rows = [
            ['first_name'=>'Aayansh', 'last_name'=>'Singanaboina','date_of_birth'=>'2015-08-31','gender'=>'male',  'blood_group'=>'A+','religion'=>'Hindu',  'nationality'=>'Indian','phone'=>'7780170207','email'=>'aayansh@gmail.com', 'address'=>'Flat 101, Green Park, Hyderabad','admission_date'=>'2021-06-01','status'=>'active','city'=>'Hyderabad','state'=>'Telangana','pincode'=>'500001','whatsapp'=>'7780170207','category'=>'general'],
            ['first_name'=>'Priya',   'last_name'=>'Sharma',      'date_of_birth'=>'2014-03-15','gender'=>'female','blood_group'=>'B+','religion'=>'Hindu',  'nationality'=>'Indian','phone'=>'7780170208','email'=>'priya.s@gmail.com',  'address'=>'12 MG Road, Pune',               'admission_date'=>'2020-06-01','status'=>'active','city'=>'Pune',     'state'=>'Maharashtra','pincode'=>'411001','whatsapp'=>'7780170208','category'=>'obc'],
            ['first_name'=>'Arjun',   'last_name'=>'Patel',       'date_of_birth'=>'2013-11-20','gender'=>'male',  'blood_group'=>'O+','religion'=>'Hindu',  'nationality'=>'Indian','phone'=>'7780170209','email'=>'arjun.p@gmail.com',  'address'=>'45 Navrangpura, Ahmedabad',       'admission_date'=>'2019-06-01','status'=>'active','city'=>'Ahmedabad','state'=>'Gujarat',   'pincode'=>'380001','whatsapp'=>'7780170209','category'=>'general'],
            ['first_name'=>'Sneha',   'last_name'=>'Reddy',       'date_of_birth'=>'2016-07-05','gender'=>'female','blood_group'=>'AB+','religion'=>'Hindu', 'nationality'=>'Indian','phone'=>'7780170210','email'=>'sneha.r@gmail.com',  'address'=>'78 Hanamkonda, Warangal',         'admission_date'=>'2022-06-01','status'=>'active','city'=>'Warangal', 'state'=>'Telangana','pincode'=>'506001','whatsapp'=>'7780170210','category'=>'sc'],
            ['first_name'=>'Rahul',   'last_name'=>'Kumar',       'date_of_birth'=>'2015-01-12','gender'=>'male',  'blood_group'=>'A-','religion'=>'Sikh',   'nationality'=>'Indian','phone'=>'7780170211','email'=>'rahul.k@gmail.com',  'address'=>'23 Sector 15, Chandigarh',        'admission_date'=>'2021-06-01','status'=>'active','city'=>'Chandigarh','state'=>'Punjab',   'pincode'=>'160015','whatsapp'=>'7780170211','category'=>'general'],
        ];

        foreach($rows as $i => $d){
            $d['admission_no'] = 'ADM-' . str_pad(1001 + $i, 4, '0', STR_PAD_LEFT);
            $d['class_id']     = $classIds[$i]    ?? null;
            $d['section_id']   = $sectionIds[$i]  ?? null;
            $d['father_id']    = $parentIds[$i * 2]     ?? null;
            $d['mother_id']    = $parentIds[$i * 2 + 1] ?? null;
            AppRecord::create(['app_table_id'=>$t->id,'data'=>$d]);
        }
        $this->command->info('Students seeded.');
    }
}

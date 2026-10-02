<?php
namespace Database\Seeders;
use App\Models\AppRecord;
use App\Models\AppTable;
use Illuminate\Database\Seeder;

class ParentSeeder extends Seeder
{
    public function run(): void
    {
        $t = AppTable::where('name','parents')->first(); if(!$t) return;
        $rows = [
            ['first_name'=>'Suresh',  'last_name'=>'Singanaboina','relation'=>'father','phone'=>'8000000001','email'=>'suresh@gmail.com', 'occupation'=>'Business',  'city'=>'Hyderabad','state'=>'Telangana','pincode'=>'500001'],
            ['first_name'=>'Lakshmi', 'last_name'=>'Singanaboina','relation'=>'mother','phone'=>'8000000002','email'=>'lakshmi@gmail.com','occupation'=>'Homemaker', 'city'=>'Hyderabad','state'=>'Telangana','pincode'=>'500001'],
            ['first_name'=>'Ravi',    'last_name'=>'Sharma',      'relation'=>'father','phone'=>'8000000003','email'=>'ravi@gmail.com',   'occupation'=>'Engineer',  'city'=>'Pune',     'state'=>'Maharashtra','pincode'=>'411001'],
            ['first_name'=>'Priya',   'last_name'=>'Patel',       'relation'=>'mother','phone'=>'8000000004','email'=>'priya@gmail.com',  'occupation'=>'Teacher',   'city'=>'Ahmedabad','state'=>'Gujarat',   'pincode'=>'380001'],
            ['first_name'=>'Mohan',   'last_name'=>'Reddy',       'relation'=>'father','phone'=>'8000000005','email'=>'mohan@gmail.com',  'occupation'=>'Farmer',    'city'=>'Warangal', 'state'=>'Telangana','pincode'=>'506001'],
        ];
        foreach($rows as $d) AppRecord::create(['app_table_id'=>$t->id,'data'=>$d]);
        $this->command->info('Parents seeded.');
    }
}

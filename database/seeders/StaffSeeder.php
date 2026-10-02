<?php
namespace Database\Seeders;
use App\Models\AppRecord;
use App\Models\AppTable;
use Illuminate\Database\Seeder;

class StaffSeeder extends Seeder
{
    public function run(): void
    {
        $t = AppTable::where('name','staff')->first(); if(!$t) return;
        $rows = [
            ['staff_no'=>'STF001','first_name'=>'Ramesh',  'last_name'=>'Kumar',   'staff_type'=>'teaching',    'designation'=>'Teacher',    'department'=>'Science',  'phone'=>'9000000001','email'=>'ramesh@school.com',  'joining_date'=>'2020-06-01','salary'=>35000,'status'=>'active'],
            ['staff_no'=>'STF002','first_name'=>'Sunita',  'last_name'=>'Sharma',  'staff_type'=>'teaching',    'designation'=>'Teacher',    'department'=>'Maths',    'phone'=>'9000000002','email'=>'sunita@school.com',  'joining_date'=>'2019-07-15','salary'=>32000,'status'=>'active'],
            ['staff_no'=>'STF003','first_name'=>'Vijay',   'last_name'=>'Patil',   'staff_type'=>'teaching',    'designation'=>'HOD',        'department'=>'English',  'phone'=>'9000000003','email'=>'vijay@school.com',   'joining_date'=>'2018-04-01','salary'=>45000,'status'=>'active'],
            ['staff_no'=>'STF004','first_name'=>'Meena',   'last_name'=>'Reddy',   'staff_type'=>'non_teaching','designation'=>'Accountant', 'department'=>'Accounts', 'phone'=>'9000000004','email'=>'meena@school.com',   'joining_date'=>'2021-01-10','salary'=>28000,'status'=>'active'],
            ['staff_no'=>'STF005','first_name'=>'Arun',    'last_name'=>'Singh',   'staff_type'=>'teaching',    'designation'=>'Teacher',    'department'=>'Social',   'phone'=>'9000000005','email'=>'arun@school.com',    'joining_date'=>'2022-06-01','salary'=>30000,'status'=>'active'],
        ];
        foreach($rows as $d) AppRecord::create(['app_table_id'=>$t->id,'data'=>$d]);
        $this->command->info('Staff seeded.');
    }
}

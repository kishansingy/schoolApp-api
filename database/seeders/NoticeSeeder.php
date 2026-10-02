<?php
namespace Database\Seeders;
use App\Models\AppRecord;
use App\Models\AppTable;
use Illuminate\Database\Seeder;

class NoticeSeeder extends Seeder
{
    public function run(): void
    {
        $t = AppTable::where('name','notices')->first(); if(!$t) return;
        $rows = [
            ['title'=>'Annual Day Celebration',    'content'=>'Annual Day will be celebrated on 15th April 2025. All students must participate.','audience'=>'all',      'publish_date'=>'2025-03-20','expiry_date'=>'2025-04-15'],
            ['title'=>'Parent-Teacher Meeting',    'content'=>'PTM scheduled for 5th April 2025 from 10 AM to 1 PM. Parents are requested to attend.','audience'=>'parents',  'publish_date'=>'2025-03-22','expiry_date'=>'2025-04-05'],
            ['title'=>'Summer Vacation Notice',    'content'=>'School will remain closed from 1st May to 15th June 2025 for summer vacation.','audience'=>'all',      'publish_date'=>'2025-03-24','expiry_date'=>'2025-05-01'],
            ['title'=>'Staff Meeting',             'content'=>'Mandatory staff meeting on 28th March 2025 at 4 PM in the conference hall.','audience'=>'staff',    'publish_date'=>'2025-03-24','expiry_date'=>'2025-03-28'],
            ['title'=>'Exam Schedule Released',    'content'=>'Annual exam schedule for 2024-25 has been released. Check the notice board.','audience'=>'students', 'publish_date'=>'2025-03-10','expiry_date'=>'2025-03-31'],
        ];
        foreach($rows as $d) AppRecord::create(['app_table_id'=>$t->id,'data'=>$d]);
        $this->command->info('Notices seeded.');
    }
}

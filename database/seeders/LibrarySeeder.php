<?php
namespace Database\Seeders;
use App\Models\AppRecord;
use App\Models\AppTable;
use Illuminate\Database\Seeder;

class LibrarySeeder extends Seeder
{
    public function run(): void
    {
        $bt = AppTable::where('name','books')->first();
        $it = AppTable::where('name','book_issues')->first();

        $studentIds = AppRecord::whereHas('appTable',fn($q)=>$q->where('name','students'))->pluck('id')->toArray();

        $bookIds = [];
        if($bt){
            $books = [
                ['title'=>'Mathematics Class 5','author'=>'R.D. Sharma',   'isbn'=>'978-81-219-0001-1','publisher'=>'Dhanpat Rai','publish_year'=>2020,'category'=>'Textbook',  'total_copies'=>5,'available_copies'=>3],
                ['title'=>'Science Explorer',   'author'=>'NCERT',          'isbn'=>'978-81-219-0002-2','publisher'=>'NCERT',     'publish_year'=>2021,'category'=>'Textbook',  'total_copies'=>8,'available_copies'=>6],
                ['title'=>'English Grammar',    'author'=>'Wren & Martin',  'isbn'=>'978-81-219-0003-3','publisher'=>'S.Chand',   'publish_year'=>2019,'category'=>'Reference', 'total_copies'=>4,'available_copies'=>4],
                ['title'=>'India After Gandhi', 'author'=>'Ramachandra Guha','isbn'=>'978-81-219-0004-4','publisher'=>'Picador',  'publish_year'=>2017,'category'=>'History',   'total_copies'=>3,'available_copies'=>2],
                ['title'=>'Wings of Fire',      'author'=>'A.P.J. Abdul Kalam','isbn'=>'978-81-219-0005-5','publisher'=>'Orient','publish_year'=>2015,'category'=>'Biography', 'total_copies'=>6,'available_copies'=>5],
            ];
            foreach($books as $b){
                $r = AppRecord::create(['app_table_id'=>$bt->id,'data'=>$b]);
                $bookIds[] = $r->id;
            }
        }

        if($it && !empty($bookIds) && !empty($studentIds)){
            foreach($studentIds as $i => $sid){
                AppRecord::create(['app_table_id'=>$it->id,'data'=>[
                    'book_id'     => $bookIds[$i % count($bookIds)],
                    'student_id'  => $sid,
                    'issue_date'  => '2025-03-01',
                    'due_date'    => '2025-03-15',
                    'return_date' => $i < 3 ? '2025-03-14' : null,
                    'fine_amount' => 0,
                    'status'      => $i < 3 ? 'returned' : 'issued',
                ]]);
            }
        }
        $this->command->info('Library seeded.');
    }
}

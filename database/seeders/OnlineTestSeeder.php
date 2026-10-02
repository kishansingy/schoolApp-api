<?php

namespace Database\Seeders;

use App\Models\OnlineTest;
use App\Models\OnlineTestSection;
use App\Models\OnlineTestQuestion;
use Illuminate\Database\Seeder;

class OnlineTestSeeder extends Seeder
{
    public function run(): void
    {
        if (OnlineTest::exists()) {
            $this->command->info('Online tests already seeded — skipping.');
            return;
        }

        $test = OnlineTest::create([
            'title'        => 'Science Mid Term Test',
            'instructions' => 'Read each question carefully. Each section has a time limit. You cannot go back to a closed section.',
            'total_time'   => 30,
            'max_marks'    => 20,
            'status'       => 'published',
        ]);

        // ── Section A: Physics ────────────────────────────────────────────────
        $secA = OnlineTestSection::create([
            'test_id'            => $test->id,
            'name'               => 'Section A — Physics',
            'time_limit'         => 10,
            'marks_per_question' => 1,
            'order'              => 0,
        ]);

        $physicsQs = [
            ['q' => 'What is the SI unit of force?',           'a' => 'Newton',   'b' => 'Joule',    'c' => 'Watt',    'd' => 'Pascal',  'ans' => 'a'],
            ['q' => 'Speed of light in vacuum is approximately?', 'a' => '3×10⁸ m/s','b' => '3×10⁶ m/s','c' => '3×10¹⁰ m/s','d' => '3×10⁴ m/s','ans' => 'a'],
            ['q' => 'Which law states F = ma?',                'a' => 'Newton\'s 1st','b' => 'Newton\'s 2nd','c' => 'Newton\'s 3rd','d' => 'Ohm\'s Law','ans' => 'b'],
            ['q' => 'Sound travels fastest in?',               'a' => 'Air',       'b' => 'Water',    'c' => 'Steel',   'd' => 'Vacuum',  'ans' => 'c'],
            ['q' => 'The unit of electric current is?',        'a' => 'Volt',      'b' => 'Ohm',      'c' => 'Ampere',  'd' => 'Watt',    'ans' => 'c'],
        ];

        foreach ($physicsQs as $i => $q) {
            OnlineTestQuestion::create([
                'test_id' => $test->id, 'section_id' => $secA->id,
                'question_text' => $q['q'], 'question_type' => 'mcq',
                'option_a' => $q['a'], 'option_b' => $q['b'], 'option_c' => $q['c'], 'option_d' => $q['d'],
                'correct_answer' => $q['ans'], 'marks' => 1, 'order' => $i,
            ]);
        }

        // ── Section B: Chemistry ──────────────────────────────────────────────
        $secB = OnlineTestSection::create([
            'test_id'            => $test->id,
            'name'               => 'Section B — Chemistry',
            'time_limit'         => 10,
            'marks_per_question' => 1,
            'order'              => 1,
        ]);

        $chemQs = [
            ['q' => 'Water is a compound of hydrogen and oxygen.',  'type' => 'true_false', 'ans' => 'true'],
            ['q' => 'The atomic number of Carbon is 8.',            'type' => 'true_false', 'ans' => 'false'],
            ['q' => 'NaCl is the chemical formula for common salt.','type' => 'true_false', 'ans' => 'true'],
            ['q' => 'Oxygen is a metal.',                           'type' => 'true_false', 'ans' => 'false'],
            ['q' => 'pH of pure water is 7.',                       'type' => 'true_false', 'ans' => 'true'],
        ];

        foreach ($chemQs as $i => $q) {
            OnlineTestQuestion::create([
                'test_id' => $test->id, 'section_id' => $secB->id,
                'question_text' => $q['q'], 'question_type' => $q['type'],
                'correct_answer' => $q['ans'], 'marks' => 1, 'order' => $i,
            ]);
        }

        // ── Section C: Biology MCQ ────────────────────────────────────────────
        $secC = OnlineTestSection::create([
            'test_id'            => $test->id,
            'name'               => 'Section C — Biology',
            'time_limit'         => 10,
            'marks_per_question' => 2,
            'order'              => 2,
        ]);

        $bioQs = [
            ['q' => 'The powerhouse of the cell is?',          'a' => 'Nucleus',    'b' => 'Mitochondria','c' => 'Ribosome',  'd' => 'Vacuole',   'ans' => 'b'],
            ['q' => 'How many chambers does the human heart have?','a' => '2',       'b' => '3',           'c' => '4',         'd' => '5',         'ans' => 'c'],
            ['q' => 'Photosynthesis occurs in?',               'a' => 'Roots',      'b' => 'Stem',        'c' => 'Chloroplast','d' => 'Mitochondria','ans' => 'c'],
            ['q' => 'DNA stands for?',                         'a' => 'Deoxyribonucleic Acid','b' => 'Diribonucleic Acid','c' => 'Deoxyribose Nucleic Acid','d' => 'None','ans' => 'a'],
            ['q' => 'Which organ produces insulin?',           'a' => 'Liver',      'b' => 'Kidney',      'c' => 'Pancreas',  'd' => 'Spleen',    'ans' => 'c'],
        ];

        foreach ($bioQs as $i => $q) {
            OnlineTestQuestion::create([
                'test_id' => $test->id, 'section_id' => $secC->id,
                'question_text' => $q['q'], 'question_type' => 'mcq',
                'option_a' => $q['a'], 'option_b' => $q['b'], 'option_c' => $q['c'], 'option_d' => $q['d'],
                'correct_answer' => $q['ans'], 'marks' => 2, 'order' => $i,
            ]);
        }

        $this->command->info('Online test seeded: 3 sections, 15 questions.');
    }
}

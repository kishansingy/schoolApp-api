<?php

namespace Database\Seeders;

use App\Models\AppField;
use App\Models\AppTable;
use Illuminate\Database\Seeder;

/**
 * Adds class_id + section_id cascade fields to fee_payments,
 * and sets reference_qualifier on section_id and student_id fields
 * so the form cascades: Class → Section → Student.
 *
 * Also applies the same pattern to student_attendance and any other
 * tables that have a student_id reference.
 */
class FeePaymentFieldsSeeder extends Seeder
{
    public function run(): void
    {
        $classes  = AppTable::where('name', 'classes')->first();
        $sections = AppTable::where('name', 'sections')->first();
        $students = AppTable::where('name', 'students')->first();

        // ── fee_payments: add class + section filter fields ───────────────────
        $fp = AppTable::where('name', 'fee_payments')->first();
        if ($fp && $classes && $sections) {
            $order = AppField::where('app_table_id', $fp->id)->max('order') + 1;

            AppField::firstOrCreate(
                ['app_table_id' => $fp->id, 'name' => 'class_id'],
                ['label' => 'Class', 'type' => 'reference', 'reference_table_id' => $classes->id,
                 'mandatory' => false, 'display' => false, 'active' => true, 'order' => $order]
            );

            AppField::firstOrCreate(
                ['app_table_id' => $fp->id, 'name' => 'section_id'],
                ['label' => 'Section', 'type' => 'reference', 'reference_table_id' => $sections->id,
                 'reference_qualifier' => 'class_id={class_id}',
                 'mandatory' => false, 'display' => false, 'active' => true, 'order' => $order + 1]
            );

            // Set cascade qualifier on student_id: filter by class + section
            AppField::where('app_table_id', $fp->id)->where('name', 'student_id')
                ->update(['reference_qualifier' => 'class_id={class_id},section_id={section_id}']);
        }

        // ── student_attendance: same cascade ──────────────────────────────────
        $sa = AppTable::where('name', 'student_attendance')->first();
        if ($sa && $classes && $sections) {
            $order = AppField::where('app_table_id', $sa->id)->max('order') + 1;

            AppField::firstOrCreate(
                ['app_table_id' => $sa->id, 'name' => 'class_id'],
                ['label' => 'Class', 'type' => 'reference', 'reference_table_id' => $classes->id,
                 'mandatory' => false, 'display' => false, 'active' => true, 'order' => $order]
            );

            AppField::firstOrCreate(
                ['app_table_id' => $sa->id, 'name' => 'section_id'],
                ['label' => 'Section', 'type' => 'reference', 'reference_table_id' => $sections->id,
                 'reference_qualifier' => 'class_id={class_id}',
                 'mandatory' => false, 'display' => false, 'active' => true, 'order' => $order + 1]
            );

            AppField::where('app_table_id', $sa->id)->where('name', 'student_id')
                ->update(['reference_qualifier' => 'class_id={class_id},section_id={section_id}']);
        }

        // ── marks: same cascade ───────────────────────────────────────────────
        $marks = AppTable::where('name', 'marks')->first();
        if ($marks && $classes && $sections) {
            $order = AppField::where('app_table_id', $marks->id)->max('order') + 1;

            AppField::firstOrCreate(
                ['app_table_id' => $marks->id, 'name' => 'class_id'],
                ['label' => 'Class', 'type' => 'reference', 'reference_table_id' => $classes->id,
                 'mandatory' => false, 'display' => false, 'active' => true, 'order' => $order]
            );

            AppField::firstOrCreate(
                ['app_table_id' => $marks->id, 'name' => 'section_id'],
                ['label' => 'Section', 'type' => 'reference', 'reference_table_id' => $sections->id,
                 'reference_qualifier' => 'class_id={class_id}',
                 'mandatory' => false, 'display' => false, 'active' => true, 'order' => $order + 1]
            );

            AppField::where('app_table_id', $marks->id)->where('name', 'student_id')
                ->update(['reference_qualifier' => 'class_id={class_id},section_id={section_id}']);
        }

        // ── sections table: set qualifier so it cascades from classes ─────────
        if ($sections && $classes) {
            // The section's class_id field should reference classes (already does)
            // No qualifier needed on sections itself — it IS the filtered table
        }

        // ── students table: section_id cascades from class_id ─────────────────
        if ($students && $sections) {
            AppField::where('app_table_id', $students->id)->where('name', 'section_id')
                ->update(['reference_qualifier' => 'class_id={class_id}']);
        }

        $this->command->info('Cascade qualifiers configured for fee_payments, attendance, marks, students.');
    }
}

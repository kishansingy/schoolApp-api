<?php

namespace Database\Seeders;

use App\Models\AppField;
use App\Models\AppTable;
use Illuminate\Database\Seeder;

class StudentParentModulesSeeder extends Seeder
{
    private function makeTable(string $name, string $label, string $type = 'standard'): AppTable
    {
        return AppTable::firstOrCreate(['name' => $name], [
            'label'      => $label,
            'table_type' => $type,
            'can_read'   => true,
            'can_create' => true,
            'can_update' => true,
            'can_delete' => true,
        ]);
    }

    private function addFields(AppTable $table, array $fields): void
    {
        foreach ($fields as $i => $f) {
            AppField::firstOrCreate(
                ['app_table_id' => $table->id, 'name' => $f['name']],
                array_merge([
                    'label'              => $f['label'],
                    'type'               => $f['type'] ?? 'string',
                    'mandatory'          => $f['mandatory'] ?? false,
                    'display'            => $f['display'] ?? false,
                    'active'             => true,
                    'order'              => $i,
                    'choices'            => $f['choices'] ?? null,
                    'default_value'      => $f['default'] ?? null,
                    'reference_table_id' => $f['ref_table_id'] ?? null,
                ], $f['extra'] ?? [])
            );
        }
    }

    public function run(): void
    {
        $this->seedEvents();
        $this->seedHomework();
        $this->seedYoutubeChannel();
        $this->seedRemarks();

        $this->command->info('Student/Parent modules seeded successfully.');
    }

    // ── Events ────────────────────────────────────────────────────────────────

    private function seedEvents(): void
    {
        $t = $this->makeTable('events', 'School Events', 'header');
        $this->addFields($t, [
            ['name' => 'title',       'label' => 'Event Title',  'type' => 'string',  'mandatory' => true, 'display' => true],
            ['name' => 'description', 'label' => 'Description',  'type' => 'html'],
            ['name' => 'event_date',  'label' => 'Event Date',   'type' => 'date',    'mandatory' => true, 'display' => true],
            ['name' => 'end_date',    'label' => 'End Date',     'type' => 'date'],
            ['name' => 'venue',       'label' => 'Venue',        'type' => 'string',  'display' => true],
            ['name' => 'audience',    'label' => 'Audience',     'type' => 'choice',  'default' => 'all',
                'choices' => [
                    ['label' => 'All',      'value' => 'all'],
                    ['label' => 'Students', 'value' => 'students'],
                    ['label' => 'Parents',  'value' => 'parents'],
                    ['label' => 'Staff',    'value' => 'staff'],
                ]],
            ['name' => 'status',      'label' => 'Status',       'type' => 'choice',  'default' => 'upcoming',
                'choices' => [
                    ['label' => 'Upcoming',  'value' => 'upcoming'],
                    ['label' => 'Ongoing',   'value' => 'ongoing'],
                    ['label' => 'Completed', 'value' => 'completed'],
                    ['label' => 'Cancelled', 'value' => 'cancelled'],
                ]],
        ]);

        // Event media (images/videos) as detail table
        $media = $this->makeTable('event_media', 'Event Media', 'detail');
        $media->detail_of_table_id = $t->id;
        $media->save();

        $this->addFields($media, [
            ['name' => 'event_id',   'label' => 'Event',      'type' => 'reference', 'mandatory' => true,
                'ref_table_id' => $t->id],
            ['name' => 'media_type', 'label' => 'Media Type', 'type' => 'choice',    'mandatory' => true, 'display' => true,
                'choices' => [
                    ['label' => 'Image', 'value' => 'image'],
                    ['label' => 'Video', 'value' => 'video'],
                ]],
            ['name' => 'file_path',  'label' => 'File',       'type' => 'image',     'mandatory' => true],
            ['name' => 'caption',    'label' => 'Caption',    'type' => 'string'],
            ['name' => 'sort_order', 'label' => 'Sort Order', 'type' => 'integer',   'default' => '0'],
        ]);
    }

    // ── Homework ──────────────────────────────────────────────────────────────

    private function seedHomework(): void
    {
        $classes  = AppTable::where('name', 'classes')->first();
        $sections = AppTable::where('name', 'sections')->first();
        $subjects = AppTable::where('name', 'subjects')->first();
        $staff    = AppTable::where('name', 'staff')->first();

        $t = $this->makeTable('homework', 'Homework');
        $this->addFields($t, [
            ['name' => 'title',           'label' => 'Title',           'type' => 'string',    'mandatory' => true, 'display' => true],
            ['name' => 'class_id',        'label' => 'Class',           'type' => 'reference', 'mandatory' => true, 'display' => true,
                'ref_table_id' => $classes?->id],
            ['name' => 'section_id',      'label' => 'Section',         'type' => 'reference', 'display' => true,
                'ref_table_id' => $sections?->id],
            ['name' => 'subject_id',      'label' => 'Subject',         'type' => 'reference', 'mandatory' => true, 'display' => true,
                'ref_table_id' => $subjects?->id],
            ['name' => 'teacher_id',      'label' => 'Assigned By',     'type' => 'reference', 'mandatory' => true,
                'ref_table_id' => $staff?->id],
            ['name' => 'description',     'label' => 'Description',     'type' => 'html'],
            ['name' => 'assigned_date',   'label' => 'Assigned Date',   'type' => 'date',      'mandatory' => true, 'display' => true],
            ['name' => 'submission_date', 'label' => 'Submission Date', 'type' => 'date',      'mandatory' => true, 'display' => true],
            ['name' => 'attachment',      'label' => 'Attachment',      'type' => 'file'],
            ['name' => 'status',          'label' => 'Status',          'type' => 'choice',    'default' => 'active',
                'choices' => [
                    ['label' => 'Active', 'value' => 'active'],
                    ['label' => 'Closed', 'value' => 'closed'],
                ]],
        ]);
    }

    // ── YouTube Channel ───────────────────────────────────────────────────────

    private function seedYoutubeChannel(): void
    {
        $t = $this->makeTable('youtube_videos', 'YouTube Channel');
        $this->addFields($t, [
            ['name' => 'title',         'label' => 'Video Title',   'type' => 'string',  'mandatory' => true, 'display' => true],
            ['name' => 'description',   'label' => 'Description',   'type' => 'text'],
            ['name' => 'youtube_url',   'label' => 'YouTube URL',   'type' => 'string',  'mandatory' => true, 'display' => true],
            ['name' => 'youtube_id',    'label' => 'YouTube ID',    'type' => 'string',
                'extra' => ['readonly' => true]],
            ['name' => 'thumbnail_url', 'label' => 'Thumbnail URL', 'type' => 'string'],
            ['name' => 'audience',      'label' => 'Audience',      'type' => 'choice',  'default' => 'all',
                'choices' => [
                    ['label' => 'All',      'value' => 'all'],
                    ['label' => 'Students', 'value' => 'students'],
                    ['label' => 'Parents',  'value' => 'parents'],
                    ['label' => 'Staff',    'value' => 'staff'],
                ]],
            ['name' => 'sort_order',    'label' => 'Sort Order',    'type' => 'integer', 'default' => '0'],
            ['name' => 'is_active',     'label' => 'Active',        'type' => 'boolean', 'default' => 'true', 'display' => true],
        ]);
    }

    // ── Remarks & Complaints ──────────────────────────────────────────────────

    private function seedRemarks(): void
    {
        $students = AppTable::where('name', 'students')->first();
        $staff    = AppTable::where('name', 'staff')->first();

        $t = $this->makeTable('remarks', 'Remarks & Complaints');
        $this->addFields($t, [
            ['name' => 'student_id',  'label' => 'Student',     'type' => 'reference', 'mandatory' => true, 'display' => true,
                'ref_table_id' => $students?->id],
            ['name' => 'teacher_id',  'label' => 'Teacher',     'type' => 'reference', 'mandatory' => true, 'display' => true,
                'ref_table_id' => $staff?->id],
            ['name' => 'type',        'label' => 'Type',        'type' => 'choice',    'mandatory' => true, 'display' => true,
                'choices' => [
                    ['label' => 'Remark',    'value' => 'remark'],
                    ['label' => 'Complaint', 'value' => 'complaint'],
                ]],
            ['name' => 'remark_by',   'label' => 'Raised By',   'type' => 'choice',    'mandatory' => true, 'display' => true,
                'choices' => [
                    ['label' => 'Teacher', 'value' => 'teacher'],
                    ['label' => 'Parent',  'value' => 'parent'],
                ]],
            ['name' => 'message',     'label' => 'Message',     'type' => 'text',      'mandatory' => true],
            ['name' => 'visibility',  'label' => 'Visibility',  'type' => 'choice',    'default' => 'parent_only',
                'choices' => [
                    ['label' => 'Parent Only', 'value' => 'parent_only'],
                    ['label' => 'All',         'value' => 'all'],
                ]],
            ['name' => 'status',      'label' => 'Status',      'type' => 'choice',    'default' => 'open', 'display' => true,
                'choices' => [
                    ['label' => 'Open',         'value' => 'open'],
                    ['label' => 'Acknowledged', 'value' => 'acknowledged'],
                    ['label' => 'Resolved',     'value' => 'resolved'],
                ]],
            ['name' => 'response',      'label' => 'Response',      'type' => 'text'],
            ['name' => 'responded_at',  'label' => 'Responded At',  'type' => 'datetime'],
        ]);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Syllabus extends Model
{
    protected $fillable = [
        'title', 'class_id', 'subject_id', 'academic_year',
        'description', 'topics', 'status', 'created_by',
    ];

    protected $casts = [
        'topics' => 'array',
    ];
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Syllabus;
use Illuminate\Http\Request;

class SyllabusController extends Controller
{
    // Admin/Teacher: list all syllabuses (with optional filters)
    public function index(Request $request)
    {
        $query = Syllabus::query();

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }
        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }
        if ($request->filled('academic_year')) {
            $query->where('academic_year', $request->academic_year);
        }

        return $query->latest()->get();
    }

    // Admin/Teacher: create syllabus
    public function store(Request $request)
    {
        $data = $request->validate([
            'title'         => 'required|string|max:255',
            'class_id'      => 'required|integer',
            'subject_id'    => 'required|integer',
            'academic_year' => 'nullable|string|max:20',
            'description'   => 'nullable|string',
            'topics'        => 'nullable|array',
            'topics.*.title'       => 'required|string',
            'topics.*.description' => 'nullable|string',
            'topics.*.order'       => 'nullable|integer',
            'status'        => 'in:draft,published',
        ]);
        $data['created_by'] = $request->user()->id;

        return response()->json(Syllabus::create($data), 201);
    }

    // Admin/Teacher: get single syllabus
    public function show(Syllabus $syllabus)
    {
        return $syllabus;
    }

    // Admin/Teacher: update syllabus
    public function update(Request $request, Syllabus $syllabus)
    {
        $data = $request->validate([
            'title'         => 'sometimes|required|string|max:255',
            'class_id'      => 'sometimes|required|integer',
            'subject_id'    => 'sometimes|required|integer',
            'academic_year' => 'nullable|string|max:20',
            'description'   => 'nullable|string',
            'topics'        => 'nullable|array',
            'topics.*.title'       => 'required|string',
            'topics.*.description' => 'nullable|string',
            'topics.*.order'       => 'nullable|integer',
            'status'        => 'in:draft,published',
        ]);

        $syllabus->update($data);
        return $syllabus->fresh();
    }

    // Admin/Teacher: delete syllabus
    public function destroy(Syllabus $syllabus)
    {
        $syllabus->delete();
        return response()->noContent();
    }

    // Student/Parent: view published syllabuses (filtered by class/subject)
    public function published(Request $request)
    {
        $query = Syllabus::where('status', 'published');

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }
        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }
        if ($request->filled('academic_year')) {
            $query->where('academic_year', $request->academic_year);
        }

        return $query->latest()->get();
    }
}

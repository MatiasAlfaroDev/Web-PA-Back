<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\JsonResponse;

class CourseController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Course::with('teacher:id,first_name,last_name')->withCount('lessons')->get()
        );
    }

    public function show(Course $course): JsonResponse
    {
        $course->load([
            'teacher:id,first_name,last_name',
            'lessons:id,course_id,title,position',
            'challenges' => fn ($q) => $q->where('published', true)
                ->select('id', 'course_id', 'lesson_id', 'title', 'points', 'difficulty', 'position'),
        ]);

        return response()->json($course);
    }
}

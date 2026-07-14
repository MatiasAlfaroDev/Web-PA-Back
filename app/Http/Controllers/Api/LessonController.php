<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function show(Request $request, Lesson $lesson): JsonResponse
    {
        $isTeacher = $request->user()->isTeacher();
        abort_unless($isTeacher || ($lesson->isAvailableNow() && $lesson->course->isAvailableNow()), 404);

        $lesson->load([
            'challenges' => fn ($q) => $q->where('published', true)
                ->select('id', 'course_id', 'lesson_id', 'title', 'points', 'difficulty', 'position'),
        ]);

        return response()->json($lesson);
    }
}

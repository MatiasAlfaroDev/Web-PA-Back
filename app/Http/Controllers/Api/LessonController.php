<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use Illuminate\Http\JsonResponse;

class LessonController extends Controller
{
    public function show(Lesson $lesson): JsonResponse
    {
        $lesson->load([
            'challenges' => fn ($q) => $q->where('published', true)
                ->select('id', 'course_id', 'lesson_id', 'title', 'points', 'difficulty', 'position'),
        ]);

        return response()->json($lesson);
    }
}

<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LessonController extends Controller
{
    public function store(Request $request, Course $course): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'position' => ['sometimes', 'integer', 'min:0'],
        ]);

        return response()->json($course->lessons()->create($data), 201);
    }

    public function update(Request $request, Lesson $lesson): JsonResponse
    {
        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'content' => ['sometimes', 'string'],
            'position' => ['sometimes', 'integer', 'min:0'],
        ]);

        $lesson->update($data);

        return response()->json($lesson);
    }

    public function destroy(Lesson $lesson): Response
    {
        $lesson->delete();

        return response()->noContent();
    }
}

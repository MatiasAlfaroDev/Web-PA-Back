<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class ChallengeController extends Controller
{
    public function store(Request $request, Course $course): JsonResponse
    {
        $data = $this->validated($request, $course);

        return response()->json($course->challenges()->create($data), 201);
    }

    public function update(Request $request, Challenge $challenge): JsonResponse
    {
        $challenge->update($this->validated($request, $challenge->course));

        return response()->json($challenge);
    }

    public function destroy(Challenge $challenge): Response
    {
        $challenge->delete();

        return response()->noContent();
    }

    public function show(Challenge $challenge): JsonResponse
    {
        // Teacher view: includes hidden test cases.
        return response()->json($challenge->load('testCases'));
    }

    private function validated(Request $request, Course $course): array
    {
        return $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'statement' => ['sometimes', 'required', 'string'],
            'starter_code' => ['sometimes', 'nullable', 'string'],
            'points' => ['sometimes', 'integer', 'min:1'],
            'min_points' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'difficulty' => ['sometimes', Rule::in(['easy', 'medium', 'hard'])],
            'position' => ['sometimes', 'integer', 'min:0'],
            'published' => ['sometimes', 'boolean'],
            'language' => ['sometimes', Rule::in(['javascript', 'java'])],
            'lesson_id' => ['sometimes', 'nullable', Rule::exists('lessons', 'id')->where('course_id', $course->id)],
        ]);
    }
}

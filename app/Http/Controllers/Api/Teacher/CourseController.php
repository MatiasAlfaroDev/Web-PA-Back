<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CourseController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules());

        $course = Course::create($data + ['teacher_id' => $request->user()->id]);

        return response()->json($course, 201);
    }

    public function update(Request $request, Course $course): JsonResponse
    {
        $data = $request->validate(array_merge($this->rules(), [
            'title' => ['sometimes', 'string', 'max:255'],
        ]));

        $course->update($data);

        return response()->json($course);
    }

    private function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'published' => ['sometimes', 'boolean'],
            'available_from' => ['sometimes', 'nullable', 'date'],
            'available_until' => ['sometimes', 'nullable', 'date'],
        ];
    }

    public function destroy(Course $course): Response
    {
        $course->delete();

        return response()->noContent();
    }
}

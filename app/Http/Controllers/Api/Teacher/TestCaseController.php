<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\TestCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TestCaseController extends Controller
{
    public function store(Request $request, Challenge $challenge): JsonResponse
    {
        $data = $request->validate([
            'stdin' => ['nullable', 'string'],
            'expected_output' => ['required', 'string'],
            'is_hidden' => ['sometimes', 'boolean'],
        ]);

        return response()->json($challenge->testCases()->create($data), 201);
    }

    public function update(Request $request, TestCase $testCase): JsonResponse
    {
        $data = $request->validate([
            'stdin' => ['sometimes', 'nullable', 'string'],
            'expected_output' => ['sometimes', 'required', 'string'],
            'is_hidden' => ['sometimes', 'boolean'],
        ]);

        $testCase->update($data);

        return response()->json($testCase);
    }

    public function destroy(TestCase $testCase): Response
    {
        $testCase->delete();

        return response()->noContent();
    }
}

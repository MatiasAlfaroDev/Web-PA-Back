<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChallengeController extends Controller
{
    public function show(Request $request, Challenge $challenge): JsonResponse
    {
        abort_unless($challenge->published || $request->user()->isTeacher(), 404);

        $challenge->load([
            'testCases' => fn ($q) => $q->where('is_hidden', false)
                ->select('id', 'challenge_id', 'stdin', 'expected_output'),
        ]);

        $challenge->setAttribute(
            'my_best_score',
            $challenge->submissions()->where('user_id', $request->user()->id)->max('score') ?? 0
        );

        return response()->json($challenge);
    }
}

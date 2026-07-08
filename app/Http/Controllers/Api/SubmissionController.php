<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\JudgeSubmission;
use App\Models\Challenge;
use App\Models\Submission;
use App\Support\Progress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{
    public function store(Request $request, Challenge $challenge): JsonResponse
    {
        abort_unless($challenge->published, 404);
        abort_unless(Progress::unlocked($request->user(), $challenge), 403, 'Este challenge todavía no está desbloqueado.');

        $data = $request->validate([
            'code' => ['required', 'string', 'max:65535'],
        ]);

        $submission = $challenge->submissions()->create([
            'user_id' => $request->user()->id,
            'code' => $data['code'],
        ]);

        JudgeSubmission::dispatch($submission);

        return response()->json($submission, 202);
    }

    public function show(Request $request, Submission $submission): JsonResponse
    {
        abort_unless(
            $submission->user_id === $request->user()->id || $request->user()->isTeacher(),
            403
        );

        return response()->json($submission);
    }

    public function indexForChallenge(Request $request, Challenge $challenge): JsonResponse
    {
        return response()->json(
            $challenge->submissions()
                ->where('user_id', $request->user()->id)
                ->latest()
                ->get()
        );
    }
}

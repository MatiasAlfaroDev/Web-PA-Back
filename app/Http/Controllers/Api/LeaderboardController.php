<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class LeaderboardController extends Controller
{
    public function index(): JsonResponse
    {
        // Best attempt per (user, challenge), then sum per user.
        // ponytail: computed on read; add users.total_points + increment-on-judge if this gets slow
        $best = DB::table('submissions')
            ->select('user_id', 'challenge_id')
            ->selectRaw('MAX(score) as best_score')
            ->selectRaw("MAX(CASE WHEN status = 'passed' THEN 1 ELSE 0 END) as solved")
            ->groupBy('user_id', 'challenge_id');

        $rows = DB::table('users')
            ->leftJoinSub($best, 'best', 'best.user_id', '=', 'users.id')
            ->where('users.role', 'student')
            ->groupBy('users.id', 'users.first_name', 'users.last_name')
            ->select('users.id', 'users.first_name', 'users.last_name')
            ->selectRaw('COALESCE(SUM(best.best_score), 0) as total_score')
            ->selectRaw('COALESCE(SUM(best.solved), 0) as challenges_solved')
            ->orderByDesc('total_score')
            ->orderBy('users.id')
            ->get();

        return response()->json(
            $rows->values()->map(fn ($row, $i) => ['rank' => $i + 1] + (array) $row)
        );
    }
}

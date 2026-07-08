<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\Progress;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
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

        // Solve dates for all students in one query → streak per user.
        $datesByUser = DB::table('submissions')
            ->where('status', 'passed')
            ->selectRaw('user_id, created_at')
            ->get()
            ->groupBy('user_id')
            ->map(fn ($group) => $group
                ->map(fn ($r) => Carbon::parse($r->created_at)->setTimezone(Progress::TZ)->toDateString())
                ->all());

        return response()->json(
            $rows->values()->map(fn ($row, $i) => ['rank' => $i + 1] + (array) $row + [
                'streak' => Progress::streakFromDates($datesByUser[$row->id] ?? []),
            ])
        );
    }
}

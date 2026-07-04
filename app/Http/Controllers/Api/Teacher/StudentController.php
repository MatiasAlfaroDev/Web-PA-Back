<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    public function index(): JsonResponse
    {
        $best = DB::table('submissions')
            ->select('user_id', 'challenge_id')
            ->selectRaw('MAX(score) as best_score')
            ->selectRaw("MAX(CASE WHEN status = 'passed' THEN 1 ELSE 0 END) as solved")
            ->groupBy('user_id', 'challenge_id');

        $students = DB::table('users')
            ->leftJoinSub($best, 'best', 'best.user_id', '=', 'users.id')
            ->where('users.role', 'student')
            ->groupBy('users.id', 'users.first_name', 'users.last_name', 'users.ci', 'users.email', 'users.email_verified_at')
            ->select('users.id', 'users.first_name', 'users.last_name', 'users.ci', 'users.email', 'users.email_verified_at')
            ->selectRaw('COALESCE(SUM(best.best_score), 0) as total_score')
            ->selectRaw('COALESCE(SUM(best.solved), 0) as challenges_solved')
            ->orderBy('users.last_name')
            ->get();

        return response()->json($students);
    }

    public function show(User $student): JsonResponse
    {
        abort_unless($student->role === 'student', 404);

        $progress = DB::table('challenges')
            ->leftJoin('submissions', function ($join) use ($student) {
                $join->on('submissions.challenge_id', '=', 'challenges.id')
                    ->where('submissions.user_id', $student->id);
            })
            ->groupBy('challenges.id', 'challenges.title', 'challenges.points', 'challenges.course_id')
            ->select('challenges.id', 'challenges.title', 'challenges.points', 'challenges.course_id')
            ->selectRaw('COUNT(submissions.id) as attempts')
            ->selectRaw('COALESCE(MAX(submissions.score), 0) as best_score')
            ->selectRaw("COALESCE(MAX(CASE WHEN submissions.status = 'passed' THEN 1 ELSE 0 END), 0) as solved")
            ->orderBy('challenges.id')
            ->get();

        return response()->json([
            'student' => $student,
            'progress' => $progress,
            'recent_submissions' => $student->submissions()->with('challenge:id,title')->latest()->limit(20)->get(),
        ]);
    }
}

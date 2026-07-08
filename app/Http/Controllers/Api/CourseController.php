<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Support\Progress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CourseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $courses = Course::with('teacher:id,first_name,last_name')
            ->withCount('lessons')
            ->withCount(['challenges' => fn ($q) => $q->where('published', true)])
            ->get();

        // Solved (published) challenges per course for this user.
        $solvedByCourse = DB::table('submissions')
            ->join('challenges', 'challenges.id', '=', 'submissions.challenge_id')
            ->where('submissions.user_id', $request->user()->id)
            ->where('submissions.status', 'passed')
            ->where('challenges.published', true)
            ->groupBy('challenges.course_id')
            ->selectRaw('challenges.course_id, COUNT(DISTINCT submissions.challenge_id) as solved')
            ->pluck('solved', 'course_id');

        $courses->each(fn ($course) => $course->setAttribute(
            'solved_count', (int) ($solvedByCourse[$course->id] ?? 0)
        ));

        return response()->json($courses);
    }

    public function show(Request $request, Course $course): JsonResponse
    {
        $isTeacher = $request->user()->isTeacher();

        $course->load([
            'teacher:id,first_name,last_name',
            'lessons:id,course_id,title,position',
            // Teachers editing a course see drafts too; students only published.
            'challenges' => fn ($q) => $q
                ->when(! $isTeacher, fn ($qq) => $qq->where('published', true))
                ->select('id', 'course_id', 'lesson_id', 'title', 'points', 'difficulty', 'position', 'published'),
        ]);

        $solvedDates = Progress::solvedDates($request->user());
        $statuses = Progress::statuses($course->challenges, $solvedDates);

        $course->challenges->each(fn ($ch) => $ch->setAttribute('status', $statuses[$ch->id] ?? 'locked'));

        return response()->json($course);
    }
}

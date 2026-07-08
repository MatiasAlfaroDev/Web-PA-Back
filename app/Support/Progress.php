<?php

namespace App\Support;

use App\Models\Challenge;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Sequential + daily-gate unlock, streak and progress — all derived from
 * submissions, no extra tables. A challenge is "solved" when a submission
 * reached status 'passed' (all test cases). Dates use Uruguay time so the
 * "one a day" gate lines up with the student's calendar day.
 */
class Progress
{
    public const TZ = 'America/Montevideo';

    /** challenge_id => 'Y-m-d' of the first day the user solved it. */
    public static function solvedDates(User $user): array
    {
        return DB::table('submissions')
            ->where('user_id', $user->id)
            ->where('status', 'passed')
            ->groupBy('challenge_id')
            ->selectRaw('challenge_id, MIN(created_at) as first_passed')
            ->pluck('first_passed', 'challenge_id')
            ->map(fn ($ts) => Carbon::parse($ts)->setTimezone(self::TZ)->toDateString())
            ->all();
    }

    /**
     * status for each challenge in a course-ordered list:
     *   done    – solved
     *   current – unlocked but not solved (previous solved on an earlier day)
     *   locked  – previous not solved, or solved today (unlocks tomorrow)
     *
     * @param  iterable<Challenge>  $challenges  ordered by position
     * @return array<int,string> challenge_id => status
     */
    public static function statuses(iterable $challenges, array $solvedDates): array
    {
        $today = Carbon::now(self::TZ)->toDateString();
        $out = [];
        $prevSolvedDate = null; // solved date of the previous challenge, or null if unsolved
        $first = true;

        foreach ($challenges as $ch) {
            $solvedOn = $solvedDates[$ch->id] ?? null;
            $unlocked = $first || ($prevSolvedDate !== null && $today > $prevSolvedDate);

            $out[$ch->id] = $solvedOn ? 'done' : ($unlocked ? 'current' : 'locked');

            $prevSolvedDate = $solvedOn;
            $first = false;
        }

        return $out;
    }

    /** Whether the user may submit to this challenge (its course-position gate is open). */
    public static function unlocked(User $user, Challenge $challenge): bool
    {
        $challenges = Challenge::where('course_id', $challenge->course_id)
            ->where('published', true)
            ->orderBy('position')
            ->get(['id']);

        $status = self::statuses($challenges, self::solvedDates($user))[$challenge->id] ?? 'locked';

        return $status !== 'locked';
    }

    /** Consecutive calendar days (Uruguay time) ending today or yesterday with >=1 solve. */
    public static function streak(User $user): int
    {
        return self::streakFromDates(array_values(self::solvedDates($user)));
    }

    /**
     * Streak from a list of 'Y-m-d' solve dates (any order, duplicates ok).
     * Consecutive days ending today or yesterday.
     *
     * @param  array<string>  $ymdDates
     */
    public static function streakFromDates(array $ymdDates): int
    {
        $days = collect($ymdDates)->unique()->flip();
        if ($days->isEmpty()) {
            return 0;
        }

        $cursor = Carbon::now(self::TZ)->startOfDay();
        if (! $days->has($cursor->toDateString())) {
            $cursor->subDay();
            if (! $days->has($cursor->toDateString())) {
                return 0;
            }
        }

        $streak = 0;
        while ($days->has($cursor->toDateString())) {
            $streak++;
            $cursor->subDay();
        }

        return $streak;
    }

    /** Total points: best score per challenge, summed. */
    public static function points(User $user): int
    {
        return (int) DB::table('submissions')
            ->where('user_id', $user->id)
            ->groupBy('challenge_id')
            ->selectRaw('MAX(score) as best')
            ->get()
            ->sum('best');
    }

    /** challenge_id set the user has solved (as a lookup collection). */
    public static function solvedCount(User $user): int
    {
        return count(self::solvedDates($user));
    }
}

<?php

namespace App\Jobs;

use App\Models\Submission;
use App\Services\Judge0Client;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class JudgeSubmission implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    public function __construct(public Submission $submission) {}

    public function handle(Judge0Client $judge0): void
    {
        $submission = $this->submission;
        $testCases = $submission->challenge->testCases;

        if ($testCases->isEmpty()) {
            $submission->update(['status' => 'error', 'judge_output' => ['message' => 'El challenge no tiene test cases.']]);

            return;
        }

        $submission->update(['status' => 'judging', 'total_count' => $testCases->count()]);

        try {
            $tokens = $judge0->submitBatch($testCases->map(fn ($tc) => [
                'source_code' => $submission->code,
                'language_id' => $submission->language_id,
                'stdin' => $tc->stdin,
                'expected_output' => $tc->expected_output,
            ])->all());

            // ponytail: simple 1s poll loop, move to Judge0 callbacks if volume grows
            $deadline = now()->addSeconds(90);
            do {
                sleep(1);
                $results = $judge0->getBatch($tokens);
                $pending = collect($results)->contains(fn ($r) => ($r['status']['id'] ?? 1) < 3);
            } while ($pending && now()->lessThan($deadline));

            if ($pending) {
                $submission->update(['status' => 'error', 'judge_output' => ['message' => 'Timeout esperando resultados del judge.']]);

                return;
            }

            $this->grade($submission, $testCases, $results);
        } catch (\Throwable $e) {
            $submission->update(['status' => 'error', 'judge_output' => ['message' => $e->getMessage()]]);

            throw $e;
        }
    }

    private function grade(Submission $submission, $testCases, array $results): void
    {
        $cases = collect($results)->values()->map(function (array $r, int $i) use ($testCases) {
            return [
                'test_case_id' => $testCases[$i]->id,
                'hidden' => $testCases[$i]->is_hidden,
                'status' => $r['status']['description'] ?? 'Unknown',
                'passed' => ($r['status']['id'] ?? 0) === 3,
                'time' => $r['time'] ?? null,
                'memory' => $r['memory'] ?? null,
                'stderr' => isset($r['stderr']) && $r['stderr'] ? base64_decode($r['stderr']) : null,
                'compile_output' => isset($r['compile_output']) && $r['compile_output'] ? base64_decode($r['compile_output']) : null,
            ];
        });

        $passed = $cases->where('passed', true)->count();
        $total = $cases->count();
        $compileError = $cases->every(fn ($c) => $c['status'] === 'Compilation Error');

        $submission->update([
            'status' => match (true) {
                $compileError => 'error',
                $passed === $total => 'passed',
                $passed > 0 => 'partial',
                default => 'failed',
            },
            'passed_count' => $passed,
            'total_count' => $total,
            'score' => (int) round($submission->challenge->points * $passed / $total),
            'judge_output' => ['cases' => $cases->all()],
        ]);
    }
}

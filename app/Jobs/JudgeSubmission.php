<?php

namespace App\Jobs;

use App\Models\Submission;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class JudgeSubmission implements ShouldQueue
{
    use Queueable;

    public int $timeout = 90;

    public function __construct(public Submission $submission) {}

    public function handle(): void
    {
        $submission = $this->submission;
        $testCases = $submission->challenge->testCases;

        if ($testCases->isEmpty()) {
            $submission->update(['status' => 'error', 'judge_output' => ['message' => 'El challenge no tiene test cases.']]);

            return;
        }

        $submission->update(['status' => 'judging', 'total_count' => $testCases->count()]);

        if ($submission->challenge->language === 'java') {
            $this->handleJava($submission, $testCases);

            return;
        }

        $this->handleJavascript($submission, $testCases);
    }

    private function handleJavascript(Submission $submission, $testCases): void
    {
        $payload = json_encode([
            'code' => $submission->code,
            'tests' => $testCases->map(fn ($tc) => ['stdin' => $tc->stdin])->all(),
            'timeoutMs' => 3000,
        ]);

        $result = Process::path(base_path())
            ->input($payload)
            ->timeout(30)
            ->run(['node', 'judge/run.mjs']);

        if (! $result->successful()) {
            $submission->update(['status' => 'error', 'judge_output' => ['message' => $result->errorOutput() ?: 'El judge falló al ejecutar el código.']]);

            return;
        }

        $results = json_decode($result->output(), true);

        if (! is_array($results)) {
            $submission->update(['status' => 'error', 'judge_output' => ['message' => 'Respuesta inválida del judge.']]);

            return;
        }

        $this->grade($submission, $testCases, $results);
    }

    // ponytail: sin sandbox de contenedor — javac/java corren directo en el worker
    // (mismo nivel de confianza "aula" que el judge de JS, ver README). Piston
    // self-hosted requeriría delegación de cgroup v2 que Railway no otorga a
    // ningún contenedor; si algún día hay que aislar más fuerte, ese es el upgrade path.
    private function handleJava(Submission $submission, $testCases): void
    {
        $dir = storage_path('app/judge-java/'.Str::uuid());
        File::ensureDirectoryExists($dir);

        try {
            File::put($dir.'/Main.java', $submission->code);

            $compile = Process::path($dir)->timeout(15)->run(['javac', 'Main.java']);

            if (! $compile->successful()) {
                $error = trim($compile->errorOutput()) ?: 'Error de compilación.';
                $this->grade($submission, $testCases, $testCases->map(fn () => ['stdout' => '', 'error' => $error])->all());

                return;
            }

            $results = $testCases->map(function ($tc) use ($dir) {
                $run = Process::path($dir)->input($tc->stdin ?? '')->timeout(10)->run(['java', 'Main']);

                return [
                    'stdout' => $run->output(),
                    'error' => $run->successful() ? null : (trim($run->errorOutput()) ?: 'Error en ejecución.'),
                ];
            })->all();

            $this->grade($submission, $testCases, $results);
        } finally {
            File::deleteDirectory($dir);
        }
    }

    private function grade(Submission $submission, $testCases, array $results): void
    {
        $cases = collect($results)->values()->map(function (array $r, int $i) use ($testCases) {
            $passed = $r['error'] === null && trim($r['stdout'] ?? '') === trim($testCases[$i]->expected_output);

            return [
                'test_case_id' => $testCases[$i]->id,
                'hidden' => $testCases[$i]->is_hidden,
                'passed' => $passed,
                'stdout' => $r['stdout'] ?? '',
                'error' => $r['error'],
            ];
        });

        $passed = $cases->where('passed', true)->count();
        $total = $cases->count();

        $submission->update([
            'status' => match (true) {
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

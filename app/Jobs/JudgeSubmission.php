<?php

namespace App\Jobs;

use App\Models\Submission;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;

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

    // ponytail: sin JDK local ni Docker — cada test case se compila y corre en
    // la API pública de Piston (emkc.org), un POST por test. Sin rate-limit
    // garantizado; si el aula lo satura, self-host Piston (docker run
    // engineer-man/piston) es el upgrade path, mismo contrato HTTP.
    private function handleJava(Submission $submission, $testCases): void
    {
        $version = $this->pistonJavaVersion();
        $url = rtrim(config('services.piston.url'), '/').'/execute';

        $results = $testCases->map(function ($tc) use ($submission, $url, $version) {
            try {
                $response = Http::timeout(20)->post($url, [
                    'language' => 'java',
                    'version' => $version,
                    'files' => [['name' => 'Main.java', 'content' => $submission->code]],
                    'stdin' => $tc->stdin ?? '',
                ])->throw()->json();
            } catch (\Throwable $e) {
                return ['stdout' => '', 'error' => 'Error al contactar el judge de Java: '.$e->getMessage()];
            }

            if (isset($response['compile']) && ($response['compile']['code'] ?? 0) !== 0) {
                return ['stdout' => '', 'error' => trim($response['compile']['stderr'] ?? '') ?: 'Error de compilación.'];
            }

            $run = $response['run'] ?? [];

            return [
                'stdout' => $run['stdout'] ?? '',
                'error' => ($run['code'] ?? 0) !== 0 ? (trim($run['stderr'] ?? '') ?: 'Error en ejecución.') : null,
            ];
        })->all();

        $this->grade($submission, $testCases, $results);
    }

    private function pistonJavaVersion(): string
    {
        return Cache::remember('piston_java_version', now()->addDay(), function () {
            $runtimes = Http::timeout(10)->get(rtrim(config('services.piston.url'), '/').'/runtimes')->json();

            return collect($runtimes)->firstWhere('language', 'java')['version'] ?? '15.0.2';
        });
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

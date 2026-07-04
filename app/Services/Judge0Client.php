<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class Judge0Client
{
    /**
     * Submit a batch of runs. Each entry: source_code, language_id, stdin, expected_output.
     * Returns the list of Judge0 tokens.
     */
    public function submitBatch(array $entries): array
    {
        $submissions = array_map(fn (array $e) => [
            'source_code' => base64_encode($e['source_code']),
            'language_id' => $e['language_id'],
            'stdin' => base64_encode($e['stdin'] ?? ''),
            'expected_output' => base64_encode($e['expected_output']),
        ], $entries);

        $response = Http::baseUrl(config('services.judge0.url'))
            ->post('/submissions/batch?base64_encoded=true', ['submissions' => $submissions])
            ->throw();

        return array_column($response->json(), 'token');
    }

    /**
     * Fetch batch results. Judge0 status ids: 1=queued, 2=processing, 3=accepted,
     * 4=wrong answer, 5=TLE, 6=compilation error, 7+=runtime/internal errors.
     */
    public function getBatch(array $tokens): array
    {
        $response = Http::baseUrl(config('services.judge0.url'))
            ->get('/submissions/batch', [
                'tokens' => implode(',', $tokens),
                'base64_encoded' => 'true',
                'fields' => 'token,status,stdout,stderr,compile_output,time,memory',
            ])
            ->throw();

        return $response->json('submissions');
    }
}

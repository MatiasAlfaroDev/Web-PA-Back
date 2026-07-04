<?php

namespace Tests\Feature;

use App\Models\Challenge;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SubmissionTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private Challenge $challenge;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->create();
        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = Course::create(['title' => 'Curso', 'teacher_id' => $teacher->id]);

        $this->challenge = $course->challenges()->create([
            'title' => 'Suma',
            'statement' => 'Sumá dos números.',
            'points' => 100,
            'published' => true,
        ]);

        $this->challenge->testCases()->createMany([
            ['stdin' => '1 2', 'expected_output' => '3', 'is_hidden' => false],
            ['stdin' => '2 3', 'expected_output' => '5', 'is_hidden' => false],
            ['stdin' => '4 5', 'expected_output' => '9', 'is_hidden' => true],
            ['stdin' => '6 7', 'expected_output' => '13', 'is_hidden' => true],
        ]);
    }

    private int $judgePassing = 0;

    /** Fake Judge0 once: first $judgePassing cases accepted, rest wrong answer. */
    private function fakeJudge0(int $passing, int $total = 4): void
    {
        $this->judgePassing = $passing;

        Http::preventStrayRequests();
        Http::fake(function ($request) use ($total) {
            if ($request->method() === 'POST') {
                return Http::response(array_map(fn ($i) => ['token' => "tok-$i"], range(1, $total)));
            }

            return Http::response(['submissions' => array_map(fn ($i) => [
                'token' => "tok-$i",
                'status' => $i <= $this->judgePassing
                    ? ['id' => 3, 'description' => 'Accepted']
                    : ['id' => 4, 'description' => 'Wrong Answer'],
                'time' => '0.01',
                'memory' => 1024,
            ], range(1, $total))]);
        });
    }

    public function test_partial_score_and_leaderboard_best_attempt(): void
    {
        // First attempt: 2 of 4 pass → 50 points, partial.
        $this->fakeJudge0(passing: 2, total: 4);

        $id = $this->actingAs($this->student)
            ->postJson("/api/challenges/{$this->challenge->id}/submissions", [
                'language_id' => 71,
                'code' => 'print(sum(map(int, input().split())))',
            ])->assertStatus(202)->json('id');

        $this->actingAs($this->student)->getJson("/api/submissions/$id")
            ->assertOk()
            ->assertJsonPath('status', 'partial')
            ->assertJsonPath('score', 50)
            ->assertJsonPath('passed_count', 2);

        // Second attempt: all pass → 100 points.
        $this->judgePassing = 4;
        $this->actingAs($this->student)
            ->postJson("/api/challenges/{$this->challenge->id}/submissions", [
                'language_id' => 71,
                'code' => 'better code',
            ])->assertStatus(202);

        // Leaderboard shows best attempt only (100, not 150).
        $this->actingAs($this->student)->getJson('/api/leaderboard')
            ->assertOk()
            ->assertJsonPath('0.total_score', 100)
            ->assertJsonPath('0.challenges_solved', 1)
            ->assertJsonPath('0.rank', 1);
    }

    public function test_hidden_test_cases_not_exposed_to_students(): void
    {
        $response = $this->actingAs($this->student)
            ->getJson("/api/challenges/{$this->challenge->id}")
            ->assertOk();

        $this->assertCount(2, $response->json('test_cases'));
    }

    public function test_students_cannot_read_others_submissions(): void
    {
        $this->fakeJudge0(passing: 4, total: 4);
        $id = $this->actingAs($this->student)
            ->postJson("/api/challenges/{$this->challenge->id}/submissions", [
                'language_id' => 71,
                'code' => 'x',
            ])->json('id');

        $other = User::factory()->create();
        $this->actingAs($other)->getJson("/api/submissions/$id")->assertForbidden();
    }
}

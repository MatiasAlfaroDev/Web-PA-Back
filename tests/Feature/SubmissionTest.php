<?php

namespace Tests\Feature;

use App\Models\Challenge;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    private const CORRECT_CODE = "const [a, b] = stdin.split(' ').map(Number); console.log(a + b);";

    // Only correct while the first number is below 3 — passes the first two
    // (non-hidden) test cases and fails the other two.
    private const PARTIAL_CODE = "const [a, b] = stdin.split(' ').map(Number); console.log(a < 3 ? a + b : 0);";

    public function test_partial_score_and_leaderboard_best_attempt(): void
    {
        // First attempt: 2 of 4 pass → 50 points, partial.
        $id = $this->actingAs($this->student)
            ->postJson("/api/challenges/{$this->challenge->id}/submissions", [
                'code' => self::PARTIAL_CODE,
            ])->assertStatus(202)->json('id');

        $this->actingAs($this->student)->getJson("/api/submissions/$id")
            ->assertOk()
            ->assertJsonPath('status', 'partial')
            ->assertJsonPath('score', 50)
            ->assertJsonPath('passed_count', 2);

        // Second attempt: all pass → 100 points.
        $this->actingAs($this->student)
            ->postJson("/api/challenges/{$this->challenge->id}/submissions", [
                'code' => self::CORRECT_CODE,
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
        $id = $this->actingAs($this->student)
            ->postJson("/api/challenges/{$this->challenge->id}/submissions", [
                'code' => self::CORRECT_CODE,
            ])->json('id');

        $other = User::factory()->create();
        $this->actingAs($other)->getJson("/api/submissions/$id")->assertForbidden();
    }
}

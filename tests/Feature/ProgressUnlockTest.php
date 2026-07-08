<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgressUnlockTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private Course $course;

    private array $challenges = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->create();
        $teacher = User::factory()->create(['role' => 'teacher']);
        $this->course = Course::create(['title' => 'Java POO', 'teacher_id' => $teacher->id]);

        foreach ([1, 2] as $pos) {
            $ch = $this->course->challenges()->create([
                'title' => "Reto $pos",
                'statement' => 'Hacé algo.',
                'points' => 100,
                'position' => $pos,
                'published' => true,
            ]);
            $ch->testCases()->create(['stdin' => '1', 'expected_output' => '1', 'is_hidden' => false]);
            $this->challenges[$pos] = $ch;
        }
    }

    private function solve(int $pos): int
    {
        return $this->actingAs($this->student)
            ->postJson("/api/challenges/{$this->challenges[$pos]->id}/submissions", [
                'code' => 'console.log(stdin);',
            ])->assertStatus(202)->json('id');
    }

    private function statuses(): array
    {
        return collect(
            $this->actingAs($this->student)
                ->getJson("/api/courses/{$this->course->id}")
                ->assertOk()
                ->json('challenges')
        )->pluck('status', 'position')->all();
    }

    public function test_sequential_daily_gate_unlock(): void
    {
        // Start: first current, second locked.
        $this->assertSame(['current', 'locked'], array_values($this->statuses()));

        // Can't submit to a locked challenge.
        $this->actingAs($this->student)
            ->postJson("/api/challenges/{$this->challenges[2]->id}/submissions", [
                'code' => 'x',
            ])->assertStatus(403);

        // Solve #1 today → done, but #2 stays locked until tomorrow (daily gate).
        $this->solve(1);
        $this->assertSame(['done', 'locked'], array_values($this->statuses()));

        // Move the solve to yesterday → #2 unlocks.
        Submission::where('challenge_id', $this->challenges[1]->id)
            ->update(['created_at' => now()->subDay()]);
        $this->assertSame(['done', 'current'], array_values($this->statuses()));

        // Now submitting to #2 is allowed.
        $this->solve(2);
    }

    public function test_profile_and_leaderboard_stats(): void
    {
        $this->actingAs($this->student)->getJson('/api/profile')
            ->assertOk()
            ->assertJsonPath('points', 0)
            ->assertJsonPath('streak', 0)
            ->assertJsonPath('solved', 0);

        $this->solve(1);

        $this->actingAs($this->student)->getJson('/api/profile')
            ->assertOk()
            ->assertJsonPath('points', 100)
            ->assertJsonPath('streak', 1)
            ->assertJsonPath('solved', 1);

        $this->actingAs($this->student)->getJson('/api/leaderboard')
            ->assertOk()
            ->assertJsonPath('0.total_score', 100)
            ->assertJsonPath('0.streak', 1);
    }

    public function test_course_index_reports_progress(): void
    {
        $this->solve(1);

        $this->actingAs($this->student)->getJson('/api/courses')
            ->assertOk()
            ->assertJsonPath('0.challenges_count', 2)
            ->assertJsonPath('0.solved_count', 1);
    }
}

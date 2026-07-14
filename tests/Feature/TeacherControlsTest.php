<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherControlsTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    private User $student;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->create(['role' => 'teacher']);
        $this->student = User::factory()->create();
        $this->course = Course::create(['title' => 'Curso', 'teacher_id' => $this->teacher->id]);
    }

    public function test_unpublished_course_is_listed_locked_but_not_enterable_by_students(): void
    {
        $this->course->update(['published' => false]);

        // Still listed (as locked, with a countdown) — just not enterable.
        $this->actingAs($this->student)->getJson('/api/courses')
            ->assertOk()->assertJsonCount(1)->assertJsonPath('0.published', false);
        $this->actingAs($this->student)->getJson("/api/courses/{$this->course->id}")->assertNotFound();

        $this->actingAs($this->teacher)->getJson('/api/courses')->assertOk()->assertJsonCount(1);
        $this->actingAs($this->teacher)->getJson("/api/courses/{$this->course->id}")->assertOk();
    }

    public function test_course_outside_availability_window_is_locked_for_students(): void
    {
        $this->course->update(['available_from' => now()->addDay()]);

        $this->actingAs($this->student)->getJson("/api/courses/{$this->course->id}")->assertNotFound();

        $this->course->update(['available_from' => null, 'available_until' => now()->subDay()]);

        $this->actingAs($this->student)->getJson("/api/courses/{$this->course->id}")->assertNotFound();
    }

    public function test_teacher_can_lock_and_unlock_the_site(): void
    {
        $this->actingAs($this->teacher)
            ->putJson('/api/site-lock', ['duration_minutes' => 10])
            ->assertOk()
            ->assertJsonStructure(['locked_until']);

        // Locked out.
        $this->actingAs($this->student)->getJson('/api/courses')->assertStatus(423);
        // Profile and the lock status itself stay reachable.
        $this->actingAs($this->student)->getJson('/api/profile')->assertOk();
        $this->actingAs($this->student)->getJson('/api/site-lock')->assertOk()->assertJsonPath('locked_until', fn ($v) => $v !== null);

        // Teacher bypasses their own lock.
        $this->actingAs($this->teacher)->getJson('/api/courses')->assertOk();

        $this->actingAs($this->teacher)->deleteJson('/api/site-lock')->assertNoContent();
        $this->actingAs($this->student)->getJson('/api/courses')->assertOk();
    }

    public function test_students_cannot_set_the_site_lock(): void
    {
        $this->actingAs($this->student)->putJson('/api/site-lock', ['duration_minutes' => 5])->assertForbidden();
    }

    public function test_score_decays_per_solver_and_floors_at_min_points(): void
    {
        $challenge = $this->course->challenges()->create([
            'title' => 'Reto', 'statement' => 'x', 'points' => 100, 'min_points' => 85, 'published' => true,
        ]);
        $challenge->testCases()->create(['stdin' => '', 'expected_output' => 'ok', 'is_hidden' => false]);

        $solvers = User::factory()->count(5)->create();

        foreach ($solvers as $i => $solver) {
            $id = $this->actingAs($solver)
                ->postJson("/api/challenges/{$challenge->id}/submissions", ['code' => "console.log('ok')"])
                ->assertStatus(202)->json('id');

            // 100, 95, 90, 85, 85 (floored) for solvers 0..4.
            $expected = max(85, 100 - $i * 5);
            $this->actingAs($solver)->getJson("/api/submissions/$id")->assertJsonPath('score', $expected);
        }
    }
}

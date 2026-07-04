<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_students_cannot_use_teacher_endpoints(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)->postJson('/api/courses', ['title' => 'Hack'])->assertForbidden();
        $this->actingAs($student)->getJson('/api/teacher/students')->assertForbidden();
    }

    public function test_teacher_crud_and_student_progress(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        User::factory()->create(); // one student

        $courseId = $this->actingAs($teacher)
            ->postJson('/api/courses', ['title' => 'Nuevo curso'])
            ->assertCreated()->json('id');

        $this->actingAs($teacher)
            ->postJson("/api/courses/$courseId/lessons", ['title' => 'L1', 'content' => '# Hola'])
            ->assertCreated();

        $challengeId = $this->actingAs($teacher)
            ->postJson("/api/courses/$courseId/challenges", [
                'title' => 'C1', 'statement' => 'Hacé algo.', 'points' => 50, 'published' => true,
            ])->assertCreated()->json('id');

        $this->actingAs($teacher)
            ->postJson("/api/challenges/$challengeId/test-cases", [
                'stdin' => '1', 'expected_output' => '1', 'is_hidden' => true,
            ])->assertCreated();

        $this->actingAs($teacher)->getJson('/api/teacher/students')
            ->assertOk()->assertJsonCount(1);

        $this->actingAs($teacher)->deleteJson("/api/courses/$courseId")->assertNoContent();
        $this->assertDatabaseMissing('courses', ['id' => $courseId]);
        $this->assertDatabaseMissing('challenges', ['id' => $challengeId]); // cascade
    }
}

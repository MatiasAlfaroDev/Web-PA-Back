<?php

namespace Tests\Feature;

use App\Models\Challenge;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JavaSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private Challenge $challenge;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->create();
        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = Course::create(['title' => 'Curso Java', 'teacher_id' => $teacher->id]);

        $this->challenge = $course->challenges()->create([
            'title' => 'Suma',
            'statement' => 'Sumá dos enteros leídos por stdin.',
            'points' => 100,
            'published' => true,
            'language' => 'java',
        ]);

        $this->challenge->testCases()->createMany([
            ['stdin' => '2 3', 'expected_output' => '5', 'is_hidden' => false],
            ['stdin' => '10 20', 'expected_output' => '30', 'is_hidden' => true],
        ]);
    }

    private const CORRECT_CODE = <<<'JAVA'
        import java.util.Scanner;
        public class Main {
            public static void main(String[] args) {
                Scanner sc = new Scanner(System.in);
                int a = sc.nextInt();
                int b = sc.nextInt();
                System.out.println(a + b);
            }
        }
        JAVA;

    private const BROKEN_CODE = 'public class Main { public static void main(String[] a) { syntax error } }';

    public function test_correct_java_submission_passes(): void
    {
        $id = $this->actingAs($this->student)
            ->postJson("/api/challenges/{$this->challenge->id}/submissions", [
                'code' => self::CORRECT_CODE,
            ])->assertStatus(202)->json('id');

        $this->actingAs($this->student)->getJson("/api/submissions/$id")
            ->assertOk()
            ->assertJsonPath('status', 'passed')
            ->assertJsonPath('score', 100)
            ->assertJsonPath('passed_count', 2);
    }

    public function test_java_compile_error_fails_with_message(): void
    {
        $id = $this->actingAs($this->student)
            ->postJson("/api/challenges/{$this->challenge->id}/submissions", [
                'code' => self::BROKEN_CODE,
            ])->assertStatus(202)->json('id');

        $response = $this->actingAs($this->student)->getJson("/api/submissions/$id")
            ->assertOk()
            ->assertJsonPath('status', 'failed')
            ->assertJsonPath('passed_count', 0);

        $this->assertNotEmpty($response->json('judge_output.cases.0.error'));
    }
}

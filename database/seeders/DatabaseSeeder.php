<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $teacher = User::factory()->create([
            'first_name' => 'Docente',
            'last_name' => 'Demo',
            'ci' => '12345672',
            'email' => 'teacher@courses.test',
            'password' => 'Password1!',
            'role' => 'teacher',
        ]);

        User::factory()->create([
            'first_name' => 'Ana',
            'last_name' => 'Estudiante',
            'email' => 'ana@courses.test',
            'password' => 'Password1!',
        ]);

        User::factory()->create([
            'first_name' => 'Bruno',
            'last_name' => 'Estudiante',
            'email' => 'bruno@courses.test',
            'password' => 'Password1!',
        ]);

        $course = Course::create([
            'title' => 'Programación 101',
            'description' => 'Curso introductorio estilo AdventJS: lecciones + challenges.',
            'teacher_id' => $teacher->id,
        ]);

        $lesson = $course->lessons()->create([
            'title' => 'Entrada y salida estándar',
            'content' => "# Entrada y salida\n\nTus soluciones leen de **stdin** y escriben a **stdout** con `console.log`.\n\nEjemplo:\n```js\nconst [a, b] = stdin.split(' ').map(Number);\nconsole.log(a + b);\n```",
            'position' => 1,
        ]);

        $suma = $course->challenges()->create([
            'lesson_id' => $lesson->id,
            'title' => 'Suma dos números',
            'statement' => "Leé dos enteros separados por espacio desde `stdin` e imprimí su suma con `console.log`.\n\n**Entrada:** `2 3`\n**Salida:** `5`",
            'starter_code' => "const [a, b] = stdin.split(' ').map(Number);\nconsole.log(a + b);\n",
            'points' => 100,
            'difficulty' => 'easy',
            'position' => 1,
            'published' => true,
        ]);

        $suma->testCases()->createMany([
            ['stdin' => '2 3', 'expected_output' => '5', 'is_hidden' => false],
            ['stdin' => '10 20', 'expected_output' => '30', 'is_hidden' => false],
            ['stdin' => '-5 5', 'expected_output' => '0', 'is_hidden' => true],
            ['stdin' => '999999 1', 'expected_output' => '1000000', 'is_hidden' => true],
        ]);

        $reverso = $course->challenges()->create([
            'lesson_id' => $lesson->id,
            'title' => 'Texto al revés',
            'statement' => "Leé una línea desde `stdin` e imprimila invertida.\n\n**Entrada:** `hola`\n**Salida:** `aloh`",
            'starter_code' => "console.log(stdin.split('').reverse().join(''));\n",
            'points' => 150,
            'difficulty' => 'medium',
            'position' => 2,
            'published' => true,
        ]);

        $reverso->testCases()->createMany([
            ['stdin' => 'hola', 'expected_output' => 'aloh', 'is_hidden' => false],
            ['stdin' => 'AdventJS', 'expected_output' => 'SJtnevdA', 'is_hidden' => true],
        ]);

        $this->call(JavaOopCoursesSeeder::class);
    }
}

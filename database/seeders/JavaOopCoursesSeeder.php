<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Seeder;

// ponytail: contenido de la Unidad 1 (POO en Java) de la planificacion 2026,
// un curso por criterio de logro (CL1.1-CL1.4). Convencion de cada challenge:
// una unica clase `public class Main` DECLARADA PRIMERO en el archivo (Piston
// corre la primera clase que encuentra en el .java, no necesariamente la
// public) que lee con Scanner y escribe con System.out.println. Los strings
// impresos son ASCII puro: el contenedor de Piston no soporta UTF-8 en stdout
// (tildes/enies se corrompen a "??"). Los enunciados (markdown, no se
// ejecutan) si usan español correcto.
class JavaOopCoursesSeeder extends Seeder
{
    public function run(): void
    {
        $teacher = User::firstOrCreate(
            ['email' => 'teacher@courses.test'],
            ['first_name' => 'Docente', 'last_name' => 'Demo', 'ci' => '12345672', 'password' => 'Password1!', 'role' => 'teacher']
        );

        foreach ($this->courses() as $courseData) {
            $course = Course::create([
                'title' => $courseData['title'],
                'description' => $courseData['description'],
                'teacher_id' => $teacher->id,
            ]);

            $lesson = $course->lessons()->create([
                'title' => $courseData['lesson_title'],
                'content' => $courseData['lesson_content'],
                'position' => 1,
            ]);

            foreach ($courseData['challenges'] as $i => $c) {
                $challenge = $course->challenges()->create([
                    'lesson_id' => $lesson->id,
                    'title' => $c['title'],
                    'statement' => $c['statement'],
                    'starter_code' => $c['starter_code'],
                    'points' => $c['points'],
                    'difficulty' => $c['difficulty'],
                    'position' => $i + 1,
                    'published' => true,
                    'language' => 'java',
                ]);

                $challenge->testCases()->createMany($c['tests']);
            }
        }
    }

    private function courses(): array
    {
        return [
            $this->cl11(),
            $this->cl12(),
            $this->cl13(),
            $this->cl14(),
        ];
    }

    private function javaIntro(string $concepto): string
    {
        return "Tu solucion es una unica clase `public class Main` con `main(String[] args)`, ".
            "declarada **primero** en el archivo. Leé la entrada con `Scanner` (System.in) e imprimí ".
            "el resultado con `System.out.println`. Podés declarar clases auxiliares en el mismo archivo, ".
            "**despues** de `Main`.\n\n Esta unidad trabaja **$concepto**.";
    }

    private function cl11(): array
    {
        return [
            'title' => 'POO en Java I — Crea clases',
            'description' => 'CL1.1: definir clases con atributos, constructores e instanciar objetos.',
            'lesson_title' => 'Clases, atributos y constructores',
            'lesson_content' => $this->javaIntro('la creacion de clases: atributos, constructores e instanciacion de objetos').
                "\n\n```java\npublic class Main {\n    public static void main(String[] args) {\n        Punto p = new Punto(3, 4);\n    }\n}\n\nclass Punto {\n    int x, y;\n    Punto(int x, int y) { this.x = x; this.y = y; }\n}\n```",
            'challenges' => [
                [
                    'title' => 'Clase Punto',
                    'difficulty' => 'easy', 'points' => 100,
                    'statement' => "Creá una clase `Punto` con atributos enteros `x` e `y`, y un constructor que los reciba. ".
                        "En `Main`, leé dos enteros separados por espacio e imprimí las coordenadas del punto como `x,y`.\n\n".
                        '**Entrada:** `3 4`'."\n".'**Salida:** `3,4`',
                    'starter_code' => "import java.util.Scanner;\n\npublic class Main {\n    public static void main(String[] args) {\n        Scanner sc = new Scanner(System.in);\n        int x = sc.nextInt();\n        int y = sc.nextInt();\n\n        // TODO: crear un Punto e imprimir \"x,y\"\n    }\n}\n\nclass Punto {\n    // TODO: atributos x, y y constructor Punto(int x, int y)\n}\n",
                    'tests' => [
                        ['stdin' => '3 4', 'expected_output' => '3,4', 'is_hidden' => false],
                        ['stdin' => '0 0', 'expected_output' => '0,0', 'is_hidden' => false],
                        ['stdin' => '-2 5', 'expected_output' => '-2,5', 'is_hidden' => true],
                        ['stdin' => '100 -100', 'expected_output' => '100,-100', 'is_hidden' => true],
                    ],
                ],
                [
                    'title' => 'Clase Persona: saludo',
                    'difficulty' => 'easy', 'points' => 100,
                    'statement' => "Creá una clase `Persona` con atributos `String nombre` e `int edad`, un constructor, y un metodo ".
                        "`saludar()` que retorne el texto `Hola, soy NOMBRE y tengo EDAD anios.`. Leé el nombre (una palabra, sin espacios) ".
                        "y la edad, e imprimí el resultado de `saludar()`.\n\n".
                        '**Entrada:** `Ana 20`'."\n".'**Salida:** `Hola, soy Ana y tengo 20 anios.`',
                    'starter_code' => "import java.util.Scanner;\n\npublic class Main {\n    public static void main(String[] args) {\n        Scanner sc = new Scanner(System.in);\n        String nombre = sc.next();\n        int edad = sc.nextInt();\n\n        // TODO: crear la Persona e imprimir persona.saludar()\n    }\n}\n\nclass Persona {\n    // TODO: atributos nombre, edad, constructor y metodo saludar()\n}\n",
                    'tests' => [
                        ['stdin' => 'Ana 20', 'expected_output' => 'Hola, soy Ana y tengo 20 anios.', 'is_hidden' => false],
                        ['stdin' => 'Bruno 17', 'expected_output' => 'Hola, soy Bruno y tengo 17 anios.', 'is_hidden' => false],
                        ['stdin' => 'Zoe 5', 'expected_output' => 'Hola, soy Zoe y tengo 5 anios.', 'is_hidden' => true],
                    ],
                ],
                [
                    'title' => 'Clase Rectangulo: area',
                    'difficulty' => 'easy', 'points' => 100,
                    'statement' => "Creá una clase `Rectangulo` con atributos enteros `base` y `altura`, un constructor, y un metodo ".
                        "`area()` que retorne `base * altura`. Leé base y altura e imprimí el area.\n\n".
                        '**Entrada:** `4 5`'."\n".'**Salida:** `20`',
                    'starter_code' => "import java.util.Scanner;\n\npublic class Main {\n    public static void main(String[] args) {\n        Scanner sc = new Scanner(System.in);\n        int base = sc.nextInt();\n        int altura = sc.nextInt();\n\n        // TODO: crear el Rectangulo e imprimir area()\n    }\n}\n\nclass Rectangulo {\n    // TODO: atributos base, altura, constructor y metodo area()\n}\n",
                    'tests' => [
                        ['stdin' => '4 5', 'expected_output' => '20', 'is_hidden' => false],
                        ['stdin' => '10 10', 'expected_output' => '100', 'is_hidden' => false],
                        ['stdin' => '1 100', 'expected_output' => '100', 'is_hidden' => true],
                        ['stdin' => '7 3', 'expected_output' => '21', 'is_hidden' => true],
                    ],
                ],
                [
                    'title' => 'Clase Circulo: area formateada',
                    'difficulty' => 'medium', 'points' => 150,
                    'statement' => "Creá una clase `Circulo` con atributo `double radio`, un constructor, y un metodo `area()` que ".
                        "retorne `Math.PI * radio * radio`. Leé el radio (entero) e imprimí el area con **2 decimales** ".
                        "usando `String.format(\"%.2f\", area)`.\n\n".
                        '**Entrada:** `5`'."\n".'**Salida:** `78.54`',
                    'starter_code' => "import java.util.Scanner;\n\npublic class Main {\n    public static void main(String[] args) {\n        Scanner sc = new Scanner(System.in);\n        double radio = sc.nextInt();\n\n        // TODO: crear el Circulo e imprimir area() con 2 decimales\n    }\n}\n\nclass Circulo {\n    // TODO: atributo radio, constructor y metodo area()\n}\n",
                    'tests' => [
                        ['stdin' => '5', 'expected_output' => '78.54', 'is_hidden' => false],
                        ['stdin' => '1', 'expected_output' => '3.14', 'is_hidden' => false],
                        ['stdin' => '10', 'expected_output' => '314.16', 'is_hidden' => true],
                        ['stdin' => '2', 'expected_output' => '12.57', 'is_hidden' => true],
                    ],
                ],
                [
                    'title' => 'Clase Producto: total',
                    'difficulty' => 'easy', 'points' => 100,
                    'statement' => "Creá una clase `Producto` con `String nombre`, `int precio` y `int cantidad`, un constructor, y un ".
                        "metodo `total()` que retorne `precio * cantidad`. Leé nombre, precio y cantidad, e imprimí ".
                        "`nombre: total`.\n\n".
                        '**Entrada:** `Lapiz 100 3`'."\n".'**Salida:** `Lapiz: 300`',
                    'starter_code' => "import java.util.Scanner;\n\npublic class Main {\n    public static void main(String[] args) {\n        Scanner sc = new Scanner(System.in);\n        String nombre = sc.next();\n        int precio = sc.nextInt();\n        int cantidad = sc.nextInt();\n\n        // TODO: crear el Producto e imprimir \"nombre: total\"\n    }\n}\n\nclass Producto {\n    // TODO: atributos nombre, precio, cantidad, constructor y metodo total()\n}\n",
                    'tests' => [
                        ['stdin' => 'Lapiz 100 3', 'expected_output' => 'Lapiz: 300', 'is_hidden' => false],
                        ['stdin' => 'Cuaderno 250 4', 'expected_output' => 'Cuaderno: 1000', 'is_hidden' => false],
                        ['stdin' => 'Goma 50 10', 'expected_output' => 'Goma: 500', 'is_hidden' => true],
                    ],
                ],
                [
                    'title' => 'Clase CuentaBancaria: saldo inicial',
                    'difficulty' => 'medium', 'points' => 150,
                    'statement' => "Creá una clase `CuentaBancaria` con `String titular` e `int saldo`, un constructor, y un metodo ".
                        "`mostrarSaldo()` que retorne `titular tiene \$saldo`. Leé titular y saldo, e imprimí `mostrarSaldo()`.\n\n".
                        '**Entrada:** `Ana 5000`'."\n".'**Salida:** `Ana tiene $5000`',
                    'starter_code' => "import java.util.Scanner;\n\npublic class Main {\n    public static void main(String[] args) {\n        Scanner sc = new Scanner(System.in);\n        String titular = sc.next();\n        int saldo = sc.nextInt();\n\n        // TODO: crear la CuentaBancaria e imprimir mostrarSaldo()\n    }\n}\n\nclass CuentaBancaria {\n    // TODO: atributos titular, saldo, constructor y metodo mostrarSaldo()\n}\n",
                    'tests' => [
                        ['stdin' => 'Ana 5000', 'expected_output' => 'Ana tiene $5000', 'is_hidden' => false],
                        ['stdin' => 'Bruno 0', 'expected_output' => 'Bruno tiene $0', 'is_hidden' => false],
                        ['stdin' => 'Leo 123456', 'expected_output' => 'Leo tiene $123456', 'is_hidden' => true],
                    ],
                ],
                [
                    'title' => 'Clase Libro: ficha',
                    'difficulty' => 'medium', 'points' => 150,
                    'statement' => "Creá una clase `Libro` con `String titulo`, `String autor` e `int paginas`, un constructor, y un metodo ".
                        "`ficha()` que retorne `titulo - autor (paginas pgs)`. Leé titulo, autor y paginas (todo tokens sin ".
                        "espacios) e imprimí `ficha()`.\n\n".
                        '**Entrada:** `ElHobbit Tolkien 310`'."\n".'**Salida:** `ElHobbit - Tolkien (310 pgs)`',
                    'starter_code' => "import java.util.Scanner;\n\npublic class Main {\n    public static void main(String[] args) {\n        Scanner sc = new Scanner(System.in);\n        String titulo = sc.next();\n        String autor = sc.next();\n        int paginas = sc.nextInt();\n\n        // TODO: crear el Libro e imprimir ficha()\n    }\n}\n\nclass Libro {\n    // TODO: atributos titulo, autor, paginas, constructor y metodo ficha()\n}\n",
                    'tests' => [
                        ['stdin' => 'ElHobbit Tolkien 310', 'expected_output' => 'ElHobbit - Tolkien (310 pgs)', 'is_hidden' => false],
                        ['stdin' => 'Dune Herbert 412', 'expected_output' => 'Dune - Herbert (412 pgs)', 'is_hidden' => false],
                        ['stdin' => 'It King 1138', 'expected_output' => 'It - King (1138 pgs)', 'is_hidden' => true],
                    ],
                ],
                [
                    'title' => 'Clase Estudiante: promedio',
                    'difficulty' => 'medium', 'points' => 150,
                    'statement' => "Creá una clase `Estudiante` con `String nombre` y tres notas enteras, un constructor, y un metodo ".
                        "`promedio()` (double) que retorne el promedio de las tres notas. Leé nombre y las tres notas, e ".
                        "imprimí el promedio con **2 decimales**.\n\n".
                        '**Entrada:** `Ana 8 7 9`'."\n".'**Salida:** `8.00`',
                    'starter_code' => "import java.util.Scanner;\n\npublic class Main {\n    public static void main(String[] args) {\n        Scanner sc = new Scanner(System.in);\n        String nombre = sc.next();\n        int n1 = sc.nextInt();\n        int n2 = sc.nextInt();\n        int n3 = sc.nextInt();\n\n        // TODO: crear el Estudiante e imprimir promedio() con 2 decimales\n    }\n}\n\nclass Estudiante {\n    // TODO: atributos nombre, notas, constructor y metodo promedio()\n}\n",
                    'tests' => [
                        ['stdin' => 'Ana 8 7 9', 'expected_output' => '8.00', 'is_hidden' => false],
                        ['stdin' => 'Bruno 6 7 8', 'expected_output' => '7.00', 'is_hidden' => false],
                        ['stdin' => 'Leo 10 9 8', 'expected_output' => '9.00', 'is_hidden' => true],
                        ['stdin' => 'Mia 5 6 7', 'expected_output' => '6.00', 'is_hidden' => true],
                    ],
                ],
                [
                    'title' => 'Clase Fecha: es bisiesto',
                    'difficulty' => 'hard', 'points' => 200,
                    'statement' => "Creá una clase `Fecha` con `int dia`, `int mes` e `int anio`, un constructor, y un metodo ".
                        "`esBisiesto()` (boolean) que aplique la regla: divisible entre 4 y no entre 100, o divisible ".
                        "entre 400. Leé dia, mes y anio, e imprimí el resultado de `esBisiesto()`.\n\n".
                        '**Entrada:** `1 1 2000`'."\n".'**Salida:** `true`',
                    'starter_code' => "import java.util.Scanner;\n\npublic class Main {\n    public static void main(String[] args) {\n        Scanner sc = new Scanner(System.in);\n        int dia = sc.nextInt();\n        int mes = sc.nextInt();\n        int anio = sc.nextInt();\n\n        // TODO: crear la Fecha e imprimir esBisiesto()\n    }\n}\n\nclass Fecha {\n    // TODO: atributos dia, mes, anio, constructor y metodo esBisiesto()\n}\n",
                    'tests' => [
                        ['stdin' => '1 1 2000', 'expected_output' => 'true', 'is_hidden' => false],
                        ['stdin' => '1 1 1900', 'expected_output' => 'false', 'is_hidden' => false],
                        ['stdin' => '1 1 2024', 'expected_output' => 'true', 'is_hidden' => true],
                        ['stdin' => '1 1 2023', 'expected_output' => 'false', 'is_hidden' => true],
                    ],
                ],
                [
                    'title' => 'Clase Auto: antiguedad',
                    'difficulty' => 'hard', 'points' => 200,
                    'statement' => "Creá una clase `Auto` con `String marca`, `String modelo` e `int anioFabricacion`, un constructor, y ".
                        "un metodo `antiguedad(int anioActual)` que retorne `anioActual - anioFabricacion`. Leé marca, ".
                        "modelo, anioFabricacion y anioActual, e imprimí `marca modelo tiene X anios`.\n\n".
                        '**Entrada:** `Toyota Corolla 2010 2026`'."\n".'**Salida:** `Toyota Corolla tiene 16 anios`',
                    'starter_code' => "import java.util.Scanner;\n\npublic class Main {\n    public static void main(String[] args) {\n        Scanner sc = new Scanner(System.in);\n        String marca = sc.next();\n        String modelo = sc.next();\n        int anioFabricacion = sc.nextInt();\n        int anioActual = sc.nextInt();\n\n        // TODO: crear el Auto e imprimir \"marca modelo tiene X anios\"\n    }\n}\n\nclass Auto {\n    // TODO: atributos marca, modelo, anioFabricacion, constructor y metodo antiguedad(int anioActual)\n}\n",
                    'tests' => [
                        ['stdin' => 'Toyota Corolla 2010 2026', 'expected_output' => 'Toyota Corolla tiene 16 anios', 'is_hidden' => false],
                        ['stdin' => 'Ford Fiesta 2020 2026', 'expected_output' => 'Ford Fiesta tiene 6 anios', 'is_hidden' => false],
                        ['stdin' => 'Fiat Uno 1995 2026', 'expected_output' => 'Fiat Uno tiene 31 anios', 'is_hidden' => true],
                    ],
                ],
            ],
        ];
    }

    private function cl12(): array
    {
        return [
            'title' => 'POO en Java II — Atributos y metodos',
            'description' => 'CL1.2: metodos que operan sobre el estado del objeto y se combinan entre si.',
            'lesson_title' => 'Metodos que leen y modifican el estado',
            'lesson_content' => $this->javaIntro('atributos y metodos: como los metodos leen y modifican el estado de un objeto').
                "\n\n```java\nclass Contador {\n    int valor;\n    void incrementar() { valor = valor + 1; }\n}\n```".
                "\n\nUn metodo puede devolver algo calculado a partir de los atributos, o modificar el estado del objeto (sin retornar nada, `void`).",
            'challenges' => [
                [
                    'title' => 'Rectangulo: area y perimetro',
                    'difficulty' => 'easy', 'points' => 100,
                    'statement' => "Clase `Rectangulo` con `int base` y `int altura`. Metodos `area()` y `perimetro()` ".
                        "(`2 * (base + altura)`). Leé base y altura, e imprimí `area:X perimetro:Y`.\n\n".
                        '**Entrada:** `4 5`'."\n".'**Salida:** `area:20 perimetro:18`',
                    'starter_code' => "import java.util.Scanner;\n\npublic class Main {\n    public static void main(String[] args) {\n        Scanner sc = new Scanner(System.in);\n        int base = sc.nextInt();\n        int altura = sc.nextInt();\n\n        // TODO: imprimir \"area:X perimetro:Y\"\n    }\n}\n\nclass Rectangulo {\n    // TODO: atributos, constructor, area() y perimetro()\n}\n",
                    'tests' => [
                        ['stdin' => '4 5', 'expected_output' => 'area:20 perimetro:18', 'is_hidden' => false],
                        ['stdin' => '10 10', 'expected_output' => 'area:100 perimetro:40', 'is_hidden' => false],
                        ['stdin' => '1 7', 'expected_output' => 'area:7 perimetro:16', 'is_hidden' => true],
                    ],
                ],
                [
                    'title' => 'Circulo: area y perimetro formateados',
                    'difficulty' => 'medium', 'points' => 150,
                    'statement' => "Clase `Circulo` con `double radio`. Metodos `area()` (`Math.PI * radio * radio`) y ".
                        "`perimetro()` (`2 * Math.PI * radio`). Leé el radio e imprimí ".
                        "`area:X.XX perimetro:Y.YY` con `String.format(\"area:%.2f perimetro:%.2f\", area, perimetro)`.\n\n".
                        '**Entrada:** `5`'."\n".'**Salida:** `area:78.54 perimetro:31.42`',
                    'starter_code' => "import java.util.Scanner;\n\npublic class Main {\n    public static void main(String[] args) {\n        Scanner sc = new Scanner(System.in);\n        double radio = sc.nextInt();\n\n        // TODO: imprimir \"area:X.XX perimetro:Y.YY\"\n    }\n}\n\nclass Circulo {\n    // TODO: atributo radio, constructor, area() y perimetro()\n}\n",
                    'tests' => [
                        ['stdin' => '5', 'expected_output' => 'area:78.54 perimetro:31.42', 'is_hidden' => false],
                        ['stdin' => '1', 'expected_output' => 'area:3.14 perimetro:6.28', 'is_hidden' => false],
                        ['stdin' => '10', 'expected_output' => 'area:314.16 perimetro:62.83', 'is_hidden' => true],
                    ],
                ],
                [
                    'title' => 'Persona: cumplir anios',
                    'difficulty' => 'easy', 'points' => 100,
                    'statement' => "Clase `Persona` con `String nombre` e `int edad`. Metodo `void cumplirAnios()` que ".
                        "incrementa `edad` en 1. Leé nombre y edad, llamá `cumplirAnios()`, e imprimí ".
                        "`nombre ahora tiene X anios`.\n\n".
                        '**Entrada:** `Ana 20`'."\n".'**Salida:** `Ana ahora tiene 21 anios`',
                    'starter_code' => "import java.util.Scanner;\n\npublic class Main {\n    public static void main(String[] args) {\n        Scanner sc = new Scanner(System.in);\n        String nombre = sc.next();\n        int edad = sc.nextInt();\n\n        // TODO: crear la Persona, llamar cumplirAnios() e imprimir el resultado\n    }\n}\n\nclass Persona {\n    // TODO: atributos, constructor, metodo void cumplirAnios() y una forma de leer la edad actual\n}\n",
                    'tests' => [
                        ['stdin' => 'Ana 20', 'expected_output' => 'Ana ahora tiene 21 anios', 'is_hidden' => false],
                        ['stdin' => 'Bruno 17', 'expected_output' => 'Bruno ahora tiene 18 anios', 'is_hidden' => false],
                        ['stdin' => 'Zoe 0', 'expected_output' => 'Zoe ahora tiene 1 anios', 'is_hidden' => true],
                    ],
                ],
                [
                    'title' => 'Temperatura: conversion',
                    'difficulty' => 'easy', 'points' => 100,
                    'statement' => "Clase `Temperatura` con `double celsius`. Metodo `aFahrenheit()` (`celsius * 9 / 5 + 32`). ".
                        "Leé la temperatura en Celsius e imprimí el resultado con **2 decimales**.\n\n".
                        '**Entrada:** `100`'."\n".'**Salida:** `212.00`',
                    'starter_code' => "import java.util.Scanner;\n\npublic class Main {\n    public static void main(String[] args) {\n        Scanner sc = new Scanner(System.in);\n        double celsius = sc.nextInt();\n\n        // TODO: crear la Temperatura e imprimir aFahrenheit() con 2 decimales\n    }\n}\n\nclass Temperatura {\n    // TODO: atributo celsius, constructor y metodo aFahrenheit()\n}\n",
                    'tests' => [
                        ['stdin' => '100', 'expected_output' => '212.00', 'is_hidden' => false],
                        ['stdin' => '0', 'expected_output' => '32.00', 'is_hidden' => false],
                        ['stdin' => '37', 'expected_output' => '98.60', 'is_hidden' => true],
                        ['stdin' => '-40', 'expected_output' => '-40.00', 'is_hidden' => true],
                    ],
                ],
                [
                    'title' => 'Empleado: sueldo con bono',
                    'difficulty' => 'medium', 'points' => 150,
                    'statement' => "Clase `Empleado` con `String nombre` e `int sueldoBase`. Metodo `calcularSueldoConBono(int bono)` ".
                        "que retorne `sueldoBase + bono`. Leé nombre, sueldoBase y bono, e imprimí `nombre cobra \$total`.\n\n".
                        '**Entrada:** `Ana 30000 5000`'."\n".'**Salida:** `Ana cobra $35000`',
                    'starter_code' => "import java.util.Scanner;\n\npublic class Main {\n    public static void main(String[] args) {\n        Scanner sc = new Scanner(System.in);\n        String nombre = sc.next();\n        int sueldoBase = sc.nextInt();\n        int bono = sc.nextInt();\n\n        // TODO: crear el Empleado e imprimir \"nombre cobra \$total\"\n    }\n}\n\nclass Empleado {\n    // TODO: atributos, constructor y metodo calcularSueldoConBono(int bono)\n}\n",
                    'tests' => [
                        ['stdin' => 'Ana 30000 5000', 'expected_output' => 'Ana cobra $35000', 'is_hidden' => false],
                        ['stdin' => 'Bruno 20000 0', 'expected_output' => 'Bruno cobra $20000', 'is_hidden' => false],
                        ['stdin' => 'Leo 50000 10000', 'expected_output' => 'Leo cobra $60000', 'is_hidden' => true],
                    ],
                ],
                [
                    'title' => 'Caja: volumen y cubo',
                    'difficulty' => 'medium', 'points' => 150,
                    'statement' => "Clase `Caja` con `int ancho`, `int alto` e `int profundo`. Metodo `volumen()` (`ancho * alto * profundo`) ".
                        "y metodo `esCubo()` (boolean: los tres lados son iguales). Leé los tres lados e imprimí ".
                        "`volumen:X esCubo:true/false`.\n\n".
                        '**Entrada:** `2 2 2`'."\n".'**Salida:** `volumen:8 esCubo:true`',
                    'starter_code' => "import java.util.Scanner;\n\npublic class Main {\n    public static void main(String[] args) {\n        Scanner sc = new Scanner(System.in);\n        int ancho = sc.nextInt();\n        int alto = sc.nextInt();\n        int profundo = sc.nextInt();\n\n        // TODO: crear la Caja e imprimir \"volumen:X esCubo:true/false\"\n    }\n}\n\nclass Caja {\n    // TODO: atributos, constructor, volumen() y esCubo()\n}\n",
                    'tests' => [
                        ['stdin' => '2 2 2', 'expected_output' => 'volumen:8 esCubo:true', 'is_hidden' => false],
                        ['stdin' => '2 3 4', 'expected_output' => 'volumen:24 esCubo:false', 'is_hidden' => false],
                        ['stdin' => '5 5 5', 'expected_output' => 'volumen:125 esCubo:true', 'is_hidden' => true],
                    ],
                ],
                [
                    'title' => 'Vector2D: suma',
                    'difficulty' => 'hard', 'points' => 200,
                    'statement' => "Clase `Vector2D` con `int x` e `int y`. Metodo `sumar(Vector2D otro)` que retorne un ".
                        "**nuevo** `Vector2D` con la suma de las componentes. Leé x1 y1 x2 y2, sumá los vectores, e ".
                        "imprimí el resultado como `x,y`.\n\n".
                        '**Entrada:** `1 2 3 4`'."\n".'**Salida:** `4,6`',
                    'starter_code' => "import java.util.Scanner;\n\npublic class Main {\n    public static void main(String[] args) {\n        Scanner sc = new Scanner(System.in);\n        int x1 = sc.nextInt();\n        int y1 = sc.nextInt();\n        int x2 = sc.nextInt();\n        int y2 = sc.nextInt();\n\n        // TODO: crear v1, v2, sumarlos e imprimir \"x,y\" del resultado\n    }\n}\n\nclass Vector2D {\n    // TODO: atributos x, y, constructor y metodo sumar(Vector2D otro) que retorne Vector2D\n}\n",
                    'tests' => [
                        ['stdin' => '1 2 3 4', 'expected_output' => '4,6', 'is_hidden' => false],
                        ['stdin' => '0 0 0 0', 'expected_output' => '0,0', 'is_hidden' => false],
                        ['stdin' => '-1 5 3 -2', 'expected_output' => '2,3', 'is_hidden' => true],
                    ],
                ],
                [
                    'title' => 'Carrito: descuento',
                    'difficulty' => 'hard', 'points' => 200,
                    'statement' => "Clase `Carrito` con `double precio` e `int cantidad`. Metodo `total()` (`precio * cantidad`) y ".
                        "metodo `void aplicarDescuento(double porcentaje)` que **modifica** `precio` restandole el ".
                        "porcentaje indicado. Leé precio, cantidad y porcentaje; aplicá el descuento y luego imprimí ".
                        "`total()` con **2 decimales**.\n\n".
                        '**Entrada:** `100 2 10`'."\n".'**Salida:** `180.00`',
                    'starter_code' => "import java.util.Scanner;\n\npublic class Main {\n    public static void main(String[] args) {\n        Scanner sc = new Scanner(System.in);\n        double precio = sc.nextInt();\n        int cantidad = sc.nextInt();\n        double porcentaje = sc.nextInt();\n\n        // TODO: crear el Carrito, aplicarDescuento(porcentaje) e imprimir total() con 2 decimales\n    }\n}\n\nclass Carrito {\n    // TODO: atributos, constructor, total() y void aplicarDescuento(double porcentaje)\n}\n",
                    'tests' => [
                        ['stdin' => '100 2 10', 'expected_output' => '180.00', 'is_hidden' => false],
                        ['stdin' => '50 1 0', 'expected_output' => '50.00', 'is_hidden' => false],
                        ['stdin' => '200 3 50', 'expected_output' => '300.00', 'is_hidden' => true],
                    ],
                ],
                [
                    'title' => 'Reloj: avanzar minutos',
                    'difficulty' => 'hard', 'points' => 200,
                    'statement' => "Clase `Reloj` con `int horas` y `int minutos`. Metodo `void avanzarMinutos(int n)` que suma ".
                        "`n` a `minutos`, llevando el excedente a `horas` (y `horas` da la vuelta cada 24). Leé horas, ".
                        "minutos y n; avanzá el reloj; e imprimí `HH:MM` con `String.format(\"%02d:%02d\", horas, minutos)`.\n\n".
                        '**Entrada:** `23 50 20`'."\n".'**Salida:** `00:10`',
                    'starter_code' => "import java.util.Scanner;\n\npublic class Main {\n    public static void main(String[] args) {\n        Scanner sc = new Scanner(System.in);\n        int horas = sc.nextInt();\n        int minutos = sc.nextInt();\n        int n = sc.nextInt();\n\n        // TODO: crear el Reloj, avanzarMinutos(n) e imprimir \"HH:MM\"\n    }\n}\n\nclass Reloj {\n    // TODO: atributos horas, minutos, constructor y metodo void avanzarMinutos(int n)\n}\n",
                    'tests' => [
                        ['stdin' => '23 50 20', 'expected_output' => '00:10', 'is_hidden' => false],
                        ['stdin' => '10 30 15', 'expected_output' => '10:45', 'is_hidden' => false],
                        ['stdin' => '0 0 0', 'expected_output' => '00:00', 'is_hidden' => true],
                        ['stdin' => '23 59 1', 'expected_output' => '00:00', 'is_hidden' => true],
                    ],
                ],
                [
                    'title' => 'Piscina: tiempo de llenado',
                    'difficulty' => 'hard', 'points' => 200,
                    'statement' => "Clase `Piscina` con `double largo`, `double ancho` y `double profundidad` (en metros). Metodo ".
                        "`capacidadLitros()` (`largo * ancho * profundidad * 1000`) y metodo ".
                        "`tiempoLlenado(double litrosPorMinuto)` (`capacidadLitros() / litrosPorMinuto`). Leé largo, ancho, ".
                        "profundidad y litrosPorMinuto, e imprimí el tiempo de llenado con **2 decimales**.\n\n".
                        '**Entrada:** `2 3 1 500`'."\n".'**Salida:** `12.00`',
                    'starter_code' => "import java.util.Scanner;\n\npublic class Main {\n    public static void main(String[] args) {\n        Scanner sc = new Scanner(System.in);\n        double largo = sc.nextInt();\n        double ancho = sc.nextInt();\n        double profundidad = sc.nextInt();\n        double litrosPorMinuto = sc.nextInt();\n\n        // TODO: crear la Piscina e imprimir tiempoLlenado(litrosPorMinuto) con 2 decimales\n    }\n}\n\nclass Piscina {\n    // TODO: atributos, constructor, capacidadLitros() y tiempoLlenado(double litrosPorMinuto)\n}\n",
                    'tests' => [
                        ['stdin' => '2 3 1 500', 'expected_output' => '12.00', 'is_hidden' => false],
                        ['stdin' => '5 2 2 1000', 'expected_output' => '20.00', 'is_hidden' => false],
                        ['stdin' => '1 1 1 200', 'expected_output' => '5.00', 'is_hidden' => true],
                    ],
                ],
            ],
        ];
    }

    private function cl13(): array
    {
        return [
            'title' => 'POO en Java III — Encapsulamiento',
            'description' => 'CL1.3: atributos privados, acceso controlado y validacion en setters.',
            'lesson_title' => 'Atributos privados y acceso controlado',
            'lesson_content' => $this->javaIntro('encapsulamiento: atributos `private`, acceso via metodos publicos, y validacion antes de modificar el estado').
                "\n\n```java\nclass Cuenta {\n    private int saldo;\n    void setSaldo(int s) { if (s >= 0) saldo = s; }\n    int getSaldo() { return saldo; }\n}\n```".
                "\n\nUn setter puede **rechazar** un valor invalido (dejando el estado sin cambios) o **ajustarlo** (clamp) a un rango valido.",
            'challenges' => [
                [
                    'title' => 'CuentaBancaria: saldo no negativo',
                    'difficulty' => 'easy', 'points' => 100,
                    'statement' => "Clase `CuentaBancaria` con `private int saldo`. Constructor recibe el saldo inicial. ".
                        "`setSaldo(int nuevo)`: si `nuevo < 0`, no cambia nada; si no, actualiza. `getSaldo()` retorna el saldo. ".
                        "Leé el saldo inicial y el nuevo saldo a intentar, e imprimí `getSaldo()` despues de llamar `setSaldo`.\n\n".
                        '**Entrada:** `1000 -500`'."\n".'**Salida:** `1000`',
                    'starter_code' => "import java.util.Scanner;\n\npublic class Main {\n    public static void main(String[] args) {\n        Scanner sc = new Scanner(System.in);\n        int inicial = sc.nextInt();\n        int nuevo = sc.nextInt();\n\n        // TODO: crear la cuenta, llamar setSaldo(nuevo) e imprimir getSaldo()\n    }\n}\n\nclass CuentaBancaria {\n    // TODO: private int saldo, constructor, setSaldo(int) que rechaza negativos y getSaldo()\n}\n",
                    'tests' => [
                        ['stdin' => '1000 -500', 'expected_output' => '1000', 'is_hidden' => false],
                        ['stdin' => '1000 2000', 'expected_output' => '2000', 'is_hidden' => false],
                        ['stdin' => '500 0', 'expected_output' => '0', 'is_hidden' => true],
                    ],
                ],
                [
                    'title' => 'Persona: edad valida',
                    'difficulty' => 'easy', 'points' => 100,
                    'statement' => "Clase `Persona` con `private int edad`. Constructor recibe la edad inicial. `setEdad(int e)`: si ".
                        "`e < 0 || e > 120`, no cambia nada; si no, actualiza. `getEdad()` retorna la edad. Leé la edad inicial ".
                        "y la edad a intentar, e imprimí `getEdad()` despues de llamar `setEdad`.\n\n".
                        '**Entrada:** `20 25`'."\n".'**Salida:** `25`',
                    'starter_code' => "import java.util.Scanner;\n\npublic class Main {\n    public static void main(String[] args) {\n        Scanner sc = new Scanner(System.in);\n        int inicial = sc.nextInt();\n        int nueva = sc.nextInt();\n\n        // TODO: crear la Persona, llamar setEdad(nueva) e imprimir getEdad()\n    }\n}\n\nclass Persona {\n    // TODO: private int edad, constructor, setEdad(int) que rechaza fuera de [0,120] y getEdad()\n}\n",
                    'tests' => [
                        ['stdin' => '20 25', 'expected_output' => '25', 'is_hidden' => false],
                        ['stdin' => '20 -5', 'expected_output' => '20', 'is_hidden' => false],
                        ['stdin' => '20 200', 'expected_output' => '20', 'is_hidden' => true],
                        ['stdin' => '5 0', 'expected_output' => '0', 'is_hidden' => true],
                    ],
                ],
                [
                    'title' => 'Producto: precio no negativo',
                    'difficulty' => 'easy', 'points' => 100,
                    'statement' => "Clase `Producto` con `private int precio`. Constructor recibe el precio inicial. `setPrecio(int p)`: ".
                        "si `p < 0`, no cambia nada; si no, actualiza. `getPrecio()` retorna el precio. Leé el precio inicial ".
                        "y el nuevo precio a intentar, e imprimí `getPrecio()` despues de llamar `setPrecio`.\n\n".
                        '**Entrada:** `100 150`'."\n".'**Salida:** `150`',
                    'starter_code' => "import java.util.Scanner;\n\npublic class Main {\n    public static void main(String[] args) {\n        Scanner sc = new Scanner(System.in);\n        int inicial = sc.nextInt();\n        int nuevo = sc.nextInt();\n\n        // TODO: crear el Producto, llamar setPrecio(nuevo) e imprimir getPrecio()\n    }\n}\n\nclass Producto {\n    // TODO: private int precio, constructor, setPrecio(int) que rechaza negativos y getPrecio()\n}\n",
                    'tests' => [
                        ['stdin' => '100 150', 'expected_output' => '150', 'is_hidden' => false],
                        ['stdin' => '100 -50', 'expected_output' => '100', 'is_hidden' => false],
                        ['stdin' => '200 0', 'expected_output' => '0', 'is_hidden' => true],
                    ],
                ],
                [
                    'title' => 'Termometro: rango seguro',
                    'difficulty' => 'medium', 'points' => 150,
                    'statement' => "Clase `Termometro` con `private int temperatura` (inicial 0). `setTemperatura(int t)` ajusta ".
                        "(clamp) el valor al rango `[-50, 50]`: si `t < -50` guarda `-50`; si `t > 50` guarda `50`; si no, ".
                        "guarda `t`. `getTemperatura()` retorna el valor. Leé la temperatura a intentar e imprimí ".
                        "`getTemperatura()`.\n\n".
                        '**Entrada:** `60`'."\n".'**Salida:** `50`',
                    'starter_code' => "import java.util.Scanner;\n\npublic class Main {\n    public static void main(String[] args) {\n        Scanner sc = new Scanner(System.in);\n        int t = sc.nextInt();\n\n        // TODO: crear el Termometro, llamar setTemperatura(t) e imprimir getTemperatura()\n    }\n}\n\nclass Termometro {\n    // TODO: private int temperatura, constructor (inicial 0), setTemperatura(int) con clamp [-50,50] y getTemperatura()\n}\n",
                    'tests' => [
                        ['stdin' => '60', 'expected_output' => '50', 'is_hidden' => false],
                        ['stdin' => '-100', 'expected_output' => '-50', 'is_hidden' => false],
                        ['stdin' => '25', 'expected_output' => '25', 'is_hidden' => true],
                        ['stdin' => '50', 'expected_output' => '50', 'is_hidden' => true],
                    ],
                ],
                [
                    'title' => 'Password: longitud minima',
                    'difficulty' => 'medium', 'points' => 150,
                    'statement' => "Clase `Password` con `private String clave` (inicial `\"sinClave\"`). `setClave(String c)`: si ".
                        "`c.length() < 8`, no cambia nada; si no, actualiza. `getClave()` retorna la clave. Leé la clave a ".
                        "intentar (una palabra, sin espacios) e imprimí `getClave()`.\n\n".
                        '**Entrada:** `abc123`'."\n".'**Salida:** `sinClave`',
                    'starter_code' => "import java.util.Scanner;\n\npublic class Main {\n    public static void main(String[] args) {\n        Scanner sc = new Scanner(System.in);\n        String candidata = sc.next();\n\n        // TODO: crear el Password, llamar setClave(candidata) e imprimir getClave()\n    }\n}\n\nclass Password {\n    // TODO: private String clave (inicial \"sinClave\"), setClave(String) que exige largo >= 8 y getClave()\n}\n",
                    'tests' => [
                        ['stdin' => 'abc123', 'expected_output' => 'sinClave', 'is_hidden' => false],
                        ['stdin' => 'abcdefgh12', 'expected_output' => 'abcdefgh12', 'is_hidden' => false],
                        ['stdin' => '12345678', 'expected_output' => '12345678', 'is_hidden' => true],
                        ['stdin' => 'short', 'expected_output' => 'sinClave', 'is_hidden' => true],
                    ],
                ],
                [
                    'title' => 'Inventario: retiro seguro',
                    'difficulty' => 'medium', 'points' => 150,
                    'statement' => "Clase `Inventario` con `private int stock`. Constructor recibe el stock inicial. Metodo ".
                        "`retirar(int cantidad)` retorna cuanto se retiro realmente (el minimo entre `cantidad` y el stock ".
                        "disponible) y descuenta esa cantidad del stock. Leé el stock inicial y la cantidad pedida; llamá ".
                        "`retirar`; e imprimí `retirado:X stockRestante:Y`.\n\n".
                        '**Entrada:** `10 5`'."\n".'**Salida:** `retirado:5 stockRestante:5`',
                    'starter_code' => "import java.util.Scanner;\n\npublic class Main {\n    public static void main(String[] args) {\n        Scanner sc = new Scanner(System.in);\n        int stockInicial = sc.nextInt();\n        int pedido = sc.nextInt();\n\n        // TODO: crear el Inventario, retirar(pedido) e imprimir \"retirado:X stockRestante:Y\"\n    }\n}\n\nclass Inventario {\n    // TODO: private int stock, constructor y metodo retirar(int cantidad) que no deja el stock negativo\n}\n",
                    'tests' => [
                        ['stdin' => '10 5', 'expected_output' => 'retirado:5 stockRestante:5', 'is_hidden' => false],
                        ['stdin' => '10 15', 'expected_output' => 'retirado:10 stockRestante:0', 'is_hidden' => false],
                        ['stdin' => '0 5', 'expected_output' => 'retirado:0 stockRestante:0', 'is_hidden' => true],
                        ['stdin' => '20 20', 'expected_output' => 'retirado:20 stockRestante:0', 'is_hidden' => true],
                    ],
                ],
                [
                    'title' => 'CuentaBancaria: depositar y retirar',
                    'difficulty' => 'hard', 'points' => 200,
                    'statement' => "Clase `CuentaBancaria` con `private int saldo`. Constructor recibe el saldo inicial. ".
                        "`void depositar(int monto)`: solo si `monto > 0`, suma al saldo. `boolean retirar(int monto)`: si ".
                        "`monto > 0 && monto <= saldo`, descuenta y retorna `true`; si no, no cambia nada y retorna ".
                        "`false`. Leé saldo inicial, monto a depositar y monto a retirar; depositá, luego retirá; e ".
                        "imprimí `saldo:X retiroExitoso:true/false`.\n\n".
                        '**Entrada:** `1000 500 2000`'."\n".'**Salida:** `saldo:1500 retiroExitoso:false`',
                    'starter_code' => "import java.util.Scanner;\n\npublic class Main {\n    public static void main(String[] args) {\n        Scanner sc = new Scanner(System.in);\n        int saldoInicial = sc.nextInt();\n        int deposito = sc.nextInt();\n        int retiro = sc.nextInt();\n\n        // TODO: crear la cuenta, depositar(deposito), retirar(retiro) e imprimir \"saldo:X retiroExitoso:true/false\"\n    }\n}\n\nclass CuentaBancaria {\n    // TODO: private int saldo, constructor, void depositar(int) y boolean retirar(int)\n}\n",
                    'tests' => [
                        ['stdin' => '1000 500 2000', 'expected_output' => 'saldo:1500 retiroExitoso:false', 'is_hidden' => false],
                        ['stdin' => '1000 0 300', 'expected_output' => 'saldo:700 retiroExitoso:true', 'is_hidden' => false],
                        ['stdin' => '500 -100 100', 'expected_output' => 'saldo:400 retiroExitoso:true', 'is_hidden' => true],
                        ['stdin' => '0 100 50', 'expected_output' => 'saldo:50 retiroExitoso:true', 'is_hidden' => true],
                    ],
                ],
                [
                    'title' => 'Empleado: el salario no baja',
                    'difficulty' => 'medium', 'points' => 150,
                    'statement' => "Clase `Empleado` con `private int salario`. Constructor recibe el salario inicial. ".
                        "`setSalario(int nuevo)`: solo actualiza si `nuevo > salario` (estrictamente mayor); si no, no ".
                        "cambia nada. `getSalario()` retorna el salario. Leé salario inicial y nuevo salario a intentar, ".
                        "e imprimí `getSalario()`.\n\n".
                        '**Entrada:** `30000 35000`'."\n".'**Salida:** `35000`',
                    'starter_code' => "import java.util.Scanner;\n\npublic class Main {\n    public static void main(String[] args) {\n        Scanner sc = new Scanner(System.in);\n        int inicial = sc.nextInt();\n        int nuevo = sc.nextInt();\n\n        // TODO: crear el Empleado, llamar setSalario(nuevo) e imprimir getSalario()\n    }\n}\n\nclass Empleado {\n    // TODO: private int salario, constructor, setSalario(int) que solo permite subir y getSalario()\n}\n",
                    'tests' => [
                        ['stdin' => '30000 35000', 'expected_output' => '35000', 'is_hidden' => false],
                        ['stdin' => '30000 20000', 'expected_output' => '30000', 'is_hidden' => false],
                        ['stdin' => '30000 30000', 'expected_output' => '30000', 'is_hidden' => true],
                        ['stdin' => '10000 50000', 'expected_output' => '50000', 'is_hidden' => true],
                    ],
                ],
                [
                    'title' => 'Calificacion: nota y aprobado',
                    'difficulty' => 'hard', 'points' => 200,
                    'statement' => "Clase `Calificacion` con `private double nota` (inicial 0). `setNota(double n)` ajusta (clamp) ".
                        "el valor al rango `[0, 10]`. Metodo `aprobado()` (boolean) retorna `nota >= 6`. Leé la nota a ".
                        "intentar; llamá `setNota`; e imprimí `nota:X.XX aprobado:true/false` (nota con **2 decimales**).\n\n".
                        '**Entrada:** `12`'."\n".'**Salida:** `nota:10.00 aprobado:true`',
                    'starter_code' => "import java.util.Scanner;\n\npublic class Main {\n    public static void main(String[] args) {\n        Scanner sc = new Scanner(System.in);\n        double n = sc.nextDouble();\n\n        // TODO: crear la Calificacion, llamar setNota(n) e imprimir \"nota:X.XX aprobado:true/false\"\n    }\n}\n\nclass Calificacion {\n    // TODO: private double nota, constructor (inicial 0), setNota(double) con clamp [0,10] y aprobado()\n}\n",
                    'tests' => [
                        ['stdin' => '12', 'expected_output' => 'nota:10.00 aprobado:true', 'is_hidden' => false],
                        ['stdin' => '-3', 'expected_output' => 'nota:0.00 aprobado:false', 'is_hidden' => false],
                        ['stdin' => '6', 'expected_output' => 'nota:6.00 aprobado:true', 'is_hidden' => true],
                        ['stdin' => '5.99', 'expected_output' => 'nota:5.99 aprobado:false', 'is_hidden' => true],
                    ],
                ],
                [
                    'title' => 'Termostato: temperatura y encendido',
                    'difficulty' => 'hard', 'points' => 200,
                    'statement' => "Clase `Termostato` con `private int temperaturaObjetivo` (inicial 20). ".
                        "`setTemperaturaObjetivo(int t)`: solo actualiza si `t` esta en `[16, 30]`; si no, no cambia nada. ".
                        "Metodo `puedeEncender()` (boolean) retorna `temperaturaObjetivo >= 18`. Leé la temperatura deseada; ".
                        "llamá `setTemperaturaObjetivo`; e imprimí `temp:X puedeEncender:true/false`.\n\n".
                        '**Entrada:** `25`'."\n".'**Salida:** `temp:25 puedeEncender:true`',
                    'starter_code' => "import java.util.Scanner;\n\npublic class Main {\n    public static void main(String[] args) {\n        Scanner sc = new Scanner(System.in);\n        int deseada = sc.nextInt();\n\n        // TODO: crear el Termostato, llamar setTemperaturaObjetivo(deseada) e imprimir \"temp:X puedeEncender:true/false\"\n    }\n}\n\nclass Termostato {\n    // TODO: private int temperaturaObjetivo (inicial 20), setTemperaturaObjetivo(int) que valida [16,30] y puedeEncender()\n}\n",
                    'tests' => [
                        ['stdin' => '25', 'expected_output' => 'temp:25 puedeEncender:true', 'is_hidden' => false],
                        ['stdin' => '10', 'expected_output' => 'temp:20 puedeEncender:true', 'is_hidden' => false],
                        ['stdin' => '17', 'expected_output' => 'temp:17 puedeEncender:false', 'is_hidden' => true],
                        ['stdin' => '40', 'expected_output' => 'temp:20 puedeEncender:true', 'is_hidden' => true],
                    ],
                ],
            ],
        ];
    }

    private function cl14(): array
    {
        return [
            'title' => 'POO en Java IV — Polimorfismo',
            'description' => 'CL1.4: herencia, sobrescritura de metodos y polimorfismo.',
            'lesson_title' => 'Herencia y sobrescritura de metodos',
            'lesson_content' => $this->javaIntro('polimorfismo: una superclase define un metodo, y cada subclase lo sobrescribe con su propio comportamiento').
                "\n\n```java\nclass Animal { String sonido() { return \"...\"; } }\nclass Perro extends Animal {\n    @Override\n    String sonido() { return \"Guau\"; }\n}\n\nAnimal a = new Perro();\na.sonido(); // \"Guau\" -- se ejecuta el metodo de Perro aunque la referencia sea Animal\n```",
            'challenges' => [
                [
                    'title' => 'Animal: sonido',
                    'difficulty' => 'easy', 'points' => 100,
                    'statement' => "Superclase `Animal` con metodo `sonido()` que retorna `\"...\"`. Subclases `Perro` (sobrescribe ".
                        "`sonido()` -> `\"Guau\"`) y `Gato` (sobrescribe `sonido()` -> `\"Miau\"`). Leé un tipo (`0`=Perro, ".
                        "`1`=Gato), creá el animal correspondiente usando una referencia `Animal`, e imprimí `sonido()`.\n\n".
                        '**Entrada:** `0`'."\n".'**Salida:** `Guau`',
                    'starter_code' => "import java.util.Scanner;\n\npublic class Main {\n    public static void main(String[] args) {\n        Scanner sc = new Scanner(System.in);\n        int tipo = sc.nextInt();\n\n        Animal a = (tipo == 0) ? new Perro() : new Gato();\n        // TODO: imprimir a.sonido()\n    }\n}\n\nclass Animal {\n    String sonido() { return \"...\"; }\n}\n\nclass Perro extends Animal {\n    // TODO: sobrescribir sonido() -> \"Guau\"\n}\n\nclass Gato extends Animal {\n    // TODO: sobrescribir sonido() -> \"Miau\"\n}\n",
                    'tests' => [
                        ['stdin' => '0', 'expected_output' => 'Guau', 'is_hidden' => false],
                        ['stdin' => '1', 'expected_output' => 'Miau', 'is_hidden' => false],
                    ],
                ],
                [
                    'title' => 'Figura: area polimorfica',
                    'difficulty' => 'medium', 'points' => 150,
                    'statement' => "Superclase `Figura` con metodo `area()` (double) que retorna `0`. Subclase `Cuadrado` (recibe ".
                        "`lado` entero, sobrescribe `area()` -> `lado * lado`). Subclase `CirculoF` (recibe `radio` entero, ".
                        "sobrescribe `area()` -> `Math.PI * radio * radio`). Leé un tipo (`0`=Cuadrado, `1`=CirculoF) y una ".
                        "medida; creá la figura usando una referencia `Figura`; e imprimí `area()` con **2 decimales**.\n\n".
                        '**Entrada:** `0 4`'."\n".'**Salida:** `16.00`',
                    'starter_code' => "import java.util.Scanner;\n\npublic class Main {\n    public static void main(String[] args) {\n        Scanner sc = new Scanner(System.in);\n        int tipo = sc.nextInt();\n        int medida = sc.nextInt();\n\n        Figura f = (tipo == 0) ? new Cuadrado(medida) : new CirculoF(medida);\n        // TODO: imprimir f.area() con 2 decimales\n    }\n}\n\nclass Figura {\n    double area() { return 0; }\n}\n\nclass Cuadrado extends Figura {\n    // TODO: atributo lado, constructor y sobrescribir area()\n}\n\nclass CirculoF extends Figura {\n    // TODO: atributo radio, constructor y sobrescribir area()\n}\n",
                    'tests' => [
                        ['stdin' => '0 4', 'expected_output' => '16.00', 'is_hidden' => false],
                        ['stdin' => '1 5', 'expected_output' => '78.54', 'is_hidden' => false],
                        ['stdin' => '0 10', 'expected_output' => '100.00', 'is_hidden' => true],
                        ['stdin' => '1 2', 'expected_output' => '12.57', 'is_hidden' => true],
                    ],
                ],
                [
                    'title' => 'Empleado: sueldo polimorfico',
                    'difficulty' => 'medium', 'points' => 150,
                    'statement' => "Superclase `Empleado` con `int sueldoBase` y metodo `calcularSueldo()` que retorna `sueldoBase`. ".
                        "Subclase `Gerente` (recibe `sueldoBase` y `bono`, sobrescribe `calcularSueldo()` -> ".
                        "`sueldoBase + bono`). Leé un tipo (`0`=Empleado, `1`=Gerente): si es `0`, leé sueldoBase; si es ".
                        "`1`, leé sueldoBase y bono. Imprimí `calcularSueldo()`.\n\n".
                        '**Entrada:** `0 30000`'."\n".'**Salida:** `30000`',
                    'starter_code' => "import java.util.Scanner;\n\npublic class Main {\n    public static void main(String[] args) {\n        Scanner sc = new Scanner(System.in);\n        int tipo = sc.nextInt();\n\n        Empleado e;\n        if (tipo == 0) {\n            e = new Empleado(sc.nextInt());\n        } else {\n            e = new Gerente(sc.nextInt(), sc.nextInt());\n        }\n        // TODO: imprimir e.calcularSueldo()\n    }\n}\n\nclass Empleado {\n    int sueldoBase;\n    Empleado(int sueldoBase) { this.sueldoBase = sueldoBase; }\n    int calcularSueldo() { return sueldoBase; }\n}\n\nclass Gerente extends Empleado {\n    // TODO: atributo bono, constructor Gerente(int sueldoBase, int bono) y sobrescribir calcularSueldo()\n}\n",
                    'tests' => [
                        ['stdin' => '0 30000', 'expected_output' => '30000', 'is_hidden' => false],
                        ['stdin' => '1 30000 5000', 'expected_output' => '35000', 'is_hidden' => false],
                        ['stdin' => '1 50000 10000', 'expected_output' => '60000', 'is_hidden' => true],
                    ],
                ],
                [
                    'title' => 'Vehiculo: descripcion polimorfica',
                    'difficulty' => 'easy', 'points' => 100,
                    'statement' => "Superclase `Vehiculo` con `String marca` y metodo `describir()` -> `\"Vehiculo: marca\"`. Subclase ".
                        "`Auto` (sobrescribe -> `\"Auto: marca\"`). Subclase `Moto` (sobrescribe -> `\"Moto: marca\"`). Leé un ".
                        "tipo (`0`=Auto, `1`=Moto) y una marca; creá el vehiculo usando una referencia `Vehiculo`; e ".
                        "imprimí `describir()`.\n\n".
                        '**Entrada:** `0 Toyota`'."\n".'**Salida:** `Auto: Toyota`',
                    'starter_code' => "import java.util.Scanner;\n\npublic class Main {\n    public static void main(String[] args) {\n        Scanner sc = new Scanner(System.in);\n        int tipo = sc.nextInt();\n        String marca = sc.next();\n\n        Vehiculo v = (tipo == 0) ? new Auto(marca) : new Moto(marca);\n        // TODO: imprimir v.describir()\n    }\n}\n\nclass Vehiculo {\n    String marca;\n    Vehiculo(String marca) { this.marca = marca; }\n    String describir() { return \"Vehiculo: \" + marca; }\n}\n\nclass Auto extends Vehiculo {\n    // TODO: constructor Auto(String marca) y sobrescribir describir()\n}\n\nclass Moto extends Vehiculo {\n    // TODO: constructor Moto(String marca) y sobrescribir describir()\n}\n",
                    'tests' => [
                        ['stdin' => '0 Toyota', 'expected_output' => 'Auto: Toyota', 'is_hidden' => false],
                        ['stdin' => '1 Honda', 'expected_output' => 'Moto: Honda', 'is_hidden' => false],
                        ['stdin' => '0 Ford', 'expected_output' => 'Auto: Ford', 'is_hidden' => true],
                    ],
                ],
                [
                    'title' => 'Ave: puede volar',
                    'difficulty' => 'easy', 'points' => 100,
                    'statement' => "Superclase `Ave` con metodo `puedeVolar()` (boolean) que retorna `true`. Subclase `Pinguino` ".
                        "sobrescribe `puedeVolar()` -> `false`. Subclase `Aguila` **hereda** el metodo sin sobrescribirlo. ".
                        "Leé un tipo (`0`=Pinguino, `1`=Aguila), creá el ave usando una referencia `Ave`, e imprimí ".
                        "`puedeVolar()`.\n\n".
                        '**Entrada:** `0`'."\n".'**Salida:** `false`',
                    'starter_code' => "import java.util.Scanner;\n\npublic class Main {\n    public static void main(String[] args) {\n        Scanner sc = new Scanner(System.in);\n        int tipo = sc.nextInt();\n\n        Ave a = (tipo == 0) ? new Pinguino() : new Aguila();\n        // TODO: imprimir a.puedeVolar()\n    }\n}\n\nclass Ave {\n    boolean puedeVolar() { return true; }\n}\n\nclass Pinguino extends Ave {\n    // TODO: sobrescribir puedeVolar() -> false\n}\n\nclass Aguila extends Ave {\n    // no hace falta sobrescribir nada: hereda puedeVolar() de Ave\n}\n",
                    'tests' => [
                        ['stdin' => '0', 'expected_output' => 'false', 'is_hidden' => false],
                        ['stdin' => '1', 'expected_output' => 'true', 'is_hidden' => false],
                    ],
                ],
                [
                    'title' => 'Personaje: ataque',
                    'difficulty' => 'medium', 'points' => 150,
                    'statement' => "Superclase `Personaje` con metodo `atacar()` que retorna `10`. Subclase `Guerrero` sobrescribe ".
                        "`atacar()` -> `25`. Subclase `Mago` sobrescribe `atacar()` -> `40`. Leé un tipo (`0`=Personaje, ".
                        "`1`=Guerrero, `2`=Mago), creá el personaje usando una referencia `Personaje`, e imprimí `atacar()`.\n\n".
                        '**Entrada:** `1`'."\n".'**Salida:** `25`',
                    'starter_code' => "import java.util.Scanner;\n\npublic class Main {\n    public static void main(String[] args) {\n        Scanner sc = new Scanner(System.in);\n        int tipo = sc.nextInt();\n\n        Personaje p;\n        if (tipo == 0) p = new Personaje();\n        else if (tipo == 1) p = new Guerrero();\n        else p = new Mago();\n        // TODO: imprimir p.atacar()\n    }\n}\n\nclass Personaje {\n    int atacar() { return 10; }\n}\n\nclass Guerrero extends Personaje {\n    // TODO: sobrescribir atacar() -> 25\n}\n\nclass Mago extends Personaje {\n    // TODO: sobrescribir atacar() -> 40\n}\n",
                    'tests' => [
                        ['stdin' => '1', 'expected_output' => '25', 'is_hidden' => false],
                        ['stdin' => '2', 'expected_output' => '40', 'is_hidden' => false],
                        ['stdin' => '0', 'expected_output' => '10', 'is_hidden' => true],
                    ],
                ],
                [
                    'title' => 'Descuento: calculo polimorfico',
                    'difficulty' => 'hard', 'points' => 200,
                    'statement' => "Superclase `Descuento` con metodo `calcular(double precio)` que retorna `precio` (sin ".
                        "descuento). Subclase `DescuentoFijo` (recibe `monto`, sobrescribe `calcular(precio)` -> ".
                        "`max(precio - monto, 0)`). Subclase `DescuentoPorcentual` (recibe `porcentaje`, sobrescribe ".
                        "`calcular(precio)` -> `precio - precio * porcentaje / 100`). Leé un tipo (`0`=Fijo, ".
                        "`1`=Porcentual), un precio y el parametro del descuento; creá el descuento usando una ".
                        "referencia `Descuento`; e imprimí `calcular(precio)` con **2 decimales**.\n\n".
                        '**Entrada:** `0 100 30`'."\n".'**Salida:** `70.00`',
                    'starter_code' => "import java.util.Scanner;\n\npublic class Main {\n    public static void main(String[] args) {\n        Scanner sc = new Scanner(System.in);\n        int tipo = sc.nextInt();\n        double precio = sc.nextInt();\n        double parametro = sc.nextInt();\n\n        Descuento d = (tipo == 0) ? new DescuentoFijo(parametro) : new DescuentoPorcentual(parametro);\n        // TODO: imprimir d.calcular(precio) con 2 decimales\n    }\n}\n\nclass Descuento {\n    double calcular(double precio) { return precio; }\n}\n\nclass DescuentoFijo extends Descuento {\n    // TODO: atributo monto, constructor y sobrescribir calcular(double precio) (minimo 0)\n}\n\nclass DescuentoPorcentual extends Descuento {\n    // TODO: atributo porcentaje, constructor y sobrescribir calcular(double precio)\n}\n",
                    'tests' => [
                        ['stdin' => '0 100 30', 'expected_output' => '70.00', 'is_hidden' => false],
                        ['stdin' => '1 100 20', 'expected_output' => '80.00', 'is_hidden' => false],
                        ['stdin' => '0 50 100', 'expected_output' => '0.00', 'is_hidden' => true],
                        ['stdin' => '1 200 50', 'expected_output' => '100.00', 'is_hidden' => true],
                    ],
                ],
                [
                    'title' => 'Empleado: tiempo completo vs medio tiempo',
                    'difficulty' => 'medium', 'points' => 150,
                    'statement' => "Superclase `Empleado` con `int horasTrabajadas` e `int valorHora`, y metodo `calcularPago()` que ".
                        "retorna `horasTrabajadas * valorHora`. Subclase `EmpleadoTiempoCompleto` sobrescribe ".
                        "`calcularPago()` sumando un plus fijo de `5000` al resultado base. Subclase ".
                        "`EmpleadoMedioTiempo` **hereda** el calculo sin sobrescribirlo. Leé un tipo (`0`=TiempoCompleto, ".
                        "`1`=MedioTiempo), horas y valorHora; creá el empleado usando una referencia `Empleado`; e ".
                        "imprimí `calcularPago()`.\n\n".
                        '**Entrada:** `0 8 500`'."\n".'**Salida:** `9000`',
                    'starter_code' => "import java.util.Scanner;\n\npublic class Main {\n    public static void main(String[] args) {\n        Scanner sc = new Scanner(System.in);\n        int tipo = sc.nextInt();\n        int horas = sc.nextInt();\n        int valorHora = sc.nextInt();\n\n        Empleado e = (tipo == 0) ? new EmpleadoTiempoCompleto(horas, valorHora) : new EmpleadoMedioTiempo(horas, valorHora);\n        // TODO: imprimir e.calcularPago()\n    }\n}\n\nclass Empleado {\n    int horasTrabajadas, valorHora;\n    Empleado(int horasTrabajadas, int valorHora) { this.horasTrabajadas = horasTrabajadas; this.valorHora = valorHora; }\n    int calcularPago() { return horasTrabajadas * valorHora; }\n}\n\nclass EmpleadoTiempoCompleto extends Empleado {\n    // TODO: constructor y sobrescribir calcularPago() sumando 5000 al resultado de la superclase\n}\n\nclass EmpleadoMedioTiempo extends Empleado {\n    // TODO: constructor (no hace falta sobrescribir calcularPago())\n}\n",
                    'tests' => [
                        ['stdin' => '0 8 500', 'expected_output' => '9000', 'is_hidden' => false],
                        ['stdin' => '1 4 500', 'expected_output' => '2000', 'is_hidden' => false],
                        ['stdin' => '0 10 300', 'expected_output' => '8000', 'is_hidden' => true],
                        ['stdin' => '1 6 200', 'expected_output' => '1200', 'is_hidden' => true],
                    ],
                ],
                [
                    'title' => 'Notificacion: formato de envio',
                    'difficulty' => 'medium', 'points' => 150,
                    'statement' => "Superclase `Notificacion` con `String destinatario` y metodo `enviar()` -> ".
                        "`\"Notificando a destinatario\"`. Subclase `NotificacionEmail` sobrescribe `enviar()` -> ".
                        "`\"Email para destinatario\"`. Subclase `NotificacionSMS` sobrescribe `enviar()` -> ".
                        "`\"SMS para destinatario\"`. Leé un tipo (`0`=Email, `1`=SMS) y un destinatario (una palabra, sin ".
                        "espacios); creá la notificacion usando una referencia `Notificacion`; e imprimí `enviar()`.\n\n".
                        '**Entrada:** `0 ana@mail.com`'."\n".'**Salida:** `Email para ana@mail.com`',
                    'starter_code' => "import java.util.Scanner;\n\npublic class Main {\n    public static void main(String[] args) {\n        Scanner sc = new Scanner(System.in);\n        int tipo = sc.nextInt();\n        String destinatario = sc.next();\n\n        Notificacion n = (tipo == 0) ? new NotificacionEmail(destinatario) : new NotificacionSMS(destinatario);\n        // TODO: imprimir n.enviar()\n    }\n}\n\nclass Notificacion {\n    String destinatario;\n    Notificacion(String destinatario) { this.destinatario = destinatario; }\n    String enviar() { return \"Notificando a \" + destinatario; }\n}\n\nclass NotificacionEmail extends Notificacion {\n    // TODO: constructor y sobrescribir enviar()\n}\n\nclass NotificacionSMS extends Notificacion {\n    // TODO: constructor y sobrescribir enviar()\n}\n",
                    'tests' => [
                        ['stdin' => '0 ana@mail.com', 'expected_output' => 'Email para ana@mail.com', 'is_hidden' => false],
                        ['stdin' => '1 099123456', 'expected_output' => 'SMS para 099123456', 'is_hidden' => false],
                        ['stdin' => '0 bruno@mail.com', 'expected_output' => 'Email para bruno@mail.com', 'is_hidden' => true],
                    ],
                ],
                [
                    'title' => 'Forma: perimetros polimorficos',
                    'difficulty' => 'hard', 'points' => 200,
                    'statement' => "Superclase `Forma` con metodo `perimetro()` (double) que retorna `0`. Subclase `Triangulo` ".
                        "(equilatero, recibe `lado`, sobrescribe `perimetro()` -> `lado * 3`). Subclase `CuadradoF` ".
                        "(recibe `lado`, sobrescribe `perimetro()` -> `lado * 4`). Leé tipo1, medida1, tipo2 y medida2 ".
                        "(`0`=Triangulo, `1`=CuadradoF); creá **dos** formas usando referencias `Forma`; sumá sus ".
                        "perimetros; e imprimí la suma con **2 decimales**.\n\n".
                        '**Entrada:** `0 3 1 4`'."\n".'**Salida:** `25.00`',
                    'starter_code' => "import java.util.Scanner;\n\npublic class Main {\n    public static void main(String[] args) {\n        Scanner sc = new Scanner(System.in);\n        int tipo1 = sc.nextInt();\n        int medida1 = sc.nextInt();\n        int tipo2 = sc.nextInt();\n        int medida2 = sc.nextInt();\n\n        Forma f1 = (tipo1 == 0) ? new Triangulo(medida1) : new CuadradoF(medida1);\n        Forma f2 = (tipo2 == 0) ? new Triangulo(medida2) : new CuadradoF(medida2);\n        // TODO: sumar f1.perimetro() + f2.perimetro() e imprimir con 2 decimales\n    }\n}\n\nclass Forma {\n    double perimetro() { return 0; }\n}\n\nclass Triangulo extends Forma {\n    // TODO: atributo lado, constructor y sobrescribir perimetro() -> lado * 3\n}\n\nclass CuadradoF extends Forma {\n    // TODO: atributo lado, constructor y sobrescribir perimetro() -> lado * 4\n}\n",
                    'tests' => [
                        ['stdin' => '0 3 1 4', 'expected_output' => '25.00', 'is_hidden' => false],
                        ['stdin' => '1 5 0 2', 'expected_output' => '26.00', 'is_hidden' => false],
                        ['stdin' => '0 10 0 10', 'expected_output' => '60.00', 'is_hidden' => true],
                    ],
                ],
            ],
        ];
    }
}

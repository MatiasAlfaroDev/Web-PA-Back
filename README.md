# Courses App — Backend (Laravel API)

Backend estilo AdventJS: estudiantes se registran con **CI uruguaya**, verifican su email con un código, leen lecciones, resuelven **code challenges** en JavaScript (sandbox: `node:vm`) y compiten en un **leaderboard**. Los docentes ven el progreso de cada estudiante y administran cursos/lecciones/challenges.

## Stack

- Laravel (API REST, sin vistas) + Sanctum (tokens)
- PostgreSQL (Supabase)
- Judge: JavaScript vía proceso Node.js (`judge/run.mjs`, sandbox `node:vm` + timeout); Java vía Piston self-hosted (Docker)
- Mailpit (SMTP de desarrollo con UI web)

## Setup

Requisitos: PHP 8.3+, Composer, Node.js, Docker (judge de Java), una base Postgres (Supabase).

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate

# completar DB_PASSWORD en .env con la contraseña de Supabase
php artisan migrate --seed

docker compose up -d        # levanta Piston (judge de Java) en localhost:2000
curl -X POST http://localhost:2000/api/v2/packages \
  -H "Content-Type: application/json" \
  -d '{"language":"java","version":"15.0.2"}'   # sólo la primera vez

php artisan serve           # API en http://localhost:8000
php artisan queue:work      # worker del judge (segunda terminal)
```

### Usuarios seed

| Email | Password | Rol |
|---|---|---|
| `teacher@courses.test` | `Password1!` | teacher |
| `ana@courses.test` / `bruno@courses.test` | `Password1!` | student |

## API

Autenticación: `Authorization: Bearer <token>` (el token lo devuelve `/api/login`). Todas las rutas protegidas exigen email verificado.

### Público

| Método | Ruta | Descripción |
|---|---|---|
| POST | `/api/register` | `first_name, last_name, ci, email, password` — valida CI uruguaya, envía código por email |
| POST | `/api/verify-email` | `email, code` (6 dígitos, expira en 15 min) |
| POST | `/api/resend-code` | `email` |
| POST | `/api/login` | `email, password` → `{token, user}` |
| POST | `/api/forgot-password` | `email` — envía link a `FRONTEND_URL/reset-password` |
| POST | `/api/reset-password` | `token, email, password, password_confirmation` |

### Estudiante (autenticado + verificado)

| Método | Ruta | Descripción |
|---|---|---|
| GET/PATCH | `/api/profile` | Ver / editar `first_name, last_name, bio` |
| GET | `/api/courses`, `/api/courses/{id}` | Cursos, con lecciones y challenges publicados |
| GET | `/api/lessons/{id}` | Contenido markdown de la lección |
| GET | `/api/challenges/{id}` | Enunciado + starter code + test cases de ejemplo (no ocultos) + mejor puntaje propio |
| POST | `/api/challenges/{id}/submissions` | `code` (JS) → 202, se juzga async |
| GET | `/api/submissions/{id}` | Estado/resultado de la submission (propia) |
| GET | `/api/challenges/{id}/submissions` | Historial propio en el challenge |
| GET | `/api/leaderboard` | Ranking: suma del mejor puntaje por challenge |

### Docente (rol `teacher`)

| Método | Ruta |
|---|---|
| POST/PATCH/DELETE | `/api/courses[/{id}]` |
| POST | `/api/courses/{id}/lessons` · PATCH/DELETE `/api/lessons/{id}` |
| POST | `/api/courses/{id}/challenges` · PATCH/DELETE `/api/challenges/{id}` |
| GET | `/api/teacher/challenges/{id}` (incluye test cases ocultos) |
| POST | `/api/challenges/{id}/test-cases` · PATCH/DELETE `/api/test-cases/{id}` |
| GET | `/api/teacher/students` — estudiantes con puntaje total y challenges resueltos |
| GET | `/api/teacher/students/{id}` — progreso por challenge + últimas submissions |

## Judge

Cada submission se corre contra los `test_cases` del challenge, comparando stdout contra `expected_output`. `Challenge.language` decide el runner:

- **`javascript`** (default): el código recibe el `stdin` de cada caso en la variable global `stdin` e imprime con `console.log`. `JudgeSubmission` invoca `node judge/run.mjs`, que corre el código en un `node:vm` context con timeout — aísla globals (sin `require`/`process`/`fs`) pero no es un sandbox de seguridad fuerte tipo `isolated-vm` (ese paquete requiere un toolchain C++20, no disponible en todos los hosts). Suficiente para un curso; revisar si alguna vez hay que endurecerlo contra código adversarial.
- **`java`**: el código debe ser una clase `public class Main` con `main(String[] args)` que lee de `System.in` (típicamente con `Scanner`) e imprime con `System.out.println`. `JudgeSubmission` llama a **Piston** ([engineer-man/piston](https://github.com/engineer-man/piston)) self-hosted vía Docker (`docker-compose.yml`, puerto 2000) — un POST a `/api/v2/execute` por test case, que compila y ejecuta en un contenedor aislado. La API pública de Piston (emkc.org) pasó a ser whitelist-only en 2026; por eso se self-hostea. Piston usa `nsjail`, no `isolate`, así que no sufre el problema de cgroup v1/v2 que tuvo Judge0. Mismo nivel de confianza "aula" que el sandbox de JS: no es defensa contra código adversarial (el contenedor corre `--privileged`).

## Puntaje

`score = round(points × tests_pasados / tests_totales)` (parcial por test case). El leaderboard suma el **mejor intento** por challenge. Un challenge cuenta como *resuelto* cuando todos los tests pasan.

## Tests

```bash
php artisan test
```

Las submissions de test corren el judge real (Node) contra código JS embebido en los tests; no hace falta mockear nada.

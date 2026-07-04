# Courses App — Backend (Laravel API)

Backend estilo AdventJS: estudiantes se registran con **CI uruguaya**, verifican su email con un código, leen lecciones, resuelven **code challenges** que se ejecutan en sandbox ([Judge0](https://judge0.com/)) y compiten en un **leaderboard**. Los docentes ven el progreso de cada estudiante y administran cursos/lecciones/challenges.

## Stack

- Laravel (API REST, sin vistas) + Sanctum (tokens)
- PostgreSQL 16
- Judge0 CE (ejecución de código en sandbox, self-hosted en Docker)
- Mailpit (SMTP de desarrollo con UI web)

## Setup

Requisitos: PHP 8.3+, Composer, Docker Desktop.

```bash
composer install
cp .env.example .env        # ya viene apuntando a los contenedores locales
php artisan key:generate

docker compose up -d        # postgres + mailpit + judge0
php artisan migrate --seed

php artisan serve           # API en http://localhost:8000
php artisan queue:work      # worker del judge (segunda terminal)
```

- **Mailpit UI:** http://localhost:8025 (acá llegan los códigos de verificación y links de reset)
- **Judge0:** http://localhost:2358/languages (ids de lenguajes para las submissions)

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
| GET | `/api/challenges/{id}` | Enunciado + test cases de ejemplo (no ocultos) + mejor puntaje propio |
| POST | `/api/challenges/{id}/submissions` | `language_id` (id Judge0), `code` → 202, se juzga async |
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

## Puntaje

`score = round(points × tests_pasados / tests_totales)` (parcial por test case). El leaderboard suma el **mejor intento** por challenge. Un challenge cuenta como *resuelto* cuando todos los tests pasan.

## Tests

```bash
php artisan test
```

Judge0 se mockea con `Http::fake()`; no se necesita Docker para correr los tests.

## Troubleshooting

### Judge0 devuelve "Internal Error" en todos los test cases

En los logs del worker aparece `Failed to create control group /sys/fs/cgroup/memory/box-N/: No such file or directory`.

Causa: el `isolate` de Judge0 1.13.x necesita **cgroup v1**, pero Docker Desktop / WSL2 modernos usan **cgroup v2**. El código de la app es correcto (los tests lo prueban); es un tema del host.

Fix (WSL2): crear/editar `C:\Users\<usuario>\.wslconfig` y forzar cgroup v1:

```ini
[wsl2]
kernelCommandLine = systemd.unified_cgroup_hierarchy=0
```

Luego, en PowerShell: `wsl --shutdown` (cierra Docker Desktop), reiniciar Docker Desktop y `docker compose up -d` de nuevo. Referencia: [docs de instalación de Judge0 — cgroup v2](https://github.com/judge0/judge0/blob/master/CHANGELOG.md).

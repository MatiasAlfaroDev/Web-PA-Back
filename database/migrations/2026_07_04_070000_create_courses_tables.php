<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('teacher_id')->constrained('users');
            $table->timestamps();
        });

        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('content'); // markdown
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('statement'); // markdown
            $table->text('starter_code')->nullable();
            $table->unsignedInteger('points')->default(100);
            $table->string('difficulty')->default('easy'); // easy | medium | hard
            $table->unsignedInteger('position')->default(0);
            $table->boolean('published')->default(false);
            $table->timestamps();
        });

        Schema::create('test_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challenge_id')->constrained()->cascadeOnDelete();
            $table->text('stdin')->nullable();
            $table->text('expected_output');
            $table->boolean('is_hidden')->default(false);
            $table->timestamps();
        });

        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('challenge_id')->constrained()->cascadeOnDelete();
            $table->text('code');
            $table->string('status')->default('pending'); // pending|judging|passed|partial|failed|error
            $table->unsignedInteger('passed_count')->default(0);
            $table->unsignedInteger('total_count')->default(0);
            $table->unsignedInteger('score')->default(0);
            $table->json('judge_output')->nullable();
            $table->timestamps();

            $table->index(['challenge_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submissions');
        Schema::dropIfExists('test_cases');
        Schema::dropIfExists('challenges');
        Schema::dropIfExists('lessons');
        Schema::dropIfExists('courses');
    }
};

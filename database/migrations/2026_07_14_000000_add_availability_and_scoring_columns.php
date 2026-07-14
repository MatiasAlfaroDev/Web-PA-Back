<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->boolean('published')->default(true)->after('description');
            $table->timestamp('available_from')->nullable()->after('published');
            $table->timestamp('available_until')->nullable()->after('available_from');
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->boolean('published')->default(true)->after('position');
            $table->timestamp('available_from')->nullable()->after('published');
            $table->timestamp('available_until')->nullable()->after('available_from');
        });

        Schema::table('challenges', function (Blueprint $table) {
            // Floor for the first-solver-decay score below. Null = no decay, always full points.
            $table->unsignedInteger('min_points')->nullable()->after('points');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['published', 'available_from', 'available_until']);
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn(['published', 'available_from', 'available_until']);
        });

        Schema::table('challenges', function (Blueprint $table) {
            $table->dropColumn('min_points');
        });
    }
};

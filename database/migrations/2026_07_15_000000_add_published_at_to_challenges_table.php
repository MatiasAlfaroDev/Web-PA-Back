<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('challenges', function (Blueprint $table) {
            // Set when a challenge transitions draft -> published; drives the
            // day-based score decay below (null = never published = no decay).
            $table->timestamp('published_at')->nullable()->after('published');
        });
    }

    public function down(): void
    {
        Schema::table('challenges', function (Blueprint $table) {
            $table->dropColumn('published_at');
        });
    }
};

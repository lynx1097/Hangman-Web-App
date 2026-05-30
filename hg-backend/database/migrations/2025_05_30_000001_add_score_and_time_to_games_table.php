<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            // Seconds the player took, as measured by the front-end clock.
            // (The `score` column already exists on the games table.)
            $table->unsignedInteger('elapsed_seconds')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn('elapsed_seconds');
        });
    }
};

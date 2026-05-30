<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            // Hint + category come from the Word Game DB API (or the local
            // fallback list) and are stored so they survive across requests.
            $table->string('hint')->nullable()->after('word');
            $table->string('category')->nullable()->after('hint');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn(['hint', 'category']);
        });
    }
};

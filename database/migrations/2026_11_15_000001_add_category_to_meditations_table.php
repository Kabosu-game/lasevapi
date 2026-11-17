<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Espace ACTIVITÉS : chaque contenu audio/vidéo appartient à une catégorie
 * (meditation, jogging, relaxation, sommeil, concentration, respiration).
 * Les méditations existantes restent dans « meditation ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meditations', function (Blueprint $table) {
            $table->string('category', 32)->default('meditation')->after('description');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::table('meditations', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropColumn('category');
        });
    }
};

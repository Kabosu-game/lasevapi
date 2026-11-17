<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Exercices de respiration guidés : rythmes gérés dans l'admin
 * (inspiration, rétention poumons pleins, expiration, pause poumons vides).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('breathing_patterns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description', 500)->nullable();
            $table->unsignedTinyInteger('inhale_seconds');
            $table->unsignedTinyInteger('hold_in_seconds')->default(0);
            $table->unsignedTinyInteger('exhale_seconds');
            $table->unsignedTinyInteger('hold_out_seconds')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();
        $patterns = [
            ['Cohérence cardiaque', "5 s d'inspiration, 5 s d'expiration. Idéal pour apaiser le stress.", 5, 0, 5, 0],
            ['Respiration carrée', '4 temps égaux pour retrouver calme et concentration.', 4, 4, 4, 4],
            ['Respiration 4-7-8', "Favorise la détente et l'endormissement.", 4, 7, 8, 0],
            ['Souffle apaisant', "Une expiration plus longue que l'inspiration pour relâcher les tensions.", 4, 0, 6, 0],
        ];
        foreach ($patterns as $i => [$name, $description, $inhale, $holdIn, $exhale, $holdOut]) {
            DB::table('breathing_patterns')->insert([
                'name' => $name,
                'description' => $description,
                'inhale_seconds' => $inhale,
                'hold_in_seconds' => $holdIn,
                'exhale_seconds' => $exhale,
                'hold_out_seconds' => $holdOut,
                'sort_order' => $i + 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('breathing_patterns');
    }
};

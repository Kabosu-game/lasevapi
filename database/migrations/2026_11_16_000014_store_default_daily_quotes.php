<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Phrases du jour : les trois phrases jusqu'ici écrites en dur (dans l'app et dans l'API)
 * deviennent une entrée « Phrases du Jour » modifiable dans l'admin. Rien n'est ajouté
 * si l'admin a déjà programmé des phrases.
 */
return new class extends Migration
{
    public function up(): void
    {
        $today = now()->toDateString();
        $configured = DB::table('daily_quotes')
            ->where('is_active', true)
            ->where('quote_date', '<=', $today)
            ->exists();
        if ($configured || DB::table('daily_quotes')->where('quote_date', $today)->exists()) {
            return;
        }

        DB::table('daily_quotes')->insert([
            'quote_1' => 'La paix intérieure commence par le sourire de l\'âme.',
            'quote_2' => 'Chaque jour est une nouvelle opportunité de grandir.',
            'quote_3' => 'La gratitude transforme ce que nous avons en suffisance.',
            'quote_date' => $today,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Les phrases restent : elles appartiennent désormais à l'admin.
    }
};

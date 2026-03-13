<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Ajoute le champ email (saisi dans le formulaire, utilisé pour Stripe).
     */
    public function up(): void
    {
        Schema::table('food_comfort_forms', function (Blueprint $table) {
            $table->string('email')->nullable()->after('last_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('food_comfort_forms', function (Blueprint $table) {
            $table->dropColumn('email');
        });
    }
};

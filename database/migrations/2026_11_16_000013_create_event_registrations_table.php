<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ÉVÉNEMENTS : inscriptions envoyées depuis l'app (auparavant simulées) et
 * informations pratiques gérées dans l'admin (durée, contact).
 * L'ancienne table event_participants (une seule inscription possible par événement)
 * n'est pas utilisée ; elle est laissée en place.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('phone', 40);
            $table->timestamps();
            $table->index(['event_id', 'phone']);
        });

        Schema::table('events', function (Blueprint $table) {
            if (!Schema::hasColumn('events', 'duration')) {
                $table->string('duration', 100)->nullable()->after('location');
            }
            if (!Schema::hasColumn('events', 'contact')) {
                $table->string('contact')->nullable()->after('duration');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_registrations');
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['duration', 'contact']);
        });
    }
};

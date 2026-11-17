<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Code de transfert : permet de retrouver son compte (tirages, défis, séances)
 * sur un nouveau téléphone.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('transfer_code', 16)->nullable()->unique()->after('device_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['transfer_code']);
            $table->dropColumn('transfer_code');
        });
    }
};

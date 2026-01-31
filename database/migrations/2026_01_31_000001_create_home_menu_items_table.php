<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique(); // affirmation, meditation, articles, gratitude, events, retreats, cuisine
            $table->string('name');
            $table->string('image')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_menu_items');
    }
};

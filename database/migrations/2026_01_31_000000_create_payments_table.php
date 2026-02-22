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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('retreat_plan_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('EUR');
            $table->string('method')->default('stripe'); // stripe ou paypal
            $table->string('status')->default('pending'); // pending, completed, failed, refunded
            $table->string('transaction_id')->unique();
            $table->string('payment_intent_id')->nullable(); // Pour Stripe
            $table->string('paypal_order_id')->nullable(); // Pour PayPal
            $table->json('metadata')->nullable(); // Données additionnelles
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('retreat_plan_id');
            $table->index('status');
            $table->index('method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

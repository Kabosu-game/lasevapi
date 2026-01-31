<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\RetreatPlan;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected RetreatPlan $retreatPlan;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer un utilisateur de test
        $this->user = User::factory()->create();

        // Créer un plan de retraite de test
        $this->retreatPlan = RetreatPlan::create([
            'title' => 'Plan Test',
            'description' => 'Plan de test',
            'duration_days' => 7,
            'price' => 2000,
            'status' => 'available',
            'features' => json_encode(['Feature 1', 'Feature 2']),
            'tags' => json_encode(['Test', 'Payment']),
            'services' => json_encode(['Service 1']),
        ]);
    }

    /** @test */
    public function can_create_stripe_payment_intent()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/payments/stripe/create-payment-intent', [
                'retreat_plan_id' => $this->retreatPlan->id,
                'payment_method' => 'stripe',
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'client_secret',
            'payment_intent_id',
            'amount',
            'retreat_plan',
            'status',
        ]);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('amount', 2000);

        // Vérifier que le paiement a été enregistré en attente
        $this->assertDatabaseHas('payments', [
            'user_id' => $this->user->id,
            'retreat_plan_id' => $this->retreatPlan->id,
            'status' => 'pending',
            'payment_method' => 'stripe',
        ]);
    }

    /** @test */
    public function cannot_create_payment_for_plan_without_price()
    {
        $planNoPrice = RetreatPlan::create([
            'title' => 'Plan Sans Prix',
            'description' => 'Plan sans prix',
            'duration_days' => 7,
            'price' => null,
            'status' => 'on_request',
            'features' => json_encode([]),
            'tags' => json_encode([]),
            'services' => json_encode([]),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/payments/stripe/create-payment-intent', [
                'retreat_plan_id' => $planNoPrice->id,
                'payment_method' => 'stripe',
            ]);

        $response->assertStatus(400);
        $response->assertJsonPath('status', 'error');
    }

    /** @test */
    public function requires_authentication_for_payment_routes()
    {
        $response = $this->postJson('/api/payments/stripe/create-payment-intent', [
            'retreat_plan_id' => $this->retreatPlan->id,
            'payment_method' => 'stripe',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function can_retrieve_payment_history()
    {
        // Créer quelques paiements
        Payment::factory()
            ->count(3)
            ->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/payments/history');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'payments' => [
                'data' => [
                    '*' => [
                        'id',
                        'retreat_plan_id',
                        'user_id',
                        'amount',
                        'currency',
                        'status',
                        'payment_method',
                        'transaction_id',
                    ],
                ],
            ],
            'status',
        ]);
    }

    /** @test */
    public function can_retrieve_specific_payment()
    {
        $payment = Payment::factory()
            ->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/payments/{$payment->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('payment.id', $payment->id);
    }

    /** @test */
    public function cannot_retrieve_other_users_payment()
    {
        $otherUser = User::factory()->create();
        $payment = Payment::factory()
            ->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/payments/{$payment->id}");

        $response->assertStatus(404);
    }

    /** @test */
    public function validates_stripe_payment_intent_parameters()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/payments/stripe/create-payment-intent', [
                // Pas de retreat_plan_id
                'payment_method' => 'stripe',
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function validates_paypal_order_parameters()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/payments/paypal/create-order', [
                // Pas de retreat_plan_id
                'payment_method' => 'paypal',
            ]);

        $response->assertStatus(422);
    }
}

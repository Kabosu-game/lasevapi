<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Payment;
use App\Models\User;
use App\Models\RetreatPlan;
use Carbon\Carbon;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::where('role', 'user')->take(3)->get();
        $retreatPlans = RetreatPlan::all();
        
        if ($users->isEmpty() || $retreatPlans->isEmpty()) {
            $this->command->warn('Aucun utilisateur ou plan de retraite trouvé. Veuillez d\'abord exécuter UserSeeder et RetreatPlanSeeder.');
            return;
        }

        $statuses = ['pending', 'completed', 'failed', 'cancelled'];
        $paymentMethods = ['card', 'mobile_money', 'bank_transfer', 'cash'];

        foreach ($users as $index => $user) {
            // Chaque utilisateur a 1-2 paiements
            $plan = $retreatPlans->random();
            
            $status = $index === 0 ? 'completed' : ($index === 1 ? 'pending' : 'completed');
            $paidAt = $status === 'completed' ? Carbon::now()->subDays(rand(1, 30)) : null;

            Payment::create([
                'retreat_plan_id' => $plan->id,
                'user_id' => $user->id,
                'amount' => $plan->price ?? rand(50000, 200000),
                'currency' => 'EUR',
                'status' => $status,
                'payment_method' => $paymentMethods[array_rand($paymentMethods)],
                'transaction_id' => 'TXN-' . strtoupper(uniqid()),
                'paid_at' => $paidAt,
            ]);

            // Certains utilisateurs ont plusieurs paiements
            if ($index === 0 && $retreatPlans->count() > 1) {
                $plan2 = $retreatPlans->where('id', '!=', $plan->id)->first();
                Payment::create([
                    'retreat_plan_id' => $plan2->id,
                    'user_id' => $user->id,
                    'amount' => $plan2->price ?? rand(50000, 200000),
                    'currency' => 'EUR',
                    'status' => 'completed',
                    'payment_method' => 'card',
                    'transaction_id' => 'TXN-' . strtoupper(uniqid()),
                    'paid_at' => Carbon::now()->subMonths(2),
                ]);
            }
        }
    }
}


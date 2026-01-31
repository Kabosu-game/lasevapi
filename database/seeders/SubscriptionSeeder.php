<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;

class SubscriptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::where('role', 'user')->take(5)->get();
        
        if ($users->isEmpty()) {
            $this->command->warn('Aucun utilisateur trouvé pour créer les abonnements. Veuillez d\'abord exécuter UserSeeder.');
            return;
        }

        foreach ($users as $index => $user) {
            // Certains utilisateurs ont un abonnement mensuel, d'autres annuel
            if ($index % 2 == 0) {
                // Abonnement mensuel
                Subscription::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'plan_type' => 'monthly',
                    ],
                    [
                        'amount' => 5000.00,
                        'currency' => 'XOF',
                        'status' => $index < 2 ? 'active' : 'expired',
                        'started_at' => Carbon::now()->subMonths($index),
                        'expires_at' => $index < 2 ? Carbon::now()->addMonths(1) : Carbon::now()->subDays(5),
                        'payment_method' => 'mobile_money',
                        'transaction_id' => 'TXN-' . strtoupper(uniqid()),
                    ]
                );
            } else {
                // Abonnement annuel
                Subscription::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'plan_type' => 'yearly',
                    ],
                    [
                        'amount' => 50000.00,
                        'currency' => 'XOF',
                        'status' => 'active',
                        'started_at' => Carbon::now()->subMonths(6),
                        'expires_at' => Carbon::now()->addMonths(6),
                        'payment_method' => 'card',
                        'transaction_id' => 'TXN-' . strtoupper(uniqid()),
                    ]
                );
            }
        }
    }
}


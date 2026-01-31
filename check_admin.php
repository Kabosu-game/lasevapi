<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Vérifier si l'admin existe
$admin = User::where('email', 'admin@lasev.com')->first();

if ($admin) {
    echo "✅ Admin existe: {$admin->name} ({$admin->email})\n";
    echo "   Role: {$admin->role}\n";
    
    // Réinitialiser le mot de passe
    $admin->password = Hash::make('password');
    $admin->save();
    echo "✅ Mot de passe réinitialisé à 'password'\n";
} else {
    echo "❌ Admin n'existe pas. Création...\n";
    
    $admin = User::create([
        'name' => 'Administrateur',
        'email' => 'admin@lasev.com',
        'password' => Hash::make('password'),
        'date_of_birth' => '1990-01-01',
        'gender' => 'other',
        'role' => 'admin',
        'device_id' => 'admin-device-' . uniqid(),
        'is_premium' => true,
        'premium_expires_at' => now()->addYears(10),
        'email_verified_at' => now(),
    ]);
    
    echo "✅ Admin créé: {$admin->name} ({$admin->email})\n";
    echo "   Mot de passe: password\n";
}

echo "\n📋 Identifiants de connexion:\n";
echo "   Email: admin@lasev.com\n";
echo "   Password: password\n";



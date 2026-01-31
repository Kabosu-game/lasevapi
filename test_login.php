<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

$email = 'admin@lasev.com';
$password = 'password';

$user = User::where('email', $email)->first();

if (!$user) {
    echo "❌ Utilisateur non trouvé\n";
    exit(1);
}

echo "✅ Utilisateur trouvé: {$user->name} ({$user->email})\n";
echo "   Role: {$user->role}\n\n";

// Vérifier le mot de passe
$passwordHash = $user->password;
echo "🔍 Vérification du mot de passe...\n";
echo "   Hash en DB: " . substr($passwordHash, 0, 20) . "...\n";

// Test avec Hash::check
$check1 = Hash::check($password, $passwordHash);
echo "   Hash::check('password', hash): " . ($check1 ? '✅ OK' : '❌ ÉCHEC') . "\n";

// Test avec Auth::attempt
$credentials = ['email' => $email, 'password' => $password];
$check2 = Auth::attempt($credentials);
echo "   Auth::attempt: " . ($check2 ? '✅ OK' : '❌ ÉCHEC') . "\n";

if (!$check1 || !$check2) {
    echo "\n⚠️  Le mot de passe ne correspond pas. Réinitialisation...\n";
    $user->password = Hash::make($password);
    $user->save();
    echo "✅ Mot de passe réinitialisé\n";
    
    // Retester
    $user->refresh();
    $check3 = Hash::check($password, $user->password);
    echo "   Nouveau hash::check: " . ($check3 ? '✅ OK' : '❌ ÉCHEC') . "\n";
    
    $check4 = Auth::attempt($credentials);
    echo "   Nouveau Auth::attempt: " . ($check4 ? '✅ OK' : '❌ ÉCHEC') . "\n";
}

echo "\n📋 Identifiants:\n";
echo "   Email: {$email}\n";
echo "   Password: {$password}\n";



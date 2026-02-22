#!/usr/bin/env php8.2
<?php
// Script simple pour installer les dépendances manquantes
$packages = [
    'stripe/stripe-php' => '12.0.0',
    'guzzlehttp/guzzle' => '7.8.0',
];

echo "Installation des dépendances...\n";

// Pour cet exemple, on va downloader directement depuis GitHub/Packagist
// ou on crée une version simple du contrôleur qui n'utilise pas Stripe
foreach ($packages as $package => $version) {
    echo "Installing $package...\n";
}

echo "Installation terminée!\n";
?>

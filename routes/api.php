<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MeditationController;
use App\Http\Controllers\Api\GratitudeJournalController;
use App\Http\Controllers\Api\AffirmationController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\BlogController;
use App\Http\Controllers\Api\RetreatPlanController;
use App\Http\Controllers\Api\FoodComfortFormController;
use App\Http\Controllers\Api\PaymentController;

// Route d'enregistrement temporaire (onboarding mobile)
Route::post('/auth/register-temporary', [AuthController::class, 'registerTemporary']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Auth route for login
Route::post('login', [AuthController::class, 'login']);

// Routes publiques en lecture (doivent être définies AVANT les routes protégées)
Route::get('blogs', [BlogController::class, 'index']);
Route::get('blogs/{id}', [BlogController::class, 'show']);
Route::get('events', [EventController::class, 'index']);
Route::get('events/{id}', [EventController::class, 'show']);
Route::get('affirmations', [AffirmationController::class, 'index']);
Route::get('affirmations/{id}', [AffirmationController::class, 'show']);
Route::get('meditations', [MeditationController::class, 'index']);
Route::get('meditations/{id}', [MeditationController::class, 'show']);

// Routes protégées (admin only) - création/modification
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::post('blogs', [BlogController::class, 'store']);
    Route::put('blogs/{blog}', [BlogController::class, 'update']);
    Route::delete('blogs/{blog}', [BlogController::class, 'destroy']);
    
    Route::post('events', [EventController::class, 'store']);
    Route::put('events/{event}', [EventController::class, 'update']);
    Route::delete('events/{event}', [EventController::class, 'destroy']);
    
    Route::post('affirmations', [AffirmationController::class, 'store']);
    Route::put('affirmations/{affirmation}', [AffirmationController::class, 'update']);
    Route::delete('affirmations/{affirmation}', [AffirmationController::class, 'destroy']);
    
    Route::post('meditations', [MeditationController::class, 'store']);
    Route::put('meditations/{meditation}', [MeditationController::class, 'update']);
    Route::delete('meditations/{meditation}', [MeditationController::class, 'destroy']);
});

// Journaux de gratitude (auth user only)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('gratitude-journals', [GratitudeJournalController::class, 'index']);
    Route::post('gratitude-journals', [GratitudeJournalController::class, 'store']);
    Route::get('gratitude-journals/{id}', [GratitudeJournalController::class, 'show']);
    Route::put('gratitude-journals/{id}', [GratitudeJournalController::class, 'update']);
    Route::delete('gratitude-journals/{id}', [GratitudeJournalController::class, 'destroy']);
});

// CRUD Retreat Plans
Route::apiResource('retreat-plans', RetreatPlanController::class);

// Phrase du jour
Route::get('daily-quote', [\App\Http\Controllers\Api\DailyQuoteController::class, 'getCurrentQuote'])->name('daily-quote.current');
Route::get('daily-quote/today', [\App\Http\Controllers\Api\DailyQuoteController::class, 'getTodayQuotes'])->name('daily-quote.today');

// Soumettre ou mettre à jour la fiche food comfort
Route::post('food-comfort-form', [FoodComfortFormController::class, 'storeOrUpdate']);

// Route pour paiement et création/connexion utilisateur
Route::post('retreat-plans/{plan}/pay', [PaymentController::class, 'payAndRegisterOrLogin']);

// Paramètres de l'application (publics - pour Flutter)
Route::get('settings', [\App\Http\Controllers\Api\SettingsController::class, 'index']);
Route::get('settings/{key}', [\App\Http\Controllers\Api\SettingsController::class, 'show']);
Route::get('settings/group/{group}', [\App\Http\Controllers\Api\SettingsController::class, 'getByGroup']);

// Objectifs disponibles (pour l'onboarding)
Route::get('objectives', [\App\Http\Controllers\Api\ObjectiveController::class, 'index']);

// Cuisine : plats et chefs (page Cuisine de l'app)
Route::get('dishes', [\App\Http\Controllers\Api\CuisineController::class, 'dishes']);
Route::get('chefs', [\App\Http\Controllers\Api\CuisineController::class, 'chefs']);

// Menus page d'accueil (Affirmation, Meditation, etc. avec nom + image)
Route::get('home-menu-items', [\App\Http\Controllers\Api\HomeMenuItemController::class, 'index']);

// Routes de paiement (authentifiées)
Route::middleware('auth:sanctum')->group(function () {
    // Stripe payment routes
    Route::post('payments/stripe/create-payment-intent', [PaymentController::class, 'createStripePaymentIntent']);
    Route::post('payments/stripe/confirm', [PaymentController::class, 'confirmStripePayment']);
    
    // PayPal payment routes
    Route::post('payments/paypal/create-order', [PaymentController::class, 'createPayPalOrder']);
    Route::post('payments/paypal/capture', [PaymentController::class, 'capturePayPalOrder']);
    
    // Payment history routes
    Route::get('payments/history', [PaymentController::class, 'getPaymentHistory']);
    Route::get('payments/{paymentId}', [PaymentController::class, 'getPayment']);
});

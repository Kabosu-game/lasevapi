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
use App\Http\Controllers\Api\UserApiController;

// Route d'enregistrement temporaire (onboarding mobile)
Route::post('/auth/register-temporary', [AuthController::class, 'registerTemporary']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Auth routes (login, logout)
Route::post('login', [UserApiController::class, 'login']);
Route::post('logout', [UserApiController::class, 'logout'])->middleware('auth:sanctum');

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

// ==================== ROUTES DE PAIEMENT ====================

// Routes publiques de paiement (Stripe & PayPal - in-app)
Route::post('create-stripe-payment-intent', [PaymentController::class, 'createStripePaymentIntent']);
Route::post('capture-stripe-payment', [PaymentController::class, 'captureStripePayment']);
Route::post('create-paypal-order', [PaymentController::class, 'createPayPalOrder']);
Route::post('approve-paypal-order', [PaymentController::class, 'approvePayPalOrder']);
Route::post('record-payment', [PaymentController::class, 'recordPayment']);

// Routes de retour PayPal (webhooks/redirects)
Route::get('payment/paypal-return', [PaymentController::class, 'paypalReturn'])->name('payment.paypal-return');
Route::get('payment/paypal-cancel', [PaymentController::class, 'paypalCancel'])->name('payment.paypal-cancel');

// Routes authentifiées pour l'historique de paiement
Route::middleware('auth:sanctum')->group(function () {
    Route::get('user/payments', [UserApiController::class, 'getPayments']);
    Route::get('user/payment-stats', [UserApiController::class, 'getPaymentStats']);
});

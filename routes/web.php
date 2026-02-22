<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MeditationController;
use App\Http\Controllers\Admin\AffirmationController;
use App\Http\Controllers\Admin\AffirmationCategoryController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RetreatPlanController;
use App\Http\Controllers\Admin\FoodComfortFormController;
use App\Http\Controllers\Admin\DailyQuoteController;
use App\Http\Controllers\Admin\DishController;
use App\Http\Controllers\Admin\ChefController;
use App\Http\Controllers\Admin\HomeMenuItemController;
use App\Http\Controllers\Admin\PaymentAdminController;
use App\Http\Controllers\Admin\PaymentSettingsController;

// Favicon (évite le 404 sur la page login)
Route::get('/favicon.ico', function () {
    $path = public_path('favicon.ico');
    if (!file_exists($path)) {
        abort(204);
    }
    return response()->file($path, ['Content-Type' => 'image/x-icon']);
});

// Routes publiques — redirection vers l'admin
Route::get('/', function () {
    return redirect()->route('admin.login');
});

// Servir les fichiers storage (évite 403 avec php artisan serve sous Windows)
// Utiliser /serve-storage/... pour que la requête passe par Laravel
Route::get('/serve-storage/{path}', function (string $path) {
    $path = str_replace('..', '', $path);
    if (!Storage::disk('public')->exists($path)) {
        abort(404);
    }
    $fullPath = Storage::disk('public')->path($path);
    $mimeType = mime_content_type($fullPath) ?: 'application/octet-stream';
    return response()->file($fullPath, ['Content-Type' => $mimeType]);
})->where('path', '.*')->name('storage.serve');

// Route de redirection pour le middleware auth par défaut
Route::get('/login', function () {
    return redirect()->route('admin.login');
})->name('login');

// Routes admin - Authentification
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login']);
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

    // Routes protégées par authentification admin
    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        
        // CRUD Méditations
        Route::resource('meditations', MeditationController::class);
        
        // CRUD Affirmations
        Route::resource('affirmations', AffirmationController::class);
        // Catégories d'affirmations (ajouter / supprimer)
        Route::resource('affirmation-categories', AffirmationCategoryController::class)->except(['show']);
        
        // CRUD Événements
        Route::resource('events', EventController::class);
        
        // CRUD Blogs
        Route::resource('blogs', BlogController::class);
        
        // CRUD Utilisateurs
        Route::resource('users', UserController::class);
        
        // CRUD Plans de retraite
        Route::resource('retreat-plans', RetreatPlanController::class);

        // Gestion des paiements
        Route::prefix('payments')->name('payments.')->group(function () {
            Route::get('/', [PaymentAdminController::class, 'index'])->name('index');
            Route::get('/export', [PaymentAdminController::class, 'export'])->name('export');
            Route::get('/statistics', [PaymentAdminController::class, 'statistics'])->name('statistics');
            Route::get('/{id}', [PaymentAdminController::class, 'show'])->name('show');
            Route::post('/{id}/status', [PaymentAdminController::class, 'updateStatus'])->name('update-status');
            Route::post('/{id}/refund', [PaymentAdminController::class, 'refund'])->name('refund');
            Route::get('/user/{userId}', [PaymentAdminController::class, 'userStats'])->name('user-stats');
        });

        // Cuisine : Plats et Chefs
        Route::resource('dishes', DishController::class)->except(['show']);
        Route::resource('chefs', ChefController::class)->except(['show']);
        
        // Formulaires de confort alimentaire
        Route::get('food-comfort-forms', [FoodComfortFormController::class, 'index'])->name('food-comfort-forms.index');
        Route::get('food-comfort-forms/{id}', [FoodComfortFormController::class, 'show'])->name('food-comfort-forms.show');
        Route::delete('food-comfort-forms/{id}', [FoodComfortFormController::class, 'destroy'])->name('food-comfort-forms.destroy');
        
        // Phrases du jour
        Route::get('daily-quotes', [DailyQuoteController::class, 'index'])->name('daily-quotes.index');
        Route::get('daily-quotes/create', [DailyQuoteController::class, 'create'])->name('daily-quotes.create');
        Route::post('daily-quotes', [DailyQuoteController::class, 'store'])->name('daily-quotes.store');
        Route::get('daily-quotes/{id}/edit', [DailyQuoteController::class, 'edit'])->name('daily-quotes.edit');
        Route::put('daily-quotes/{id}', [DailyQuoteController::class, 'update'])->name('daily-quotes.update');
        Route::delete('daily-quotes/{id}', [DailyQuoteController::class, 'destroy'])->name('daily-quotes.destroy');
        
        // Menus page d'accueil (images par section : Affirmation, Meditation, etc.)
        Route::get('home-menu-items', [HomeMenuItemController::class, 'index'])->name('home-menu-items.index');
        Route::get('home-menu-items/{id}/edit', [HomeMenuItemController::class, 'edit'])->name('home-menu-items.edit');
        Route::put('home-menu-items/{id}', [HomeMenuItemController::class, 'update'])->name('home-menu-items.update');
        
        // CMS - Gestion des paramètres de l'application
        Route::prefix('cms')->name('cms.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('index');
            Route::post('/settings/{key}', [\App\Http\Controllers\Admin\SettingsController::class, 'update'])->name('update');
            Route::post('/settings/bulk', [\App\Http\Controllers\Admin\SettingsController::class, 'bulkUpdate'])->name('bulk-update');
            Route::delete('/settings/{key}/file', [\App\Http\Controllers\Admin\SettingsController::class, 'deleteFile'])->name('delete-file');
            Route::post('/settings/{key}/reset', [\App\Http\Controllers\Admin\SettingsController::class, 'reset'])->name('reset');
        });

        // Configuration des Paiements
        Route::prefix('payment-settings')->name('payment-settings.')->group(function () {
            Route::get('/', [PaymentSettingsController::class, 'index'])->name('index');
            Route::post('/', [PaymentSettingsController::class, 'update'])->name('update');
            Route::post('/test-stripe', [PaymentSettingsController::class, 'testStripe'])->name('test-stripe');
            Route::post('/test-paypal', [PaymentSettingsController::class, 'testPayPal'])->name('test-paypal');
        });
    });
});

// Fallback : toute URL non reconnue redirige vers la connexion admin (évite la 404)
Route::fallback(function () {
    return redirect()->route('admin.login');
});

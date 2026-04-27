<?php

use App\Http\Controllers\Admin\BudgetAnnuelController;
use App\Http\Controllers\Admin\ComiteArbitrageController;
use App\Http\Controllers\Admin\EntrepriseController;
use App\Http\Controllers\Admin\GroupeController;
use App\Http\Controllers\Admin\NiveauValidationController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\ValidateurController;
use App\Http\Controllers\ComptaController;
use App\Http\Controllers\DapPdfController;
use App\Http\Controllers\DocumentationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpressionBesoinController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaiementController;
use App\Http\Controllers\SessionArbitrageController;
use App\Http\Controllers\ValidationDapController;
use App\Http\Controllers\VoteArbitrageController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/df/export', [DashboardController::class, 'exportDfDashboard'])
        ->middleware('role:validateur,admin')
        ->name('dashboard.df.export');

    // Notifications
    Route::get('/notifications/poll', [NotificationController::class, 'poll'])->name('notifications.poll');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::get('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');

    // Expressions de besoin (employés + validateur + admin)
    Route::middleware('role:employe,validateur,admin')->group(function () {
        Route::get('/expressions-besoin', [ExpressionBesoinController::class, 'index'])->name('expressions-besoin.index');
        Route::get('/expressions-besoin/create', [ExpressionBesoinController::class, 'create'])->name('expressions-besoin.create');
        Route::post('/expressions-besoin', [ExpressionBesoinController::class, 'store'])->name('expressions-besoin.store');
        Route::get('/expressions-besoin/{expressionsBesoin}', [ExpressionBesoinController::class, 'show'])->name('expressions-besoin.show');
        Route::get('/attachments/eb/{attachment}/download', [ExpressionBesoinController::class, 'downloadAttachment'])->name('eb-attachments.download');
    });

    // Compta : validation EB → DAP
    Route::middleware('role:validateur,admin')->prefix('compta')->name('compta.')->group(function () {
        Route::get('/', [ComptaController::class, 'index'])->name('index');
        Route::get('/{expression}', [ComptaController::class, 'show'])->name('show');
        Route::post('/{expression}/valider', [ComptaController::class, 'valider'])->name('valider');
        Route::post('/{expression}/rejeter', [ComptaController::class, 'rejeter'])->name('rejeter');
    });

    // Validations DAP (DF, DG, PDG, admin)
    Route::middleware('role:validateur,admin')->prefix('validations-dap')->name('validations-dap.')->group(function () {
        Route::get('/', [ValidationDapController::class, 'index'])->name('index');
        Route::get('/toutes', [ValidationDapController::class, 'toutes'])->name('toutes');
        Route::get('/export', [ValidationDapController::class, 'exportExcel'])->name('export');
        Route::post('/{dap}/approuver', [ValidationDapController::class, 'approuver'])->name('approuver');
        Route::post('/{dap}/rejeter', [ValidationDapController::class, 'rejeter'])->name('rejeter');
    });

    // Fiche DAP accessible aussi aux employés (lecture seule de leurs propres DAPs)
    Route::middleware('role:employe,validateur,admin')->get('/validations-dap/{dap}', [ValidationDapController::class, 'show'])->name('validations-dap.show');

    // Export PDF DAP
    Route::middleware('role:validateur,admin')->get('/dap/{dap}/pdf', [DapPdfController::class, 'download'])->name('dap.pdf');

    // Documentation PDF
    Route::middleware('role:validateur,admin')->get('/documentation/df', [DocumentationController::class, 'df'])->name('documentation.df');
    Route::middleware('role:admin')->get('/documentation/facture', [DocumentationController::class, 'facture'])->name('documentation.facture');

    // Paiements (admin + compta)
    Route::middleware('role:validateur,admin')->group(function () {
        Route::get('/paiements', [PaiementController::class, 'index'])->name('paiements.index');
        Route::post('/paiements/{dap}', [PaiementController::class, 'store'])->name('paiements.store');
    });

    // Arbitrage
    Route::prefix('arbitrage')->name('arbitrage.')->group(function () {
        Route::get('/sessions', [SessionArbitrageController::class, 'index'])->name('sessions.index');
        Route::get('/sessions/{session}', [SessionArbitrageController::class, 'show'])->name('sessions.show');
        Route::post('/sessions/{session}/voter', [VoteArbitrageController::class, 'store'])->name('sessions.voter');

        Route::middleware('role:admin')->group(function () {
            Route::get('/sessions/create', [SessionArbitrageController::class, 'create'])->name('sessions.create');
            Route::post('/sessions', [SessionArbitrageController::class, 'store'])->name('sessions.store');
            Route::post('/sessions/{session}/ouvrir-vote', [SessionArbitrageController::class, 'ouvrirVote'])->name('sessions.ouvrir-vote');
            Route::post('/sessions/{session}/finaliser', [SessionArbitrageController::class, 'finaliser'])->name('sessions.finaliser');
            Route::delete('/sessions/{session}', [SessionArbitrageController::class, 'destroy'])->name('sessions.destroy');
        });
    });

    // Administration
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('groupes', GroupeController::class)->except(['show']);
        Route::resource('entreprises', EntrepriseController::class)->except(['show']);
        Route::resource('users', AdminUserController::class)->except(['show']);
        Route::resource('budgets-annuels', BudgetAnnuelController::class)->except(['show']);
        Route::resource('validateurs', ValidateurController::class)->only(['index', 'store', 'destroy']);
        Route::get('niveaux-validation', [NiveauValidationController::class, 'index'])->name('niveaux-validation.index');
        Route::patch('niveaux-validation/{niveauValidation}', [NiveauValidationController::class, 'update'])->name('niveaux-validation.update');
        Route::resource('comites-arbitrage', ComiteArbitrageController::class)->except(['show']);
    });
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';

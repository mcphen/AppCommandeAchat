<?php

use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\ValidationLevelController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\ValidationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Commandes (Demandeur + Admin)
    Route::middleware('role:demandeur,admin')->group(function () {
        Route::resource('purchase-orders', PurchaseOrderController::class);
        Route::post('purchase-orders/{purchase_order}/submit', [PurchaseOrderController::class, 'submit'])
            ->name('purchase-orders.submit');
    });

    // Téléchargement de pièces jointes (tous les rôles authentifiés)
    Route::get('attachments/{attachment}/download', [AttachmentController::class, 'download'])
        ->name('attachments.download');

    // Validations (Validateur + Admin)
    Route::middleware('role:validateur,admin')->group(function () {
        Route::get('/validations', [ValidationController::class, 'index'])->name('validations.index');
        Route::get('/validations/{purchase_order}', [ValidationController::class, 'show'])->name('validations.show');
        Route::post('/validations/{purchase_order}/approve', [ValidationController::class, 'approve'])->name('validations.approve');
        Route::post('/validations/{purchase_order}/reject', [ValidationController::class, 'reject'])->name('validations.reject');
    });

    // Administration (Admin uniquement)
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', AdminUserController::class)->except(['show']);
        Route::resource('validation-levels', ValidationLevelController::class)->except(['show']);
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

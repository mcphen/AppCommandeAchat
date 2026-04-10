<?php

use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\BoutiqueController;
use App\Http\Controllers\Admin\ValidationLevelController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
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

    // Notifications (tous rôles)
    Route::get('/notifications/poll', [NotificationController::class, 'poll'])->name('notifications.poll');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::get('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');

    // Téléchargement PDF et pièces jointes (tous les rôles authentifiés)
    Route::get('purchase-orders/{purchase_order}/pdf', [PurchaseOrderController::class, 'downloadPdf'])
        ->name('purchase-orders.pdf');
    Route::get('purchase-orders/export/{format}', [PurchaseOrderController::class, 'export'])
        ->name('purchase-orders.export')
        ->where('format', 'csv|excel|pdf');
    Route::get('attachments/{attachment}/download', [AttachmentController::class, 'download'])
        ->name('attachments.download');

    // Validations (Validateur + Admin)
    Route::middleware('role:validateur,admin')->group(function () {
        Route::get('/validations', [ValidationController::class, 'index'])->name('validations.index');
        Route::get('/validations/{purchase_order}', [ValidationController::class, 'show'])->name('validations.show');
        Route::post('/validations/{purchase_order}/approve', [ValidationController::class, 'approve'])->name('validations.approve');
        Route::post('/validations/{purchase_order}/reject', [ValidationController::class, 'reject'])->name('validations.reject');

        // Audit & historique
        Route::get('/audit', [AuditController::class, 'index'])->name('audit.index');
        Route::get('/audit/export/{format}', [AuditController::class, 'export'])->name('audit.export')->where('format', 'pdf|excel');
    });

    // Administration (Admin uniquement)
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('boutiques', BoutiqueController::class)->except(['show']);
        Route::resource('users', AdminUserController::class)->except(['show']);
        Route::resource('validation-levels', ValidationLevelController::class)->except(['show']);
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

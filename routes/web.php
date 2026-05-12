<?php

use App\Http\Controllers\Admin\AccountingController;
use App\Http\Controllers\DecaissementController;
use App\Http\Controllers\CaisseController;
use App\Http\Controllers\TransactionEpargneController;
use App\Http\Controllers\PretController;
use App\Http\Controllers\PretValidationController;
use App\Http\Controllers\RemboursementPretController;
use App\Http\Controllers\MonCompteController;
use App\Http\Controllers\Admin\AgentController;
use App\Http\Controllers\Admin\AppSettingController;
use App\Http\Controllers\ChecklistController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\BoutiqueController;
use App\Http\Controllers\Admin\BudgetController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\FournisseurArticleController;
use App\Http\Controllers\Admin\FournisseurController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\WhatsAppTestController;
use App\Http\Controllers\Admin\ValidationLevelController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\DelegationController;
use App\Http\Controllers\OrderCommentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\ReceptionController;
use App\Http\Controllers\ValidationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Commandes : création, consultation, édition, soumission (Demandeur + Validateur + Admin)
    Route::middleware('role:demandeur,validateur,admin')->group(function () {
        Route::resource('purchase-orders', PurchaseOrderController::class);
        Route::post('purchase-orders/{purchase_order}/submit', [PurchaseOrderController::class, 'submit'])
            ->name('purchase-orders.submit');
        Route::post('purchase-orders/{purchase_order}/cancel', [PurchaseOrderController::class, 'cancel'])
            ->name('purchase-orders.cancel');
    });

    // Commandes : confirmation d'ordre et réceptions (Demandeur + Admin uniquement)
    Route::middleware('role:demandeur,admin')->group(function () {
        Route::post('purchase-orders/{purchase_order}/mark-ordered', [PurchaseOrderController::class, 'markOrdered'])
            ->name('purchase-orders.mark-ordered');

        Route::post('purchase-orders/{purchase_order}/receptions', [ReceptionController::class, 'store'])
            ->name('purchase-orders.receptions.store');
        Route::patch('purchase-orders/{purchase_order}/receptions/{reception}/invoice', [ReceptionController::class, 'updateInvoice'])
            ->name('purchase-orders.receptions.invoice');

        // Tableau de bord réceptions / livraisons
        Route::get('receptions', [ReceptionController::class, 'index'])
            ->name('receptions.index');
    });

    // Commentaires & discussion (tous rôles authentifiés)
    Route::post('purchase-orders/{purchase_order}/comments', [OrderCommentController::class, 'store'])
        ->name('order-comments.store');
    Route::get('purchase-orders/{purchase_order}/comments/{comment}/download', [OrderCommentController::class, 'download'])
        ->name('order-comments.download');
    Route::delete('purchase-orders/{purchase_order}/comments/{comment}', [OrderCommentController::class, 'destroy'])
        ->name('order-comments.destroy');

    // Onboarding & checklist
    Route::post('/onboarding/complete', [OnboardingController::class, 'complete'])->name('onboarding.complete');
    Route::post('/checklist/dismiss',   [ChecklistController::class, 'dismiss'])->name('checklist.dismiss');

    // Comparaison de prix fournisseurs pour un article (tous rôles)
    Route::get('/articles/{article}/prix-fournisseurs', [FournisseurArticleController::class, 'compareByArticle'])->name('articles.prix-fournisseurs');

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

        // Délégations
        Route::get('/delegations', [DelegationController::class, 'index'])->name('delegations.index');
        Route::post('/delegations', [DelegationController::class, 'store'])->name('delegations.store');
        Route::patch('/delegations/{delegation}/toggle', [DelegationController::class, 'toggle'])->name('delegations.toggle');
        Route::delete('/delegations/{delegation}', [DelegationController::class, 'destroy'])->name('delegations.destroy');

        // Audit & historique
        Route::get('/audit', [AuditController::class, 'index'])->name('audit.index');
        Route::get('/audit/export/{format}', [AuditController::class, 'export'])->name('audit.export')->where('format', 'pdf|excel');
    });

    // Mon Compte (Agent)
    Route::middleware('role:agent')->group(function () {
        Route::get('/mon-compte', [MonCompteController::class, 'index'])->name('mon-compte.index');
    });

    // Caisse épargne & prêts (Caissier + Admin)
    Route::middleware('role:caissier,admin')->prefix('caisse')->name('caisse.')->group(function () {
        Route::get('/', [CaisseController::class, 'index'])->name('index');
        Route::get('/agents/{agent}', [CaisseController::class, 'showAgent'])->name('agents.show');
        Route::post('/agents/{agent}/transactions', [TransactionEpargneController::class, 'store'])->name('transactions.store');
        Route::get('/prets', [PretController::class, 'index'])->name('prets.index');
        Route::get('/prets/create', [PretController::class, 'create'])->name('prets.create');
        Route::post('/prets', [PretController::class, 'store'])->name('prets.store');
        Route::get('/prets/{pret}', [PretController::class, 'show'])->name('prets.show');
        Route::post('/prets/{pret}/submit', [PretController::class, 'submit'])->name('prets.submit');
        Route::post('/prets/{pret}/decaisser', [PretController::class, 'decaisser'])->name('prets.decaisser');
        Route::post('/prets/{pret}/remboursements', [RemboursementPretController::class, 'store'])->name('remboursements.store');
    });

    // Validation des prêts (Validateur + Admin)
    Route::middleware('role:validateur,admin')->group(function () {
        Route::get('/pret-validations', [PretValidationController::class, 'index'])->name('pret-validations.index');
        Route::get('/pret-validations/{pret}', [PretValidationController::class, 'show'])->name('pret-validations.show');
        Route::post('/pret-validations/{pret}/approve', [PretValidationController::class, 'approve'])->name('pret-validations.approve');
        Route::post('/pret-validations/{pret}/reject', [PretValidationController::class, 'reject'])->name('pret-validations.reject');
    });

    // Décaissements (Caissier + Admin)
    Route::middleware('role:caissier,admin')->group(function () {
        Route::get('/decaissements', [DecaissementController::class, 'index'])->name('decaissements.index');
        Route::get('/decaissements/{purchase_order}', [DecaissementController::class, 'show'])->name('decaissements.show');
        Route::post('/decaissements/{purchase_order}', [DecaissementController::class, 'store'])->name('decaissements.store');
    });

    // Analytique (Admin uniquement)
    Route::middleware('role:admin')->group(function () {
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
    });

    // Administration (Admin uniquement)
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('boutiques', BoutiqueController::class)->except(['show']);
        Route::resource('users', AdminUserController::class)->except(['show']);
        Route::resource('validation-levels', ValidationLevelController::class)->except(['show']);
        Route::resource('categories', CategoryController::class)->except(['show']);
        // Import fournisseurs (avant resource pour éviter conflit de routes)
        Route::get('fournisseurs/template', [FournisseurController::class, 'downloadTemplate'])->name('fournisseurs.template');
        Route::post('fournisseurs/import/parse', [FournisseurController::class, 'importParse'])->name('fournisseurs.import.parse');
        Route::post('fournisseurs/import/confirm', [FournisseurController::class, 'importConfirm'])->name('fournisseurs.import.confirm');
        Route::resource('fournisseurs', FournisseurController::class);
        // Catalogue de prix par fournisseur
        Route::get('fournisseurs/{fournisseur}/catalogue', [FournisseurArticleController::class, 'index'])->name('fournisseurs.catalogue.index');
        Route::post('fournisseurs/{fournisseur}/catalogue', [FournisseurArticleController::class, 'store'])->name('fournisseurs.catalogue.store');
        Route::put('fournisseurs/{fournisseur}/catalogue/{tarif}', [FournisseurArticleController::class, 'update'])->name('fournisseurs.catalogue.update');
        Route::delete('fournisseurs/{fournisseur}/catalogue/{tarif}', [FournisseurArticleController::class, 'destroy'])->name('fournisseurs.catalogue.destroy');
        // Import CSV/Excel du catalogue
        Route::get('fournisseurs/{fournisseur}/catalogue/template', [FournisseurArticleController::class, 'downloadTemplate'])->name('fournisseurs.catalogue.template');
        Route::post('fournisseurs/{fournisseur}/catalogue/import/parse', [FournisseurArticleController::class, 'importParse'])->name('fournisseurs.catalogue.import.parse');
        Route::post('fournisseurs/{fournisseur}/catalogue/import/confirm', [FournisseurArticleController::class, 'importConfirm'])->name('fournisseurs.catalogue.import.confirm');

        Route::resource('agents', AgentController::class)->except(['show']);
        // Import articles (avant resource pour éviter conflit de routes)
        Route::get('articles/template', [ArticleController::class, 'downloadTemplate'])->name('articles.template');
        Route::post('articles/import/parse', [ArticleController::class, 'importParse'])->name('articles.import.parse');
        Route::post('articles/import/confirm', [ArticleController::class, 'importConfirm'])->name('articles.import.confirm');
        Route::resource('articles', ArticleController::class)->except(['show']);
        Route::resource('budgets', BudgetController::class)->except(['show']);
        Route::get('accounting', [AccountingController::class, 'index'])->name('accounting.index');
        Route::get('accounting/export/{format}', [AccountingController::class, 'export'])->name('accounting.export')->where('format', 'fec|csv');

        // Configuration de l'application
        Route::get('settings', [AppSettingController::class, 'index'])->name('settings.index');
        Route::patch('settings/mail', [AppSettingController::class, 'updateMail'])->name('settings.mail');
        Route::patch('settings/company', [AppSettingController::class, 'updateCompany'])->name('settings.company');
        Route::delete('settings/logo', [AppSettingController::class, 'deleteLogo'])->name('settings.logo.delete');
        Route::post('settings/test-mail', [AppSettingController::class, 'testMail'])->name('settings.test-mail');

        // Test WhatsApp
        Route::get('whatsapp/test', [WhatsAppTestController::class, 'send'])->name('whatsapp.test');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

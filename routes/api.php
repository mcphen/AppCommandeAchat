<?php

use App\Http\Controllers\Api\SageWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/sage/purchase-orders', [SageWebhookController::class, 'store'])
    ->middleware('sage.token')
    ->name('api.sage.purchase-orders.store');

Route::get('/sage/purchase-orders/missing-project', [SageWebhookController::class, 'missingProject'])
    ->middleware('sage.token')
    ->name('api.sage.purchase-orders.missing-project');

Route::patch('/sage/purchase-orders/{numero}/project', [SageWebhookController::class, 'updateProject'])
    ->middleware('sage.token')
    ->name('api.sage.purchase-orders.update-project');

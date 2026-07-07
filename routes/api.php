<?php

use App\Http\Controllers\Api\SageWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/sage/purchase-orders', [SageWebhookController::class, 'store'])
    ->middleware('sage.token')
    ->name('api.sage.purchase-orders.store');

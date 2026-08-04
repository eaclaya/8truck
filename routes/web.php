<?php

use App\Http\Controllers\AcceptQuoteController;
use App\Http\Controllers\PublishShipmentController;
use App\Http\Controllers\ShipmentController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::resource('shipments', ShipmentController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('shipments/{shipment}/publish', PublishShipmentController::class)->name('shipments.publish');
    Route::post('quotes/{quote}/accept', AcceptQuoteController::class)->name('quotes.accept');
});

require __DIR__.'/settings.php';

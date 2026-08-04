<?php

use App\Http\Controllers\AcceptQuoteController;
use App\Http\Controllers\AdvanceJobStatusController;
use App\Http\Controllers\CompleteShipmentController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\LoadController;
use App\Http\Controllers\PublishShipmentController;
use App\Http\Controllers\RateShipmentController;
use App\Http\Controllers\ShipmentController;
use App\Http\Controllers\SubmitQuoteController;
use App\Http\Controllers\TruckController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    // Customer
    Route::resource('shipments', ShipmentController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('shipments/{shipment}/publish', PublishShipmentController::class)->name('shipments.publish');
    Route::post('shipments/{shipment}/complete', CompleteShipmentController::class)->name('shipments.complete');
    Route::post('shipments/{shipment}/ratings', RateShipmentController::class)->name('shipments.rate');
    Route::post('quotes/{quote}/accept', AcceptQuoteController::class)->name('quotes.accept');

    // Transporter
    Route::get('loads', [LoadController::class, 'index'])->name('loads.index');
    Route::get('loads/{shipment}', [LoadController::class, 'show'])->name('loads.show');
    Route::post('loads/{shipment}/quotes', SubmitQuoteController::class)->name('loads.quote');
    Route::get('jobs', [JobController::class, 'index'])->name('jobs.index');
    Route::post('jobs/{shipment}/advance', AdvanceJobStatusController::class)->name('jobs.advance');
    Route::resource('trucks', TruckController::class)->only(['index', 'create', 'store', 'update', 'destroy']);
});

require __DIR__.'/settings.php';

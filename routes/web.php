<?php

use App\Http\Controllers\AcceptQuoteController;
use App\Http\Controllers\AdvanceJobStatusController;
use App\Http\Controllers\CompleteShipmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\LoadController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\OperatingRegionController;
use App\Http\Controllers\PublishShipmentController;
use App\Http\Controllers\RateShipmentController;
use App\Http\Controllers\ShipmentController;
use App\Http\Controllers\SubmitQuoteController;
use App\Http\Controllers\TruckController;
use App\Http\Controllers\UploadProofOfDeliveryController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('google.redirect');
    Route::get('auth/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');
    Route::get('auth/google/complete', [GoogleAuthController::class, 'complete'])->name('google.complete');
    Route::post('auth/google/complete', [GoogleAuthController::class, 'store'])->name('google.store');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('onboarding', OnboardingController::class)->name('onboarding');

    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');
    Route::post('notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');

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
    Route::post('jobs/{shipment}/pod', UploadProofOfDeliveryController::class)->name('jobs.pod');
    Route::resource('trucks', TruckController::class)->only(['index', 'create', 'store', 'update', 'destroy']);
    Route::get('regions', [OperatingRegionController::class, 'index'])->name('regions.index');
    Route::post('regions', [OperatingRegionController::class, 'store'])->name('regions.store');
    Route::delete('regions/{operatingRegion}', [OperatingRegionController::class, 'destroy'])->name('regions.destroy');
    Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
});

require __DIR__.'/settings.php';

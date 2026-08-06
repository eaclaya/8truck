<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DeviceTokenController;
use App\Http\Controllers\Api\V1\DocumentController;
use App\Http\Controllers\Api\V1\JobController;
use App\Http\Controllers\Api\V1\LoadController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\RegionController;
use App\Http\Controllers\Api\V1\ShipmentController;
use App\Http\Controllers\Api\V1\TruckController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('auth/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('auth.login');
    Route::post('auth/google', [AuthController::class, 'google'])->middleware('throttle:10,1')->name('auth.google');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        Route::get('dashboard', DashboardController::class)->name('dashboard');
        Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('password', [ProfileController::class, 'updatePassword'])->name('password.update');
        Route::post('devices', [DeviceTokenController::class, 'store'])->name('devices.store');
        Route::delete('devices', [DeviceTokenController::class, 'destroy'])->name('devices.destroy');

        Route::get('cities', [CatalogController::class, 'cities'])->name('cities');
        Route::get('truck-types', [CatalogController::class, 'truckTypes'])->name('truck-types');

        Route::get('shipments', [ShipmentController::class, 'index'])->name('shipments.index');
        Route::post('shipments', [ShipmentController::class, 'store'])->name('shipments.store');
        Route::get('shipments/{shipment}', [ShipmentController::class, 'show'])->name('shipments.show');
        Route::post('shipments/{shipment}/photos', [ShipmentController::class, 'storePhotos'])->name('shipments.photos');
        Route::post('shipments/{shipment}/publish', [ShipmentController::class, 'publish'])->name('shipments.publish');
        Route::post('shipments/{shipment}/complete', [ShipmentController::class, 'complete'])->name('shipments.complete');
        Route::post('shipments/{shipment}/ratings', [ShipmentController::class, 'rate'])->name('shipments.rate');
        Route::post('quotes/{quote}/accept', [ShipmentController::class, 'acceptQuote'])->name('quotes.accept');

        Route::get('loads', [LoadController::class, 'index'])->name('loads.index');
        Route::get('loads/{shipment}', [LoadController::class, 'show'])->name('loads.show');
        Route::post('loads/{shipment}/quotes', [LoadController::class, 'quote'])->name('loads.quote');

        Route::get('jobs', [JobController::class, 'index'])->name('jobs.index');
        Route::post('jobs/{shipment}/advance', [JobController::class, 'advance'])->name('jobs.advance');
        Route::post('jobs/{shipment}/pod', [JobController::class, 'uploadPod'])->name('jobs.pod');

        Route::apiResource('trucks', TruckController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('regions', [RegionController::class, 'index'])->name('regions.index');
        Route::post('regions', [RegionController::class, 'store'])->name('regions.store');
        Route::delete('regions/{operatingRegion}', [RegionController::class, 'destroy'])->name('regions.destroy');

        Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
        Route::post('documents', [DocumentController::class, 'store'])->name('documents.store');
        Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');

        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');
        Route::post('notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');
    });
});

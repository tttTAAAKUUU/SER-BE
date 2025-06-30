<?php

use App\Http\Controllers\Auth\ServiceProvidersController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\UsersController;
use App\Http\Controllers\ProviderServicesController;
use App\Http\Controllers\ServicesController;
use App\Http\Controllers\ServiceProvider\ProviderServiceRequestsController;
use App\Http\Controllers\User\UserServiceRequestsController;

// Public routes
Route::group(['prefix' => 'services'], function () {
    Route::get('/', [ServicesController::class, 'index']);
    Route::get('/{id}', [ServicesController::class, 'show']);
    Route::put('/{id}', [ServicesController::class, 'update']);
    Route::delete('/{id}', [ServicesController::class, 'destroy']);
});

Route::group(['prefix' => 'users'], function () {
    Route::post('/register', [UsersController::class, 'register']);
    Route::post('/login', [UsersController::class, 'login']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/profile', [UsersController::class, 'profile']);
        Route::post('/logout', [UsersController::class, 'logout']);

        Route::group(['prefix' => 'services'], function () {
            Route::get('/', [ServicesController::class, 'getProviderServices']);
        });

        Route::group(['prefix' => 'service-requests'], function () {
            Route::get('/', [UserServiceRequestsController::class, 'index']);
            Route::post('/', [UserServiceRequestsController::class, 'store']);
            Route::get('/{id}', [UserServiceRequestsController::class, 'show']);
            Route::put('/{id}', [UserServiceRequestsController::class, 'update']);
            Route::delete('/{id}', [UserServiceRequestsController::class, 'destroy']);
        });
    });
});

Route::group(['prefix' => 'service-providers'], function () {
    Route::post('/register', [ServiceProvidersController::class, 'register']);
    Route::post('/login', [ServiceProvidersController::class, 'login']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/profile', [ServiceProvidersController::class, 'profile']);

        Route::group(['prefix' => 'services'], function () {
            Route::get('/', [ProviderServicesController::class, 'index']);
            Route::get('/{id}', [ProviderServicesController::class, 'show']);
            Route::post('/', [ProviderServicesController::class, 'store']);
            Route::put('/{id}', [ProviderServicesController::class, 'update']);
            Route::delete('/{id}', [ProviderServicesController::class, 'destroy']);
        });

        Route::group(['prefix' => 'service-requests'], function () {
            Route::get('/', [ProviderServiceRequestsController::class, 'index']);
            Route::get('/{id}', [ProviderServiceRequestsController::class, 'show']);
            Route::put('/{id}', [ProviderServiceRequestsController::class, 'update']);
        });

        Route::post('/logout', [ServiceProvidersController::class, 'logout']);
    });
});

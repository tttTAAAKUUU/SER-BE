<?php

use App\Http\Controllers\Auth\AdministratorsController;
use App\Http\Controllers\Auth\ServiceProvidersController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\UsersController;
use App\Http\Controllers\StoreManagerProfileController;
use App\Http\Controllers\ProviderServicesController;
use App\Http\Controllers\Services\ServicesController;
use App\Http\Controllers\Admin\Services\ServicesController as AdminServicesController;
use App\Http\Controllers\Admin\ServiceCategories\ServiceCategoriesController;
use App\Http\Controllers\Auth\BusinessesController;
use App\Http\Controllers\Businesses\BusinessEmployeesController;
use App\Http\Controllers\Businesses\Stores\BookingController;
use App\Http\Controllers\Businesses\Stores\BusinessServicesController;
use App\Http\Controllers\Businesses\Stores\BusinessStoresController;
use App\Http\Controllers\Businesses\Stores\StoreEmployeesController;
use App\Http\Controllers\Businesses\Stores\StoreServicesController;
use App\Http\Controllers\ServiceProvider\ProviderServiceRequestsController;
use App\Http\Controllers\ServiceProvider\ServiceProviderServicesController;
use App\Http\Controllers\User\Bookings\UserBookingsController;
use App\Http\Controllers\User\UserServiceRequestsController;

// Public routes
Route::group(['prefix' => 'services'], function () {
    Route::get('/', [ServicesController::class, 'index']);
    Route::get('/{id}', [ServicesController::class, 'show']);
    Route::put('/{id}', [ServicesController::class, 'update']);
    Route::delete('/{id}', [ServicesController::class, 'destroy'])->middleware('auth:sanctum');
});

Route::group(['prefix' => 'users'], function () {
    Route::post('/register', [UsersController::class, 'register']);
    Route::post('/login', [UsersController::class, 'login']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/profile', [UsersController::class, 'profile']);
        Route::put('/profile', [UsersController::class, 'update']);
        Route::patch('/profile', [UsersController::class, 'update']);
        Route::post('/logout', [UsersController::class, 'logout']);

        Route::group(['prefix' => 'provider'], function () {
            Route::group(['prefix' => 'services'], function () {
                Route::get('/', [ServiceProviderServicesController::class, 'getServices']);
            });
        });

        Route::group(['prefix' => 'business'], function () {
            Route::group(['prefix' => 'services'], function () {
                Route::get('/', [BusinessServicesController::class, 'getServices']);
            });
        });


        Route::group(['prefix' => 'service-requests'], function () {
            Route::get('/', [UserServiceRequestsController::class, 'index']);
            Route::post('/', [UserServiceRequestsController::class, 'store']);
            Route::get('/{serviceRequest}', [UserServiceRequestsController::class, 'show']);
            Route::put('/{serviceRequest}', [UserServiceRequestsController::class, 'update']);
            Route::delete('/{serviceRequest}', [UserServiceRequestsController::class, 'destroy']);
        });

        Route::group(['prefix' => 'bookings'], function () {
            Route::get('/', [UserBookingsController::class, 'index']);
            Route::post('/', [UserBookingsController::class, 'store']);
            Route::get('/{booking}', [UserBookingsController::class, 'show']);
            Route::put('/{booking}', [UserBookingsController::class, 'update']);
            Route::delete('/{booking}', [UserBookingsController::class, 'destroy']);
        });
    });
});

Route::group(['prefix' => 'service-providers'], function () {
    Route::post('/register', [ServiceProvidersController::class, 'register']);
    Route::post('/login', [ServiceProvidersController::class, 'login']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/profile', [ServiceProvidersController::class, 'profile']);
        Route::put('/profile', [ServiceProvidersController::class, 'update']);
        Route::patch('/profile', [ServiceProvidersController::class, 'update']);

        Route::group(['prefix' => 'services'], function () {
            Route::get('/', [ProviderServicesController::class, 'index']);
            Route::get('/{id}', [ProviderServicesController::class, 'show']);
            Route::post('/', [ProviderServicesController::class, 'store']);
            Route::put('/{id}', [ProviderServicesController::class, 'update']);
            Route::delete('/{id}', [ProviderServicesController::class, 'destroy']);
        });

        Route::group(['prefix' => 'service-requests'], function () {
            Route::get('/', [ProviderServiceRequestsController::class, 'index']);
            Route::get('/{serviceRequest}', [ProviderServiceRequestsController::class, 'show']);
            Route::put('/{serviceRequest}', [ProviderServiceRequestsController::class, 'update']);
        });

        Route::post('/logout', [ServiceProvidersController::class, 'logout']);
    });
});

Route::group(['prefix' => 'administrators'], function () {
    Route::post('/register', [AdministratorsController::class, 'register']);
    Route::post('/login', [AdministratorsController::class, 'login']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/profile', [AdministratorsController::class, 'profile']);
        Route::put('/profile', [AdministratorsController::class, 'update']);
        Route::patch('/profile', [AdministratorsController::class, 'update']);
        Route::get('/dashboard', [AdministratorsController::class, 'dashboard']);

        Route::group(['prefix' => 'service-categories'], function () {
            Route::get('/', [ServiceCategoriesController::class, 'index']);
            Route::get('/{id}', [ServiceCategoriesController::class, 'show']);
            Route::post('/', [ServiceCategoriesController::class, 'store']);
            Route::put('/{id}', [ServiceCategoriesController::class, 'update']);
            Route::delete('/{id}', [ServiceCategoriesController::class, 'destroy']);
        });

        Route::group(['prefix' => 'services'], function () {
            Route::get('/', [AdminServicesController::class, 'index']);
            Route::get('/{id}', [AdminServicesController::class, 'show']);
            Route::post('/', [AdminServicesController::class, 'store']);
            Route::put('/{id}', [AdminServicesController::class, 'update']);
            Route::delete('/{id}', [AdminServicesController::class, 'destroy']);
        });

        Route::post('/logout', [ServiceProvidersController::class, 'logout']);
    });
});

Route::group(['prefix' => 'businesses'], function () {

    Route::post('/', [BusinessesController::class, 'index']);
    Route::post('/register', [BusinessesController::class, 'register']);
    Route::post('/login', [BusinessesController::class, 'login']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/profile', [BusinessesController::class, 'profile']);
        Route::put('/profile', [BusinessesController::class, 'update']);
        Route::patch('/profile', [BusinessesController::class, 'update']);
        Route::get('/dashboard', [BusinessesController::class, 'dashboard']);

        Route::group(['prefix' => 'stores'], function () {

            Route::group(['prefix' => '{store}/employees'], function () {
                Route::get('/', [StoreEmployeesController::class, 'index']);
                Route::get('/{id}', [StoreEmployeesController::class, 'show']);
                Route::post('/', [StoreEmployeesController::class, 'store']);
                Route::put('/{id}', [StoreEmployeesController::class, 'update']);
                Route::delete('/{id}', [StoreEmployeesController::class, 'destroy']);
            });

            Route::group(['prefix' => '{store}/bookings'], function () {
                Route::get('/', [BookingController::class, 'index']);
                Route::get('/{booking}', [BookingController::class, 'show']);
                Route::post('/', [BookingController::class, 'store']);
                Route::put('/{booking}', [BookingController::class, 'update']);
                Route::delete('/{booking}', [BookingController::class, 'destroy']);
            });

            Route::group(['prefix' => '{store}/services'], function () {

                Route::get('/', [StoreServicesController::class, 'index']);
                Route::get('/{id}', [StoreServicesController::class, 'show']);
                Route::post('/', [StoreServicesController::class, 'store']);
                Route::put('/{id}', [StoreServicesController::class, 'update']);
                Route::delete('/{id}', [StoreServicesController::class, 'destroy']);
            });

            Route::get('/', [BusinessStoresController::class, 'index']);
            Route::get('/{id}', [BusinessStoresController::class, 'show']);
            Route::post('/', [BusinessStoresController::class, 'store']);
            Route::put('/{id}', [BusinessStoresController::class, 'update']);
            Route::delete('/{id}', [BusinessStoresController::class, 'destroy']);
        });

        Route::group(['prefix' => 'employees'], function () {
            Route::get('/', [BusinessEmployeesController::class, 'index']);
        });

        Route::post('/logout', [ServiceProvidersController::class, 'logout']);
    });
});

Route::group(['prefix' => 'store-managers'], function () {
    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::put('/profile', [StoreManagerProfileController::class, 'update']);
        Route::patch('/profile', [StoreManagerProfileController::class, 'update']);
    });
});

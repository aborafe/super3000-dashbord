<?php

use App\Http\Controllers\Api\V1\CustomerAuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('auth/login', [CustomerAuthController::class, 'login'])
        ->middleware('throttle:api-login')
        ->name('auth.login');

    Route::post('auth/register', [CustomerAuthController::class, 'register'])
        ->middleware('throttle:api-sensitive')
        ->name('auth.register');

    Route::middleware(['auth:sanctum', 'customer.token'])->group(function () {
        Route::post('auth/logout', [CustomerAuthController::class, 'logout'])
            ->middleware('throttle:api-sensitive')
            ->name('auth.logout');
        Route::get('me', [CustomerAuthController::class, 'me'])
            ->name('auth.me');

        Route::get('categories', [CategoryController::class, 'index'])
            ->name('categories.index');

        Route::get('products', [ProductController::class, 'index'])
            ->name('products.index');
        Route::get('products/{product}', [ProductController::class, 'show'])
            ->name('products.show');

        Route::get('orders', [OrderController::class, 'index'])
            ->name('orders.index');
        Route::get('orders/{order}', [OrderController::class, 'show'])
            ->name('orders.show');
        Route::post('orders', [OrderController::class, 'store'])
            ->middleware('throttle:api-sensitive')
            ->name('orders.store');

        Route::get('notifications', [NotificationController::class, 'index'])
            ->name('notifications.index');
        Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount'])
            ->name('notifications.unread-count');
        Route::patch('notifications/read-all', [NotificationController::class, 'markAllAsRead'])
            ->middleware('throttle:api-sensitive')
            ->name('notifications.read-all');
        Route::patch('notifications/{notification}/read', [NotificationController::class, 'markAsRead'])
            ->middleware('throttle:api-sensitive')
            ->whereUuid('notification')
            ->name('notifications.read');
    });
});

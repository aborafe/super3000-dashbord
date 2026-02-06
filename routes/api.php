<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you may register API routes for your application.
| All routes in this file will be prefixed with /api and typically
| will be stateless, protected later with Sanctum.
|
*/

Route::get('/health', function (Request $request) {
    return response()->json([
        'data' => ['status' => 'ok'],
        'meta' => ['message' => 'API is up'],
    ]);
});

Route::prefix('v1')
    ->name('api.v1.')
    ->group(function (): void {
        // Auth
        Route::post('auth/login', [AuthController::class, 'login'])
            ->name('auth.login');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('auth/logout', [AuthController::class, 'logout'])
                ->name('auth.logout');

            Route::get('me', function (Request $request) {
                /** @var \App\Models\User $user */
                $user = $request->user();

                return response()->json([
                    'data' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'roles' => $user->getRoleNames(),
                        'permissions' => $user->getAllPermissions()->pluck('name'),
                    ],
                    'meta' => [
                        'message' => 'Authenticated user.',
                    ],
                ]);
            })->name('me');

            // Products
            Route::get('products', [ProductController::class, 'index'])
                ->name('products.index');
            Route::get('products/{product}', [ProductController::class, 'show'])
                ->name('products.show');            // Orders
            Route::get('orders', [OrderController::class, 'index'])
                ->name('orders.index');
            Route::get('orders/{order}', [OrderController::class, 'show'])
                ->name('orders.show');
        });
    });

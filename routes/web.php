<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\MyProfileController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WarehouseController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/' . config('app.locale', 'en'));
});

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])
        ->middleware('throttle:web-login')
        ->name('login.store');
});

Route::get('{locale}', function (string $locale) {
    return redirect()->route('admin.dashboard', ['locale' => $locale]);
})->whereIn('locale', ['ar', 'en']);

Route::prefix('{locale}/admin')
    ->whereIn('locale', ['ar', 'en'])
    ->middleware(['setlocale', 'auth'])
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->middleware('can:dashboard.view')
            ->name('dashboard');

        Route::patch('products/{product}/toggle', [ProductController::class, 'toggle'])
            ->middleware('can:products.update')
            ->name('products.toggle');
        Route::resource('products', ProductController::class)
            ->only(['index'])
            ->middleware('can:products.view')
            ->names('products');
        Route::resource('products', ProductController::class)
            ->only(['create', 'store'])
            ->middleware('can:products.create')
            ->names('products');
        Route::resource('products', ProductController::class)
            ->only(['edit', 'update'])
            ->middleware('can:products.update')
            ->names('products');
        Route::resource('products', ProductController::class)
            ->only(['destroy'])
            ->middleware('can:products.delete')
            ->names('products');

        Route::get('orders', [OrderController::class, 'index'])
            ->middleware('can:orders.view')
            ->name('orders.index');
        Route::get('orders/{order}', [OrderController::class, 'show'])
            ->middleware('can:orders.view')
            ->name('orders.show');
        Route::patch('orders/{order}/details', [OrderController::class, 'updateDetails'])
            ->middleware('can:orders.update')
            ->name('orders.details');
        Route::patch('orders/{order}/items', [OrderController::class, 'updateItems'])
            ->middleware('can:orders.update')
            ->name('orders.items');
        Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])
            ->middleware('can:orders.change_status')
            ->name('orders.status');

        Route::get('invoices', [InvoiceController::class, 'index'])
            ->middleware('can:orders.view')
            ->name('invoices.index');
        Route::get('invoices/{order}/print', [InvoiceController::class, 'print'])
            ->middleware('can:orders.view')
            ->name('invoices.print');

        Route::get('users', [UserController::class, 'index'])
            ->middleware('can:users.view')
            ->name('users.index');
        Route::get('users/{user}', [UserController::class, 'show'])
            ->middleware('can:users.view')
            ->name('users.show');

        Route::get('myprofile', [MyProfileController::class, 'index'])->name('myprofile');

        Route::get('notifications', [NotificationController::class, 'index'])
            ->middleware('can:notifications.view')
            ->name('notifications.index');
        Route::get('notifications/live', [NotificationController::class, 'live'])
            ->middleware('can:notifications.view')
            ->name('notifications.live');
        Route::get('notifications/compose', [NotificationController::class, 'compose'])
            ->middleware('can:notifications.send')
            ->name('notifications.compose');
        Route::post('notifications/send', [NotificationController::class, 'send'])
            ->middleware('can:notifications.send')
            ->name('notifications.send');
        Route::patch('notifications/read-all', [NotificationController::class, 'markAllAsRead'])
            ->middleware('can:notifications.view')
            ->name('notifications.read-all');
        Route::patch('notifications/{notification}/read', [NotificationController::class, 'markAsRead'])
            ->middleware('can:notifications.view')
            ->whereUuid('notification')
            ->name('notifications.read');

        Route::prefix('catalog')->name('catalog.')->group(function () {
            Route::resource('categories', CategoryController::class)
                ->only(['index'])
                ->middleware('can:categories.view');
            Route::resource('categories', CategoryController::class)
                ->only(['create', 'store'])
                ->middleware('can:categories.create');
            Route::resource('categories', CategoryController::class)
                ->only(['edit', 'update'])
                ->middleware('can:categories.update');
            Route::resource('categories', CategoryController::class)
                ->only(['destroy'])
                ->middleware('can:categories.delete');
            Route::patch('categories/{category}/toggle', [CategoryController::class, 'toggle'])
                ->middleware('can:categories.update')
                ->name('categories.toggle');

            Route::get('inventory', [InventoryController::class, 'index'])
                ->middleware('can:inventory.view')
                ->name('inventory.index');
            Route::get('inventory/create', [InventoryController::class, 'create'])
                ->middleware('can:inventory.create')
                ->name('inventory.create');
            Route::post('inventory', [InventoryController::class, 'store'])
                ->middleware('can:inventory.create')
                ->name('inventory.store');

            Route::patch('products/{product}/toggle', [ProductController::class, 'toggle'])
                ->middleware('can:products.update')
                ->name('products.toggle');
            Route::resource('products', ProductController::class)
                ->only(['index'])
                ->middleware('can:products.view')
                ->names('products');
            Route::resource('products', ProductController::class)
                ->only(['create', 'store'])
                ->middleware('can:products.create')
                ->names('products');
            Route::resource('products', ProductController::class)
                ->only(['edit', 'update'])
                ->middleware('can:products.update')
                ->names('products');
            Route::resource('products', ProductController::class)
                ->only(['destroy'])
                ->middleware('can:products.delete')
                ->names('products');
        });

        Route::prefix('sales')->name('sales.')->group(function () {
            Route::resource('customers', CustomerController::class)
                ->only(['index'])
                ->middleware('can:customers.view');
            Route::resource('customers', CustomerController::class)
                ->only(['create', 'store'])
                ->middleware('can:customers.create');
            Route::resource('customers', CustomerController::class)
                ->only(['edit', 'update'])
                ->middleware('can:customers.update');
            Route::resource('customers', CustomerController::class)
                ->only(['destroy'])
                ->middleware('can:customers.delete');

            Route::get('orders', [OrderController::class, 'index'])
                ->middleware('can:orders.view')
                ->name('orders.index');
            Route::get('orders/{order}', [OrderController::class, 'show'])
                ->middleware('can:orders.view')
                ->name('orders.show');
            Route::patch('orders/{order}/details', [OrderController::class, 'updateDetails'])
                ->middleware('can:orders.update')
                ->name('orders.details');
            Route::patch('orders/{order}/items', [OrderController::class, 'updateItems'])
                ->middleware('can:orders.update')
                ->name('orders.items');
            Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])
                ->middleware('can:orders.change_status')
                ->name('orders.status');
            Route::get('payments', [PaymentController::class, 'index'])
                ->middleware('can:payments.view')
                ->name('payments.index');
        });

        Route::prefix('operations')->name('operations.')->group(function () {
            Route::resource('warehouses', WarehouseController::class)
                ->only(['index'])
                ->middleware('can:warehouses.view');
            Route::resource('warehouses', WarehouseController::class)
                ->only(['create', 'store'])
                ->middleware('can:warehouses.create');
            Route::resource('warehouses', WarehouseController::class)
                ->only(['edit', 'update'])
                ->middleware('can:warehouses.update');
            Route::resource('warehouses', WarehouseController::class)
                ->only(['destroy'])
                ->middleware('can:warehouses.delete');
            Route::get('reports', [ReportController::class, 'index'])
                ->middleware('can:reports.view')
                ->name('reports.index');
        });

        Route::prefix('security')->name('security.')->group(function () {
            Route::get('roles', [RoleController::class, 'index'])
                ->middleware('can:roles.view')
                ->name('roles.index');
            Route::get('roles/{role}/edit', [RoleController::class, 'edit'])
                ->middleware('can:roles.view')
                ->name('roles.edit');
            Route::put('roles/{role}', [RoleController::class, 'update'])
                ->middleware('can:roles.update')
                ->name('roles.update');
            Route::get('activity-logs', [ActivityLogController::class, 'index'])
                ->middleware('can:activity_logs.view')
                ->name('activity.index');
            Route::get('users', [UserController::class, 'index'])
                ->middleware('can:users.view')
                ->name('users.index');
            Route::get('users/create', [UserController::class, 'create'])
                ->middleware('can:users.create')
                ->name('users.create');
            Route::post('users', [UserController::class, 'store'])
                ->middleware('can:users.create')
                ->name('users.store');
            Route::get('users/{user}/edit', [UserController::class, 'edit'])
                ->middleware('can:users.update')
                ->name('users.edit');
            Route::put('users/{user}', [UserController::class, 'update'])
                ->middleware('can:users.update')
                ->name('users.update');
            Route::get('users/{user}', [UserController::class, 'show'])
                ->middleware('can:users.view')
                ->name('users.show');
        });

        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('general', [SettingsController::class, 'general'])
                ->middleware('can:settings.manage')
                ->name('general');
            Route::put('general', [SettingsController::class, 'updateGeneral'])
                ->middleware('can:settings.manage')
                ->name('general.update');
            Route::get('appearance', [SettingsController::class, 'appearance'])
                ->middleware('can:settings.manage')
                ->name('appearance');
            Route::put('appearance', [SettingsController::class, 'updateAppearance'])
                ->middleware('can:settings.manage')
                ->name('appearance.update');
        });

        Route::post('logout', function (Request $request) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/' . app()->getLocale());
        })->name('logout');
    });

Route::get('/admin/{path?}', function ($path = 'dashboard') {
    return redirect('/en/admin/' . ltrim($path, '/'));
})->where('path', '.*');

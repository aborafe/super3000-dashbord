<?php

use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PartnerController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\WarehouseController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ThemeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->to('/' . config('app.locale', 'en'));
});

Route::prefix('{locale}')
    ->where(['locale' => 'en|ar'])
    ->middleware('setlocale')
    ->group(function () {
        Route::get('/', function () {
            return redirect()->route('admin.dashboard');
        })->name('home');

        Route::middleware('guest')->group(function () {
            Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
            Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
        });

        Route::post('/logout', [AuthController::class, 'logout'])
            ->middleware('auth')
            ->name('logout');

        Route::post('/admin/theme/toggle', [ThemeController::class, 'toggle'])
            ->middleware('auth')
            ->name('admin.theme.toggle');

        Route::post('/locale/{target}', [LocaleController::class, 'switch'])
            ->name('locale.switch');

        Route::middleware(['auth', 'role:admin'])
            ->prefix('admin')
            ->as('admin.')
            ->group(function () {
                Route::get('/dashboard', function () {
                    return view('admin.dashboard');
                })->name('dashboard');

                // Products module
                Route::get('products', [ProductController::class, 'index'])
                    ->name('products.index')
                    ->middleware('permission:products.view');

                Route::get('products/create', [ProductController::class, 'create'])
                    ->name('products.create')
                    ->middleware('permission:products.create');

                Route::post('products', [ProductController::class, 'store'])
                    ->name('products.store')
                    ->middleware('permission:products.create');

                Route::get('products/{product}/edit', [ProductController::class, 'edit'])
                    ->name('products.edit')
                    ->middleware('permission:products.update');

                Route::put('products/{product}', [ProductController::class, 'update'])
                    ->name('products.update')
                    ->middleware('permission:products.update');

                Route::delete('products/{product}', [ProductController::class, 'destroy'])
                    ->name('products.destroy')
                    ->middleware('permission:products.delete');

                // Orders module
                Route::get('orders', [OrderController::class, 'index'])
                    ->name('orders.index')
                    ->middleware('permission:orders.view');

                Route::get('orders/create', [OrderController::class, 'create'])
                    ->name('orders.create')
                    ->middleware('permission:orders.create');

                Route::post('orders', [OrderController::class, 'store'])
                    ->name('orders.store')
                    ->middleware('permission:orders.create');

                Route::get('orders/{order}', [OrderController::class, 'show'])
                    ->name('orders.show')
                    ->middleware('permission:orders.view');

                Route::get('orders/{order}/edit', [OrderController::class, 'edit'])
                    ->name('orders.edit')
                    ->middleware('permission:orders.update');

                Route::put('orders/{order}', [OrderController::class, 'update'])
                    ->name('orders.update')
                    ->middleware('permission:orders.update');

                Route::post('orders/{order}/payments', [OrderController::class, 'storePayment'])
                    ->name('orders.payments.store')
                    ->middleware('permission:orders.update');

                Route::get('orders/{order}/invoice', [OrderController::class, 'invoice'])
                    ->name('orders.invoice')
                    ->middleware('permission:orders.view');

                Route::put('orders/{order}/status', [OrderController::class, 'updateStatus'])
                    ->name('orders.update-status')
                    ->middleware('permission:orders.change_status');

                Route::put('orders/{order}/payment-status', [OrderController::class, 'updatePaymentStatus'])
                    ->name('orders.update-payment-status')
                    ->middleware('permission:orders.change_status');

                // Reports module
                Route::get('reports', [ReportController::class, 'index'])
                    ->name('reports.index')
                    ->middleware('permission:reports.view');

                // Partners module
                Route::get('partners', [PartnerController::class, 'index'])
                    ->name('partners.index')
                    ->middleware('permission:partners.view');
                Route::get('partners/create', [PartnerController::class, 'create'])
                    ->name('partners.create')
                    ->middleware('permission:partners.create');
                Route::post('partners', [PartnerController::class, 'store'])
                    ->name('partners.store')
                    ->middleware('permission:partners.create');
                Route::get('partners/{partner}', [PartnerController::class, 'show'])
                    ->name('partners.show')
                    ->middleware('permission:partners.view');
                Route::get('partners/{partner}/edit', [PartnerController::class, 'edit'])
                    ->name('partners.edit')
                    ->middleware('permission:partners.update');
                Route::put('partners/{partner}', [PartnerController::class, 'update'])
                    ->name('partners.update')
                    ->middleware('permission:partners.update');
                Route::delete('partners/{partner}', [PartnerController::class, 'destroy'])
                    ->name('partners.destroy')
                    ->middleware('permission:partners.delete');

                // Warehouses module
                Route::get('warehouses', [WarehouseController::class, 'index'])
                    ->name('warehouses.index')
                    ->middleware('permission:warehouses.view');
                Route::get('warehouses/create', [WarehouseController::class, 'create'])
                    ->name('warehouses.create')
                    ->middleware('permission:warehouses.create');
                Route::post('warehouses', [WarehouseController::class, 'store'])
                    ->name('warehouses.store')
                    ->middleware('permission:warehouses.create');
                Route::get('warehouses/{warehouse}', [WarehouseController::class, 'show'])
                    ->name('warehouses.show')
                    ->middleware('permission:warehouses.view');
                Route::get('warehouses/{warehouse}/edit', [WarehouseController::class, 'edit'])
                    ->name('warehouses.edit')
                    ->middleware('permission:warehouses.update');
                Route::put('warehouses/{warehouse}', [WarehouseController::class, 'update'])
                    ->name('warehouses.update')
                    ->middleware('permission:warehouses.update');
                Route::delete('warehouses/{warehouse}', [WarehouseController::class, 'destroy'])
                    ->name('warehouses.destroy')
                    ->middleware('permission:warehouses.delete');
                Route::post('warehouses/{warehouse}/transfer', [WarehouseController::class, 'transfer'])
                    ->name('warehouses.transfer')
                    ->middleware('permission:warehouses.transfer');

                // Employees module
                Route::get('employees', [EmployeeController::class, 'index'])
                    ->name('employees.index')
                    ->middleware('permission:employees.view');
                Route::get('employees/create', [EmployeeController::class, 'create'])
                    ->name('employees.create')
                    ->middleware('permission:employees.create');
                Route::post('employees', [EmployeeController::class, 'store'])
                    ->name('employees.store')
                    ->middleware('permission:employees.create');
                Route::get('employees/{employee}', [EmployeeController::class, 'show'])
                    ->name('employees.show')
                    ->middleware('permission:employees.view');
                Route::get('employees/{employee}/edit', [EmployeeController::class, 'edit'])
                    ->name('employees.edit')
                    ->middleware('permission:employees.update');
                Route::put('employees/{employee}', [EmployeeController::class, 'update'])
                    ->name('employees.update')
                    ->middleware('permission:employees.update');
                Route::delete('employees/{employee}', [EmployeeController::class, 'destroy'])
                    ->name('employees.destroy')
                    ->middleware('permission:employees.delete');

                // Notifications
                Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead'])
                    ->name('notifications.read');

                Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])
                    ->name('notifications.read-all');
            });
    });

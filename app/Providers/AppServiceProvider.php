<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\ProductStock;
use App\Observers\OrderObserver;
use App\Observers\ProductStockObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Order::observe(OrderObserver::class);
        ProductStock::observe(ProductStockObserver::class);
        URL::defaults(['locale' => config('app.locale', 'en')]);
    }
}

<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\ProductStock;
use App\Models\Setting;
use App\Observers\OrderObserver;
use App\Observers\ProductStockObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

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
        RateLimiter::for('api-login', function (Request $request): array {
            $identifier = strtolower((string) ($request->input('email')
                ?: $request->input('phone')
                ?: $request->input('identifier')
                ?: 'guest'));

            return [
                Limit::perMinute(10)->by($identifier.'|'.$request->ip()),
            ];
        });

        RateLimiter::for('web-login', function (Request $request): array {
            $email = Str::lower(trim((string) $request->input('email', 'guest')));

            return [
                Limit::perMinute(5)->by($email.'|'.$request->ip()),
                Limit::perMinute(25)->by('ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('api-sensitive', function (Request $request): array {
            $userId = $request->user()?->getAuthIdentifier();
            $key = $userId ? 'user:'.$userId : 'ip:'.$request->ip();

            return [
                Limit::perMinute(30)->by($key),
            ];
        });

        Order::observe(OrderObserver::class);
        ProductStock::observe(ProductStockObserver::class);
        URL::defaults(['locale' => config('app.locale', 'ar')]);
        Paginator::useBootstrapFive();

        View::composer(['layouts.admin', 'layouts.blank'], function ($view): void {
            $settings = Setting::getMany([
                'appearance.theme',
                'appearance.rtl',
                'general.site_name',
            ]);

            $view->with([
                'appearanceTheme' => $settings['appearance.theme'] ?: 'light',
                'appearanceRtl' => (bool) ((int) ($settings['appearance.rtl'] ?? 0)),
                'siteName' => $settings['general.site_name'] ?: 'Super3000',
            ]);
        });
    }
}

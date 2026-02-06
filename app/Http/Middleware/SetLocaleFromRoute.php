<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class SetLocaleFromRoute
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->route('locale');

        if (! in_array($locale, ['en', 'ar'], true)) {
            $locale = config('app.locale', 'en');
        }

        app()->setLocale($locale);
        URL::defaults(['locale' => $locale]);

        $request->route()->forgetParameter('locale');

        return $next($request);
    }
}

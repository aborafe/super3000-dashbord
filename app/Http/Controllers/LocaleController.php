<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function switch(string $target, Request $request): RedirectResponse
    {
        if (! in_array($target, ['en', 'ar'], true)) {
            $target = config('app.locale', 'en');
        }

        $referer = $request->headers->get('referer');
        if ($referer) {
            $path = parse_url($referer, PHP_URL_PATH) ?? '';
            $query = parse_url($referer, PHP_URL_QUERY);

            $segments = array_values(array_filter(explode('/', trim($path, '/'))));

            if (! empty($segments) && in_array($segments[0], ['en', 'ar'], true)) {
                $segments[0] = $target;
                $newPath = '/' . implode('/', $segments);
                if ($query) {
                    $newPath .= '?' . $query;
                }

                return redirect()->to($newPath);
            }
        }

        return redirect()->to('/' . $target);
    }
}


<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ThemeController extends Controller
{
    public function toggle(Request $request): RedirectResponse
    {
        $requested = $request->string('theme')->toString();
        if (in_array($requested, ['light', 'dark'], true)) {
            $request->session()->put('theme', $requested);

            return back();
        }

        $current = $request->session()->get('theme', 'light');
        $next = $current === 'dark' ? 'light' : 'dark';

        $request->session()->put('theme', $next);

        return back();
    }
}


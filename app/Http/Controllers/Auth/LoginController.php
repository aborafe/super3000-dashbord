<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::check() && Auth::user()?->can('dashboard.view')) {
            return redirect()->route('admin.dashboard', ['locale' => config('app.locale', 'ar')]);
        }

        if (Auth::check()) {
            Auth::logout();
        }

        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, (bool) $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => [__('These credentials do not match our records.')],
            ]);
        }

        if (! $request->user()?->can('dashboard.view')) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => [__('You are not allowed to access the dashboard.')],
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(
            route('admin.dashboard', ['locale' => config('app.locale', 'ar')])
        );
    }
}

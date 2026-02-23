@extends('layouts.blank')

@section('title', __('Login'))

@section('content')
    <style>
        .login-shell {
            --brand-a: #0f766e;
            --brand-b: #164e63;
            --brand-c: #f59e0b;
            --surface: #ffffff;
            --line: rgba(15, 23, 42, 0.12);
            min-height: 100vh;
            background:
                radial-gradient(circle at 12% 16%, rgba(16, 185, 129, 0.2), transparent 38%),
                radial-gradient(circle at 82% 18%, rgba(245, 158, 11, 0.22), transparent 34%),
                radial-gradient(circle at 86% 86%, rgba(22, 78, 99, 0.3), transparent 44%),
                linear-gradient(145deg, #f7fafc 0%, #ecf3f7 40%, #e8f2f0 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            overflow: hidden;
            position: relative;
        }

        .login-orb {
            position: absolute;
            border-radius: 999px;
            filter: blur(3px);
            opacity: 0.35;
            pointer-events: none;
            animation: loginFloat 7s ease-in-out infinite;
        }

        .login-orb--1 {
            width: 14rem;
            height: 14rem;
            top: -4rem;
            right: -4rem;
            background: rgba(245, 158, 11, 0.45);
        }

        .login-orb--2 {
            width: 10rem;
            height: 10rem;
            left: -2rem;
            bottom: 10%;
            background: rgba(15, 118, 110, 0.35);
            animation-delay: .8s;
        }

        .login-frame {
            width: min(980px, 100%);
            border-radius: 1.5rem;
            overflow: hidden;
            border: 1px solid var(--line);
            box-shadow: 0 35px 90px rgba(2, 8, 23, 0.2);
            background: var(--surface);
            display: grid;
            grid-template-columns: 1.05fr .95fr;
            animation: loginRise .65s ease-out;
        }

        .login-showcase {
            position: relative;
            padding: 2.25rem;
            color: #f8fafc;
            background: linear-gradient(160deg, var(--brand-a), var(--brand-b) 52%, #0b1220);
        }

        .login-chip {
            display: inline-block;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
            border: 1px solid rgba(255, 255, 255, 0.35);
            border-radius: 999px;
            padding: .32rem .75rem;
            margin-bottom: 1.25rem;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(6px);
        }

        .login-showcase h1 {
            margin: 0 0 .75rem;
            font-size: clamp(1.55rem, 2vw + 1rem, 2.1rem);
            line-height: 1.25;
            font-weight: 700;
        }

        .login-showcase p {
            margin: 0;
            max-width: 33ch;
            color: rgba(241, 245, 249, 0.9);
        }

        .login-badges {
            margin-top: 1.8rem;
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
        }

        .login-badges span {
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 999px;
            padding: .35rem .68rem;
            font-size: .74rem;
        }

        .login-form {
            padding: 2rem;
            background: #ffffff;
        }

        .login-title {
            margin: 0 0 .25rem;
            font-size: 1.35rem;
            font-weight: 700;
            color: #0f172a;
        }

        .login-subtitle {
            margin: 0 0 1.4rem;
            color: #64748b;
        }

        .login-form .form-label {
            font-weight: 600;
            color: #334155;
            margin-bottom: .45rem;
        }

        .login-form .form-control {
            height: 2.9rem;
            border-color: #cbd5e1;
            border-radius: .75rem;
            box-shadow: none;
            transition: border-color .2s ease, box-shadow .2s ease;
        }

        .login-form .form-control:focus {
            border-color: var(--brand-a);
            box-shadow: 0 0 0 .2rem rgba(15, 118, 110, 0.14);
        }

        .password-wrap {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            top: 50%;
            right: .7rem;
            transform: translateY(-50%);
            border: 0;
            background: transparent;
            color: #64748b;
            font-size: .78rem;
            font-weight: 700;
            letter-spacing: .03em;
            padding: .15rem .35rem;
            border-radius: .4rem;
        }

        .password-toggle:hover {
            color: #0f172a;
            background: #e2e8f0;
        }

        .login-form .form-check-label {
            color: #475569;
            font-size: .92rem;
        }

        .login-submit {
            height: 2.95rem;
            border: 0;
            border-radius: .82rem;
            font-weight: 700;
            letter-spacing: .02em;
            background: linear-gradient(120deg, var(--brand-a), #0f766e 55%, #115e59);
            box-shadow: 0 14px 28px rgba(15, 118, 110, 0.28);
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .login-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 18px 30px rgba(15, 118, 110, 0.34);
        }

        @media (max-width: 991.98px) {
            .login-frame {
                grid-template-columns: 1fr;
            }

            .login-showcase {
                padding: 1.6rem;
            }

            .login-form {
                padding: 1.6rem;
            }
        }

        @keyframes loginRise {
            from {
                opacity: 0;
                transform: translateY(14px) scale(.99);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes loginFloat {
            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-12px);
            }
        }
    </style>

    <div class="login-shell">
        <span class="login-orb login-orb--1"></span>
        <span class="login-orb login-orb--2"></span>

        <div class="login-frame">
            <section class="login-showcase">
                <span class="login-chip">{{ __('Admin Portal') }}</span>
                <h1>{{ __('Welcome back to Super3000') }}</h1>
                <p>{{ __('Sign in to manage products, orders, inventory, and customer operations from one place.') }}</p>
                <div class="login-badges">
                    <span>{{ __('Operations') }}</span>
                    <span>{{ __('Sales') }}</span>
                    <span>{{ __('Inventory') }}</span>
                </div>
            </section>

            <section class="login-form">
                <h2 class="login-title">{{ __('Sign in') }}</h2>
                <p class="login-subtitle">{{ __('Enter your account credentials to continue.') }}</p>

                <form method="POST" action="{{ route('login.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label" for="email">{{ __('Email') }}</label>
                        <input id="email" type="email" name="email"
                            class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}"
                            required autofocus autocomplete="email">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="password">{{ __('Password') }}</label>
                        <div class="password-wrap">
                            <input id="password" type="password" name="password"
                                class="form-control @error('password') is-invalid @enderror" required
                                autocomplete="current-password">
                            <button type="button" class="password-toggle" data-password-toggle>{{ __('Show') }}</button>
                        </div>
                        @error('password')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3 form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="remember" name="remember"
                            {{ old('remember') ? 'checked' : '' }}>
                        <label class="form-check-label" for="remember">{{ __('Remember me') }}</label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 login-submit">{{ __('Log in') }}</button>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('page-scripts')
    <script>
        document.querySelectorAll('[data-password-toggle]').forEach(function(toggle) {
            toggle.addEventListener('click', function() {
                var input = document.getElementById('password');
                if (!input) return;

                var hidden = input.type === 'password';
                input.type = hidden ? 'text' : 'password';
                toggle.textContent = hidden ? '{{ __('Hide') }}' : '{{ __('Show') }}';
            });
        });
    </script>
@endsection

<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use App\Exceptions\StockConflictException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

$helpersPath = __DIR__.'/../app/Support/helpers.php';
if (is_file($helpersPath)) {
    require_once $helpersPath;
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['prefix' => 'api', 'middleware' => ['api', 'auth:sanctum', \App\Http\Middleware\EnsureCustomerToken::class],]
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Here you can customize middleware groups and aliases if needed.
        // Sanctum will register its own middleware alias when installed.
        $middleware->alias([
            'setlocale' => \App\Http\Middleware\SetLocaleFromRoute::class,
            'customer.token' => \App\Http\Middleware\EnsureCustomerToken::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $errors = collect($exception->errors())
                ->flatMap(fn (array $messages, string $field) => collect($messages)->map(
                    fn (string $message) => ['field' => $field, 'message' => $message]
                ))
                ->values()
                ->all();

            return response()->json([
                'status' => false,
                'data' => null,
                'meta' => ['message' => 'Validation failed'],
                'errors' => $errors,
            ], 422);
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'status' => false,
                'data' => null,
                'meta' => ['message' => 'Unauthenticated'],
                'errors' => [],
            ], 401);
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'status' => false,
                'data' => null,
                'meta' => ['message' => 'Forbidden'],
                'errors' => [],
            ], 403);
        });

        $exceptions->render(function (AccessDeniedHttpException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'status' => false,
                'data' => null,
                'meta' => ['message' => 'Forbidden'],
                'errors' => [],
            ], 403);
        });

        $exceptions->render(function (ModelNotFoundException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'status' => false,
                'data' => null,
                'meta' => ['message' => 'Resource not found'],
                'errors' => [],
            ], 404);
        });

        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'status' => false,
                'data' => null,
                'meta' => ['message' => 'Resource not found'],
                'errors' => [],
            ], 404);
        });

        $exceptions->render(function (MethodNotAllowedHttpException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'status' => false,
                'data' => null,
                'meta' => ['message' => 'Method not allowed'],
                'errors' => [],
            ], 405);
        });

        $exceptions->render(function (StockConflictException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'status' => false,
                'data' => null,
                'meta' => ['message' => $exception->getMessage() ?: 'Stock conflict'],
                'errors' => [],
            ], 409);
        });

        $exceptions->render(function (\Throwable $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'status' => false,
                'data' => null,
                'meta' => ['message' => 'Server error'],
                'errors' => config('app.debug')
                    ? [['field' => null, 'message' => $exception->getMessage()]]
                    : [],
            ], 500);
        });
    })->create();

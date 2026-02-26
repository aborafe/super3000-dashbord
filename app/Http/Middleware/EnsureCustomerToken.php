<?php

namespace App\Http\Middleware;

use App\Models\Customer;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $bearerToken = trim((string) $request->bearerToken());

        if (! $user instanceof Customer || $bearerToken === '') {
            return $this->unauthorized('Unauthenticated');
        }

        /** @var \App\Models\Customer|null $freshCustomer */
        $freshCustomer = Customer::query()
            ->select(['id', 'is_active', 'token'])
            ->find($user->getKey());

        if (! $freshCustomer || ! $freshCustomer->is_active || ! is_string($freshCustomer->token) || $freshCustomer->token === '') {
            return $this->unauthorized('Invalid token');
        }

        if (! hash_equals($freshCustomer->token, hash('sha256', $bearerToken))) {
            return $this->unauthorized('Invalid token');
        }

        return $next($request);
    }

    protected function unauthorized(string $message): JsonResponse
    {
        return response()->json([
            'status' => false,
            'data' => null,
            'meta' => ['message' => $message],
            'errors' => [],
        ], 401);
    }
}

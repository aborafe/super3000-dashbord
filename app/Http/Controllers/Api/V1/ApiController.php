<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

abstract class ApiController extends Controller
{
    protected function success(mixed $data = null, int $status = 200, string $message = 'OK', array $meta = []): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => $data,
            'meta' => array_merge(['message' => $message], $meta),
            'errors' => [],
        ], $status);
    }

    protected function error(string $message, int $status = 400, array $errors = [], mixed $data = null, array $meta = []): JsonResponse
    {
        return response()->json([
            'status' => false,
            'data' => $data,
            'meta' => array_merge(['message' => $message], $meta),
            'errors' => $errors,
        ], $status);
    }

    protected function paginationMeta(object $paginator): array
    {
        return [
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }
}

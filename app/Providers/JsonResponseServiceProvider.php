<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;

class JsonResponseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // ✅ Macro global: crear JsonResponse con sustitución de UTF-8
        Response::macro('safeJson', function ($data, $status = 200, array $headers = [], $options = 0) {
            $options |= JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR;
            return new JsonResponse($data, $status, $headers, $options);
        });
    }
}
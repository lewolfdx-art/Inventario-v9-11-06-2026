<?php

namespace App\Http\Middleware;

use App\Support\Utf8Cleaner;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeUtf8Response
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Solo procesar respuestas JSON
        $contentType = $response->headers->get('Content-Type', '');
        if (!str_contains($contentType, 'application/json')) {
            return $response;
        }

        $content = $response->getContent();

        if (!is_string($content) || $content === '') {
            return $response;
        }

        // Si no es UTF-8 válido, intentar repararlo
        if (!Utf8Cleaner::isValid($content)) {
            $decoded = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $cleaned = Utf8Cleaner::clean($decoded);
                $response->setContent(
                    json_encode($cleaned, JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE)
                );
            }
        }

        return $response;
    }
}
<?php

namespace App\Http\Middleware;

use App\Support\Utf8Cleaner;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeUtf8Input
{
    public function handle(Request $request, Closure $next): Response
    {
        // Limpiar query params
        if ($request->query->count() > 0) {
            $request->query->replace(
                Utf8Cleaner::clean($request->query->all())
            );
        }

        // Limpiar body (POST/PUT/PATCH)
        if ($request->getContent() !== '') {
            // Para JSON (Livewire y APIs)
            if ($request->isJson()) {
                try {
                    $decoded = json_decode($request->getContent(), true);
                    if (is_array($decoded)) {
                        $cleaned = Utf8Cleaner::clean($decoded);
                        $request->json()->replace($cleaned);
                        $request->replace($cleaned);
                    }
                } catch (\Throwable $e) {
                    // Si falla, dejamos pasar tal cual
                }
            } else {
                // Para form data
                $request->replace(
                    Utf8Cleaner::clean($request->all())
                );
            }
        }

        return $next($request);
    }
}
<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Guarda firmas capturadas como imágenes.
 *
 * El campo de firma del formulario entrega un "data URL" (base64) cuando el
 * usuario dibuja o pega su firma. Este helper lo convierte en un archivo dentro
 * del disco público y devuelve la ruta relativa que se guarda en la base de
 * datos. Si el valor ya es una ruta (la firma no se tocó) se devuelve tal cual.
 */
class Firma
{
    public static function guardar(?string $valor, string $carpeta = 'firmas/maletines', ?string $anterior = null): ?string
    {
        if (blank($valor)) {
            if ($anterior) {
                Storage::disk('public')->delete($anterior);
            }

            return null;
        }

        // Ya es una ruta de archivo existente (la firma no se modificó).
        if (! str_starts_with($valor, 'data:image')) {
            return $valor;
        }

        if (! preg_match('/^data:image\/(png|jpe?g|webp);base64,/', $valor, $m)) {
            return $anterior;
        }

        $extension = strtolower($m[1]);
        $extension = $extension === 'jpeg' ? 'jpg' : $extension;

        $binario = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $valor), true);

        if ($binario === false || $binario === '') {
            return $anterior;
        }

        $ruta = trim($carpeta, '/').'/'.Str::random(24).'.'.$extension;

        if (! Storage::disk('public')->put($ruta, $binario) || ! Storage::disk('public')->exists($ruta)) {
            return $anterior;
        }

        if ($anterior && $anterior !== $ruta) {
            Storage::disk('public')->delete($anterior);
        }

        return $ruta;
    }

    /**
     * Devuelve el "data URL" listo para incrustar en PDF (o un string vacío).
     */
    public static function base64(?string $ruta): string
    {
        if (blank($ruta) || ! Storage::disk('public')->exists($ruta)) {
            return '';
        }

        $contenido = Storage::disk('public')->get($ruta);
        $mime = Storage::disk('public')->mimeType($ruta) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode($contenido);
    }
}

<?php

namespace App\Support;

class Utf8Cleaner
{
    /**
     * Limpia cualquier estructura (array, string, objeto) y devuelve
     * una versión 100% UTF-8 válida.
     */
    public static function clean(mixed $data): mixed
    {
        if (is_array($data)) {
            $out = [];
            foreach ($data as $key => $value) {
                $out[self::cleanKey($key)] = self::clean($value);
            }
            return $out;
        }

        if (is_object($data)) {
            // No tocar objetos Eloquent/Modelos ni Closures
            if ($data instanceof \Illuminate\Database\Eloquent\Model) {
                return self::cleanModel($data);
            }
            if ($data instanceof \Closure) {
                return $data;
            }
            // Otros objetos: convertir a array y limpiar
            try {
                return self::clean((array) $data);
            } catch (\Throwable) {
                return $data;
            }
        }

        if (is_string($data)) {
            return self::cleanString($data);
        }

        return $data;
    }

    /**
     * Limpia un modelo Eloquent recursivamente.
     */
    protected static function cleanModel($model): mixed
    {
        foreach ($model->getAttributes() as $key => $value) {
            if (is_string($value)) {
                $model->setAttribute($key, self::cleanString($value));
            }
        }
        return $model;
    }

    /**
     * Limpia una key de array (por si acaso).
     */
    protected static function cleanKey(mixed $key): mixed
    {
        if (is_string($key)) {
            return self::cleanString($key);
        }
        return $key;
    }

    /**
     * Limpia un string dejándolo como UTF-8 válido.
     */
    public static function cleanString(string $value): string
    {
        if ($value === '') {
            return '';
        }

        // Si ya es UTF-8 válido, solo quitar bytes de control
        if (!mb_check_encoding($value, 'UTF-8')) {
            // Intentar conversión desde UTF-8 laxo
            $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        }

        // Quitar bytes de control invisibles (excepto \t \n \r)
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);

        // Última defensa: sustituir cualquier byte raro restante
        if (!mb_check_encoding($value, 'UTF-8')) {
            $value = mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
        }

        return $value;
    }

    /**
     * Verifica si un string es UTF-8 válido.
     */
    public static function isValid(string $value): bool
    {
        return mb_check_encoding($value, 'UTF-8');
    }
}
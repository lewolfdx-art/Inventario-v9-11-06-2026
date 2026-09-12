<?php

namespace App\Traits;

trait SanitizesUtf8
{
    protected function sanitizeUtf8($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        if (!mb_check_encoding($value, 'UTF-8')) {
            $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        }

        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);

        return $value;
    }

    protected static function bootSanitizesUtf8(): void
    {
        /** @noinspection PhpUndefinedMethodInspection */
        static::saving(function ($model) {
            foreach ($model->getAttributes() as $key => $value) {
                if (is_string($value) && !mb_check_encoding($value, 'UTF-8')) {
                    $model->setAttribute($key, mb_convert_encoding($value, 'UTF-8', 'UTF-8'));
                }
            }
        });
    }
}
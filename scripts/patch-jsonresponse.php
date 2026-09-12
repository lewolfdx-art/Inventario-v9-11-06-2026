<?php

$file = __DIR__ . '/../vendor/laravel/framework/src/Illuminate/Http/JsonResponse.php';

if (!file_exists($file)) {
    echo "❌ No se encontró JsonResponse.php\n";
    exit(1);
}

$content = file_get_contents($file);

if (str_contains($content, 'JSON_INVALID_UTF8_SUBSTITUTE')) {
    echo "✅ El parche ya está aplicado\n";
    exit(0);
}

$buscar = '        $this->data = match (true) {
            $data instanceof Jsonable => $data->toJson($this->encodingOptions),
            $data instanceof JsonSerializable => json_encode($data->jsonSerialize(), $this->encodingOptions),
            $data instanceof Arrayable => json_encode($data->toArray(), $this->encodingOptions),
            default => json_encode($data, $this->encodingOptions),
        };';

$reemplazar = '        // ✅ PARCHE: forzar sustitución de UTF-8 inválido
        $options = $this->encodingOptions | JSON_INVALID_UTF8_SUBSTITUTE;

        $this->data = match (true) {
            $data instanceof Jsonable => $data->toJson($options),
            $data instanceof JsonSerializable => json_encode($data->jsonSerialize(), $options),
            $data instanceof Arrayable => json_encode($data->toArray(), $options),
            default => json_encode($data, $options),
        };';

$content = str_replace($buscar, $reemplazar, $content);

file_put_contents($file, $content);
echo "✅ Parche aplicado correctamente\n";
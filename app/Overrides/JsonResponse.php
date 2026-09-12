<?php

namespace App\Overrides;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Http\JsonResponse as BaseJsonResponse;
use InvalidArgumentException;
use JsonSerializable;

class JsonResponse extends BaseJsonResponse
{
    /**
     * ✅ PARCHE UTF-8: reemplaza setData() del framework para forzar
     * JSON_INVALID_UTF8_SUBSTITUTE y evitar el error
     * "Malformed UTF-8 characters, possibly incorrectly encoded".
     */
    public function setData($data = [])
    {
        $this->original = $data;

        $options = $this->encodingOptions | JSON_INVALID_UTF8_SUBSTITUTE;

        if ($data instanceof Jsonable) {
            $this->data = $data->toJson($options);
        } elseif ($data instanceof JsonSerializable) {
            $this->data = json_encode($data->jsonSerialize(), $options);
        } elseif ($data instanceof Arrayable) {
            $this->data = json_encode($data->toArray(), $options);
        } else {
            $this->data = json_encode($data, $options);
        }

        if (! $this->hasValidJson(json_last_error())) {
            throw new InvalidArgumentException(json_last_error_msg());
        }

        return $this->update();
    }
}
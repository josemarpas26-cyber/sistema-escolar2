<?php

namespace App\Models\Concerns;

use App\Support\IdMask;

trait HasMaskedRouteKey
{
    public function getRouteKey(): string
    {
        return IdMask::encode((int) $this->getKey());
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $id = IdMask::decode((string) $value);

        if ($id === null) {
            return null;
        }

        return $this->where($field ?? $this->getRouteKeyName(), $id)->first();
    }
}

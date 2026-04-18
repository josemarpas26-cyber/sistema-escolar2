<?php

namespace App\Support;

use Illuminate\Support\Facades\Crypt;
use Throwable;

class IdMask
{
    public static function encode(int $id): string
    {
        $encrypted = Crypt::encryptString((string) $id);

        return rtrim(strtr(base64_encode($encrypted), '+/', '-_'), '=');
    }

    public static function decode(?string $maskedId): ?int
    {
        if (blank($maskedId)) {
            return null;
        }

        if (ctype_digit($maskedId)) {
            return (int) $maskedId;
        }

        $decoded = base64_decode(strtr($maskedId, '-_', '+/'), true);

        if ($decoded === false) {
            return null;
        }

        try {
            $plain = Crypt::decryptString($decoded);

            return ctype_digit($plain) ? (int) $plain : null;
        } catch (Throwable) {
            return null;
        }
    }
}

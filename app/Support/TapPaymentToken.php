<?php

namespace App\Support;

final class TapPaymentToken
{
    public static function extractTokenId(mixed $value): ?string
    {
        if (is_string($value)) {
            $trimmed = trim($value);

            if (str_starts_with($trimmed, 'tok_')) {
                return $trimmed;
            }

            if (str_starts_with($trimmed, '{')) {
                $decoded = json_decode($trimmed, true);

                return self::extractTokenId($decoded);
            }

            return null;
        }

        if (! is_array($value)) {
            return null;
        }

        $candidates = [
            $value['id'] ?? null,
            $value['token'] ?? null,
            is_array($value['source'] ?? null) ? ($value['source']['id'] ?? null) : null,
            is_array($value['token'] ?? null) ? ($value['token']['id'] ?? null) : null,
            is_array($value['data'] ?? null) ? ($value['data']['id'] ?? null) : null,
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && str_starts_with($candidate, 'tok_')) {
                return $candidate;
            }
        }

        return null;
    }
}

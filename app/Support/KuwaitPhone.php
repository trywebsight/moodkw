<?php

namespace App\Support;

class KuwaitPhone
{
    public const PATTERN = '/^[24569]\d{7}$/';

    public static function sanitize(string $value): string
    {
        $digits = preg_replace('/\D/', '', $value);

        if (str_starts_with($digits, '965')) {
            $digits = substr($digits, 3);
        }

        return substr($digits, 0, 8);
    }

    public static function rules(): array
    {
        return ['required', 'string', 'regex:'.self::PATTERN];
    }
}

<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TapMode: string implements HasLabel
{
    case Test = 'test';
    case Live = 'live';

    public function label(): string
    {
        return match ($this) {
            self::Test => 'Test',
            self::Live => 'Live',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }
}

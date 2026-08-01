<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getSubheading(): ?string
    {
        return now()->translatedFormat('l, F j, Y');
    }

    public function getColumns(): int | array
    {
        return 1;
    }
}

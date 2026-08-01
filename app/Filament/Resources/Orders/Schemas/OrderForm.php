<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Order Status')
                    ->schema([
                        Select::make('order_status')
                            ->options(OrderStatus::class)
                            ->required(),
                        Select::make('payment_status')
                            ->options(PaymentStatus::class)
                            ->required()
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }
}

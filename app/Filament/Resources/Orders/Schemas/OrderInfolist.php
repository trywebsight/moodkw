<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\AddressType;
use App\Enums\PaymentMethod;
use App\Models\Order;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Order')
                    ->schema([
                        TextEntry::make('order_number'),
                        TextEntry::make('order_status')
                            ->badge(),
                        TextEntry::make('payment_status')
                            ->badge(),
                        TextEntry::make('payment_method')
                            ->badge()
                            ->formatStateUsing(fn (?PaymentMethod $state): ?string => $state?->label())
                            ->placeholder('—'),
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('paid_at')
                            ->dateTime()
                            ->placeholder('—'),
                        TextEntry::make('tap_charge_id')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('signed_payment_url')
                            ->label('Payment link')
                            ->state(fn (Order $record): ?string => $record->signedPaymentUrl())
                            ->copyable()
                            ->visible(fn (Order $record): bool => $record->canRetryPayment())
                            ->columnSpanFull()
                            ->helperText('Signed link for the customer to pay online. Valid for 7 days.'),
                    ])
                    ->columns(2),

                Section::make('Customer')
                    ->schema([
                        TextEntry::make('customer_name'),
                        TextEntry::make('customer_phone'),
                    ])
                    ->columns(2),

                Section::make('Delivery Address')
                    ->schema([
                        TextEntry::make('address_type')
                            ->label('Address type')
                            ->badge(),
                        TextEntry::make('governorate.name')
                            ->label('Governorate'),
                        TextEntry::make('area.name')
                            ->label('Area'),
                        TextEntry::make('block'),
                        TextEntry::make('street'),
                        TextEntry::make('building')
                            ->label(fn ($record) => $record->address_type === AddressType::Home ? 'House No.' : 'Building'),
                        TextEntry::make('floor')
                            ->label('Floor')
                            ->placeholder('—')
                            ->visible(fn ($record) => in_array($record->address_type, [AddressType::Office, AddressType::Apartment], true)),
                        TextEntry::make('apartment')
                            ->label(fn ($record) => match ($record->address_type) {
                                AddressType::Office => 'Office / Unit',
                                AddressType::Apartment => 'Apartment',
                                default => 'Apartment',
                            })
                            ->placeholder('—')
                            ->visible(fn ($record) => $record->address_type !== AddressType::Home),
                        TextEntry::make('delivery_notes')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Product')
                    ->schema([
                        TextEntry::make('product.name'),
                        TextEntry::make('quantity'),
                        TextEntry::make('unit_price')
                            ->money('KWD'),
                        TextEntry::make('subtotal')
                            ->money('KWD'),
                        TextEntry::make('delivery_fee')
                            ->money('KWD'),
                        TextEntry::make('total')
                            ->money('KWD'),
                    ])
                    ->columns(3),
            ]);
    }
}

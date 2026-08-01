<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Product Details')
                    ->schema([
                        TextInput::make('name')
                            ->label('Name (English)')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('name_ar')
                            ->label('Name (Arabic)')
                            ->maxLength(255),
                        Textarea::make('description')
                            ->label('Description (English)')
                            ->rows(4)
                            ->columnSpanFull(),
                        Textarea::make('description_ar')
                            ->label('Description (Arabic)')
                            ->rows(4)
                            ->columnSpanFull(),
                        TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->step(0.001)
                            ->prefix('KWD'),
                        FileUpload::make('images')
                            ->label('Product photos')
                            ->multiple()
                            ->reorderable()
                            ->image()
                            ->directory('products')
                            ->visibility('public')
                            ->imageEditor()
                            ->columnSpanFull()
                            ->helperText('First photo is used as the cover image on the storefront.'),
                        Toggle::make('is_active')->default(true),
                        TextInput::make('stock')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                    ])
                    ->columns(2),
            ]);
    }
}

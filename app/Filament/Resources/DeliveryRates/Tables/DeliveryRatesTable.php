<?php

namespace App\Filament\Resources\DeliveryRates\Tables;

use App\Models\Area;
use App\Models\DeliveryRate;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class DeliveryRatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('governorate.name')
                    ->label('Governorate (EN)')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('governorate.name_ar')
                    ->label('Governorate (AR)')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('fee')
                    ->money('KWD')
                    ->sortable(),
                TextColumn::make('areas_active')
                    ->label('Areas')
                    ->state(function (DeliveryRate $record): string {
                        $areas = $record->governorate?->areas ?? collect();
                        $active = $areas->where('is_active', true)->count();

                        return "{$active}/{$areas->count()} active";
                    })
                    ->description(function (DeliveryRate $record): ?string {
                        $activeAreas = $record->governorate?->areas
                            ? $record->governorate->areas->where('is_active', true)->sortBy('name')->values()
                            : collect();

                        if ($activeAreas->isEmpty()) {
                            return 'No areas enabled';
                        }

                        $preview = $activeAreas
                            ->take(4)
                            ->map(fn (Area $area): string => $area->name_ar ?: $area->name)
                            ->join(', ');

                        if ($activeAreas->count() > 4) {
                            $preview .= '…';
                        }

                        return $preview;
                    }),
                ToggleColumn::make('is_active')
                    ->label('Active')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('updateFee')
                        ->label('Update fee')
                        ->schema([
                            TextInput::make('fee')
                                ->label('Delivery fee')
                                ->required()
                                ->numeric()
                                ->minValue(0)
                                ->step(0.001)
                                ->prefix('KWD'),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $records->each->update(['fee' => $data['fee']]);

                            Notification::make()
                                ->title('Delivery fees updated')
                                ->body('Updated '.$records->count().' governorate'.($records->count() === 1 ? '' : 's').' to KWD '.number_format((float) $data['fee'], 3))
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

<?php

namespace App\Filament\Resources\Languages\Tables;

use App\Models\Language;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class LanguagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                ImageColumn::make('flag_path')->label('Flag')->disk('public')->imageHeight(16),
                TextColumn::make('code')->badge()->searchable(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('native_name')->label('Native name'),
                TextColumn::make('direction')->badge()->color(fn (string $state) => $state === 'rtl' ? 'warning' : 'gray'),
                TextColumn::make('fallback_code')->label('Fallback')->placeholder(__('default')),
                IconColumn::make('is_default')->label('Default')->boolean(),
                ToggleColumn::make('is_active')->label('Enabled')->disabled(fn (Language $record) => $record->is_default),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->disabled(fn (Language $record) => ! $record->canBeDeleted())
                    ->tooltip(fn (Language $record) => $record->canBeDeleted() ? null : ($record->is_default
                        ? __('The default language cannot be deleted.')
                        : __('Used by :count translations — disable it instead, or remove its content first.', ['count' => $record->contentCount()]))),
            ]);
    }
}

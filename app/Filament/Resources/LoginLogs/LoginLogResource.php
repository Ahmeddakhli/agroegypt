<?php

namespace App\Filament\Resources\LoginLogs;

use App\Filament\Resources\LoginLogs\Pages\ListLoginLogs;
use App\Filament\Support\TranslatesResourceLabels;
use App\Models\LoginLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class LoginLogResource extends Resource
{
    use TranslatesResourceLabels;

    protected static ?string $model = LoginLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFingerPrint;

    protected static string|UnitEnum|null $navigationGroup = 'Users & security';

    protected static ?string $navigationLabel = 'Sign-in history';

    protected static ?int $navigationSort = 4;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('When')->dateTime()->sortable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('user.name')->label('User')->placeholder('—'),
                IconColumn::make('successful')->boolean(),
                TextColumn::make('ip_address')->label('IP')->searchable(),
                TextColumn::make('user_agent')->limit(50)->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([TernaryFilter::make('successful')]);
    }

    public static function getPages(): array
    {
        return ['index' => ListLoginLogs::route('/')];
    }
}

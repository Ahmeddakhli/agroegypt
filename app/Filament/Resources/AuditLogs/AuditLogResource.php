<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Filament\Support\TranslatesResourceLabels;
use App\Models\AuditLog;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class AuditLogResource extends Resource
{
    use TranslatesResourceLabels;

    protected static ?string $model = AuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Users & security';

    protected static ?string $navigationLabel = 'Activity log';

    protected static ?int $navigationSort = 3;

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

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextEntry::make('created_at')->dateTime(),
            TextEntry::make('user.name')->label('User')->placeholder(__('System')),
            TextEntry::make('event')->badge()->formatStateUsing(fn (string $state) => __(ucfirst($state))),
            TextEntry::make('subject')->state(fn (AuditLog $record) => class_basename($record->auditable_type).' #'.$record->auditable_id),
            TextEntry::make('ip_address')->label('IP'),
            KeyValueEntry::make('changes')
                ->state(fn (AuditLog $record) => collect($record->changes ?? [])->map(fn ($v) => is_scalar($v) || $v === null ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE))->all())
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('When')->dateTime()->sortable(),
                TextColumn::make('user.name')->label('User')->placeholder(__('System'))->searchable(),
                TextColumn::make('event')->badge()->formatStateUsing(fn (string $state) => __(ucfirst($state)))->color(fn (string $state) => match ($state) {
                    'created' => 'success', 'deleted' => 'danger', default => 'info',
                }),
                TextColumn::make('auditable_type')->label('Subject')->formatStateUsing(fn (?string $state, AuditLog $record) => class_basename((string) $state).' #'.$record->auditable_id),
                TextColumn::make('ip_address')->label('IP')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('event')->options(['created' => __('Created'), 'updated' => __('Updated'), 'deleted' => __('Deleted')]),
                SelectFilter::make('user_id')->label('User')->relationship('user', 'name'),
                SelectFilter::make('auditable_type')->label('Subject type')->options(fn () => AuditLog::query()->distinct()->pluck('auditable_type')->mapWithKeys(fn ($t) => [$t => class_basename($t)])->all()),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListAuditLogs::route('/')];
    }
}

<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Models\AuditLog;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentActivity extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return AuditLogResource::canViewAny();
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('Recent activity'))
            ->query(AuditLog::query()->with('user')->latest('created_at')->limit(10))
            ->paginated(false)
            ->columns([
                TextColumn::make('created_at')->label('When')->since(),
                TextColumn::make('user.name')->label('User')->placeholder(__('System')),
                TextColumn::make('event')->badge()->formatStateUsing(fn (string $state) => __(ucfirst($state))),
                TextColumn::make('auditable_type')->label('Subject')->formatStateUsing(fn (?string $state, AuditLog $record) => class_basename((string) $state).' #'.$record->auditable_id),
            ]);
    }
}

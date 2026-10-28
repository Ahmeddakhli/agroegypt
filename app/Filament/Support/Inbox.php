<?php

namespace App\Filament\Support;

use App\Enums\InboxStatus;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Shared table/actions for the contact-message and job-application inboxes.
 */
final class Inbox
{
    /**
     * @param  list<mixed>  $extraColumns
     * @param  array<string, string>  $exportColumns  attribute => CSV header
     */
    public static function table(Table $table, array $extraColumns, array $exportColumns, string $exportName): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->recordClasses(fn (Model $record) => $record->read_at ? null : 'font-semibold')
            ->columns([
                TextColumn::make('created_at')->label('Received')->dateTime()->sortable(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('email')->searchable()->copyable(),
                ...$extraColumns,
                TextColumn::make('status')->badge()->formatStateUsing(fn (InboxStatus $state) => $state->label())
                    ->color(fn (InboxStatus $state) => match ($state) {
                        InboxStatus::New => 'warning',
                        InboxStatus::InProgress => 'info',
                        InboxStatus::Replied => 'success',
                        InboxStatus::Archived => 'gray',
                    }),
                TextColumn::make('locale')->label('Lang')->badge()->color('gray'),
            ])
            ->filters([
                SelectFilter::make('status')->options(collect(InboxStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()),
                TernaryFilter::make('read_at')->label('Read')->nullable(),
                Filter::make('received')
                    ->schema([DatePicker::make('from'), DatePicker::make('until')])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date))),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->label('Status & notes')->schema(self::editSchema()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('markRead')->icon('heroicon-o-envelope-open')->action(fn (Collection $records) => $records->each->markAsRead())->deselectRecordsAfterCompletion(),
                    BulkAction::make('markUnread')->icon('heroicon-o-envelope')->action(fn (Collection $records) => $records->each->markAsUnread())->deselectRecordsAfterCompletion(),
                    BulkAction::make('export')->label('Export CSV')->icon('heroicon-o-arrow-down-tray')->action(fn (Collection $records) => self::export($records, $exportColumns, $exportName)),
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    /**
     * @return list<mixed>
     */
    public static function editSchema(): array
    {
        return [
            Select::make('status')->options(collect(InboxStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all())->required(),
            Textarea::make('notes')->label('Internal notes')->rows(5)->maxLength(5000),
        ];
    }

    public static function toggleReadAction(): Action
    {
        return Action::make('toggleRead')
            ->label(fn (Model $record) => $record->read_at ? 'Mark as unread' : 'Mark as read')
            ->icon(fn (Model $record) => $record->read_at ? 'heroicon-o-envelope' : 'heroicon-o-envelope-open')
            ->color('gray')
            ->action(fn (Model $record) => $record->read_at ? $record->markAsUnread() : $record->markAsRead());
    }

    /**
     * @param  iterable<Model>  $records
     * @param  array<string, string>  $columns
     */
    public static function export(iterable $records, array $columns, string $name): StreamedResponse
    {
        return response()->streamDownload(function () use ($records, $columns) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, array_values($columns), escape: '');
            foreach ($records as $record) {
                fputcsv($out, array_map(function (string $attribute) use ($record) {
                    $value = $record->{$attribute};

                    return self::csvSafe($value instanceof \BackedEnum ? $value->value : (string) $value);
                }, array_keys($columns)), escape: '');
            }
            fclose($out);
        }, $name.'-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Neutralise spreadsheet formulas in visitor-supplied text (CSV injection).
     */
    private static function csvSafe(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }
}

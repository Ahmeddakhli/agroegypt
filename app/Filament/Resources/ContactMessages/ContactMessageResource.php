<?php

namespace App\Filament\Resources\ContactMessages;

use App\Filament\Resources\ContactMessages\Pages\ListContactMessages;
use App\Filament\Resources\ContactMessages\Pages\ViewContactMessage;
use App\Filament\Support\Inbox;
use App\Filament\Support\TranslatesResourceLabels;
use App\Models\ContactMessage;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ContactMessageResource extends Resource
{
    use TranslatesResourceLabels;

    protected static ?string $model = ContactMessage::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static string|UnitEnum|null $navigationGroup = 'Inbox';

    protected static ?string $navigationLabel = 'Contact messages';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return ($count = ContactMessage::unread()->count()) ? (string) $count : null;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(3)->schema([
                TextEntry::make('name'),
                TextEntry::make('email')->copyable()->url(fn (ContactMessage $record) => 'mailto:'.$record->email),
                TextEntry::make('created_at')->label('Received')->dateTime(),
                TextEntry::make('locale')->label('Language')->badge(),
                TextEntry::make('status')->badge()->formatStateUsing(fn ($state) => $state->label()),
                TextEntry::make('ip_address')->label('IP'),
                TextEntry::make('message')->columnSpanFull()->placeholder('—')->extraAttributes(['style' => 'white-space: pre-line']),
                TextEntry::make('notes')->label('Internal notes')->columnSpanFull()->placeholder('—')->extraAttributes(['style' => 'white-space: pre-line']),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return Inbox::table($table, [
            TextColumn::make('message')->limit(60)->searchable()->toggleable(),
        ], ['created_at' => 'Received', 'name' => 'Name', 'email' => 'Email', 'message' => 'Message', 'locale' => 'Language', 'status' => 'Status', 'notes' => 'Notes'], 'contact-messages');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContactMessages::route('/'),
            'view' => ViewContactMessage::route('/{record}'),
        ];
    }
}

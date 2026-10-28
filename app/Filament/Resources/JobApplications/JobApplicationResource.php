<?php

namespace App\Filament\Resources\JobApplications;

use App\Filament\Resources\JobApplications\Pages\ListJobApplications;
use App\Filament\Resources\JobApplications\Pages\ViewJobApplication;
use App\Filament\Support\Inbox;
use App\Filament\Support\TranslatesResourceLabels;
use App\Models\JobApplication;
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

class JobApplicationResource extends Resource
{
    use TranslatesResourceLabels;

    protected static ?string $model = JobApplication::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static string|UnitEnum|null $navigationGroup = 'Inbox';

    protected static ?string $navigationLabel = 'Job applications';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return ($count = JobApplication::unread()->count()) ? (string) $count : null;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(3)->schema([
                TextEntry::make('name'),
                TextEntry::make('email')->copyable()->url(fn (JobApplication $record) => 'mailto:'.$record->email),
                TextEntry::make('phone'),
                TextEntry::make('job_title'),
                TextEntry::make('created_at')->label('Received')->dateTime(),
                TextEntry::make('status')->badge()->formatStateUsing(fn ($state) => $state->label()),
                TextEntry::make('cv_original_name')->label('CV')->placeholder('—'),
                TextEntry::make('locale')->label('Language')->badge(),
                TextEntry::make('ip_address')->label('IP'),
                TextEntry::make('message')->columnSpanFull()->placeholder('—')->extraAttributes(['style' => 'white-space: pre-line']),
                TextEntry::make('notes')->label('Internal notes')->columnSpanFull()->placeholder('—')->extraAttributes(['style' => 'white-space: pre-line']),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return Inbox::table($table, [
            TextColumn::make('job_title')->searchable(),
            TextColumn::make('phone')->toggleable(),
        ], ['created_at' => 'Received', 'name' => 'Name', 'email' => 'Email', 'phone' => 'Phone', 'job_title' => 'Job title', 'message' => 'Message', 'status' => 'Status', 'notes' => 'Notes'], 'job-applications');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJobApplications::route('/'),
            'view' => ViewJobApplication::route('/{record}'),
        ];
    }
}

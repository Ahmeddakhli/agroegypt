<?php

namespace App\Filament\Resources\Documents;

use App\Filament\Resources\Documents\Pages\CreateDocument;
use App\Filament\Resources\Documents\Pages\EditDocument;
use App\Filament\Resources\Documents\Pages\ListDocuments;
use App\Filament\Support\MediaPicker;
use App\Filament\Support\TranslatesResourceLabels;
use App\Filament\Support\Translations;
use App\Models\Document;
use App\Models\Language;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class DocumentResource extends Resource
{
    use TranslatesResourceLabels;

    protected static ?string $model = Document::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Certificates & documents';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Translations::tabs(fn (Language $language, bool $isDefault) => [
                TextInput::make('title')->required($isDefault)->maxLength(190),
                Textarea::make('description')->rows(2)->maxLength(1000),
            ]),
            Section::make()->columns(2)->schema([
                Select::make('type')->options(array_combine(Document::TYPES, array_map(fn (string $type) => __(ucfirst($type)), Document::TYPES)))->required()->default('certificate'),
                TextInput::make('sort')->numeric()->default(0),
                MediaPicker::make('image_media_id', 'Logo / preview image'),
                MediaPicker::make('file_media_id', 'Downloadable file (PDF…)', imagesOnly: false),
                Toggle::make('is_active')->label('Visible')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['translations', 'image', 'file']))
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                ImageColumn::make('image_url')->label('')->state(fn (Document $record) => $record->image?->url(480))->imageHeight(40)->extraImgAttributes(['style' => 'background:#858533']),
                TextColumn::make('title')->searchable(query: fn (Builder $query, string $search) => $query->whereTranslationLike('title', "%{$search}%")),
                TextColumn::make('type')->badge()->formatStateUsing(fn (string $state) => __(ucfirst($state))),
                TextColumn::make('file.name')->label('File')->placeholder('—')->url(fn (Document $record) => $record->file?->url())->openUrlInNewTab(),
                ToggleColumn::make('is_active')->label('Visible'),
            ])
            ->filters([SelectFilter::make('type')->options(array_combine(Document::TYPES, array_map(fn (string $type) => __(ucfirst($type)), Document::TYPES)))])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocuments::route('/'),
            'create' => CreateDocument::route('/create'),
            'edit' => EditDocument::route('/{record}/edit'),
        ];
    }
}

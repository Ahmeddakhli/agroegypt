<?php

namespace App\Filament\Resources\Galleries;

use App\Filament\Resources\Galleries\Pages\CreateGallery;
use App\Filament\Resources\Galleries\Pages\EditGallery;
use App\Filament\Resources\Galleries\Pages\ListGalleries;
use App\Filament\Support\MediaPicker;
use App\Filament\Support\TranslatesResourceLabels;
use App\Filament\Support\Translations;
use App\Models\Gallery;
use App\Models\Language;
use App\Support\MediaLibrary;
use App\Support\SiteIndex;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use UnitEnum;

class GalleryResource extends Resource
{
    use TranslatesResourceLabels;

    protected static ?string $model = Gallery::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Translations::tabs(fn (Language $language, bool $isDefault) => [
                Translations::nameField('title', $isDefault, 'Title'),
                Translations::slugField($language, $isDefault, fn (string $locale, string $slug, $get, ?Model $record) => Gallery::query()
                    ->when($record, fn (Builder $q) => $q->whereKeyNot($record->getKey()))
                    ->whereHas('translations', fn (Builder $q) => $q->where('locale', $locale)->where('slug', $slug))->exists()),
                TextInput::make('meta_title')->label('SEO title')->maxLength(190),
                Textarea::make('meta_description')->label('SEO description')->rows(2)->maxLength(300),
            ]),
            Section::make(__('Settings'))->columns(3)->schema([
                MediaPicker::make('cover_media_id', 'Cover image (gallery page tile)'),
                MediaPicker::make('hero_media_id', 'Page header image'),
                TextInput::make('sort')->numeric()->default(0),
                Toggle::make('is_active')->label('Visible')->default(true),
                Toggle::make('is_featured')->label('Show as large card (like "Packing Station")'),
            ]),
            Section::make(__('Images'))->description(__('Drag to reorder. Use "Bulk upload" on the list page actions to add many at once.'))->schema([
                Repeater::make('imageItems')
                    ->hiddenLabel()
                    ->relationship('imageItems')
                    ->orderColumn('sort')
                    ->reorderableWithDragAndDrop()
                    ->grid(4)
                    ->defaultItems(0)
                    ->addActionLabel(__('Add image'))
                    ->schema([MediaPicker::make('media_id', 'Image')->required()]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['translations', 'cover'])->withCount('imageItems'))
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                ImageColumn::make('cover_url')->label('')->state(fn (Gallery $record) => $record->cover?->url(480))->imageHeight(40),
                TextColumn::make('title')->searchable(query: fn (Builder $query, string $search) => $query->whereTranslationLike('title', "%{$search}%")),
                TextColumn::make('image_items_count')->label('Images')->badge(),
                ToggleColumn::make('is_active')->label('Visible'),
            ])
            ->recordActions([
                Action::make('view')->icon('heroicon-o-arrow-top-right-on-square')->url(fn (Gallery $record) => SiteIndex::url($record))->openUrlInNewTab()->visible(fn (Gallery $record) => SiteIndex::url($record) !== null),
                Action::make('bulkUpload')
                    ->label('Bulk upload')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->schema([FileUpload::make('files')->multiple()->required()->storeFiles(false)->maxFiles(50)->image()->maxSize(20 * 1024)])
                    ->action(function (Gallery $record, array $data) {
                        $sort = (int) $record->imageItems()->max('sort');
                        $added = 0;
                        foreach ($data['files'] as $file) {
                            try {
                                $record->imageItems()->firstOrCreate(['media_id' => MediaLibrary::store($file)->id], ['sort' => ++$sort]);
                                $added++;
                            } catch (InvalidArgumentException $e) {
                                Notification::make()->warning()->title($file->getClientOriginalName().': '.__($e->getMessage()))->send();
                            }
                        }
                        Notification::make()->success()->title(__(':count image(s) added', ['count' => $added]))->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGalleries::route('/'),
            'create' => CreateGallery::route('/create'),
            'edit' => EditGallery::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources\Products;

use App\Enums\ContentStatus;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Support\MediaPicker;
use App\Filament\Support\TranslatesResourceLabels;
use App\Filament\Support\Translations;
use App\Models\Language;
use App\Models\Product;
use App\Support\CatalogSlug;
use App\Support\SiteIndex;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class ProductResource extends Resource
{
    use TranslatesResourceLabels;

    protected static ?string $model = Product::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        $months = collect(range(1, 12))->mapWithKeys(fn (int $m) => [$m => Carbon::create(2000, $m, 1)->translatedFormat('M')])->all();

        return $schema->components([
            Tabs::make('product')->persistTabInQueryString()->columnSpanFull()->tabs([
                Tab::make('Content')->icon('heroicon-o-document-text')->schema([
                    Translations::tabs(fn (Language $language, bool $isDefault) => [
                        Grid::make(2)->schema([
                            Translations::nameField('name', $isDefault),
                            Translations::slugField($language, $isDefault, fn (string $locale, string $slug, Get $get, ?Model $record) => CatalogSlug::isTaken(
                                $locale, $slug, $get('../category_id') ? (int) $get('../category_id') : null, $record,
                            )),
                            TextInput::make('subtitle')->label('Heading above the description')->maxLength(255),
                            TextInput::make('menu_label')->label('Menu label')->maxLength(100),
                        ]),
                        Textarea::make('short_description')->rows(2)->maxLength(500),
                        RichEditor::make('description')->toolbarButtons(['bold', 'italic', 'link', 'bulletList', 'orderedList']),
                        TextInput::make('spec_label')->label('Label of the size/count list')->placeholder(__('Counts'))->maxLength(50),
                        Grid::make(2)->schema([
                            TextInput::make('meta_title')->label('SEO title')->maxLength(190),
                            Textarea::make('meta_description')->label('SEO description')->rows(2)->maxLength(300),
                        ]),
                    ]),
                ]),
                Tab::make('Settings & images')->icon('heroicon-o-photo')->schema([
                    Section::make()->columns(3)->schema([
                        Select::make('category_id')->label('Category')->options(fn () => CategoryResource::parentOptions(null))->placeholder(__('— Directly under Our Products —'))->live(),
                        ToggleButtons::make('status')->options(ContentStatus::class)->default(ContentStatus::Draft)->inline()->required(),
                        TextInput::make('sort')->numeric()->default(0)->minValue(0),
                        Toggle::make('is_featured')->label('Featured'),
                    ]),
                    Section::make(__('Images'))->columns(3)->schema([
                        MediaPicker::make('image_media_id', 'Main product image'),
                        MediaPicker::make('card_media_id', 'Card image (listings)'),
                        MediaPicker::make('hero_media_id', 'Page header image')->helperText(__('Empty = inherit from the category.')),
                        MediaPicker::make('season_icon_media_id', 'Seasonality icon'),
                        MediaPicker::make('icon_media_id', 'Line icon'),
                    ]),
                    Section::make(__('Gallery'))->description(__('Drag to reorder.'))->schema([
                        Repeater::make('imageItems')
                            ->hiddenLabel()
                            ->relationship('imageItems')
                            ->orderColumn('sort')
                            ->reorderableWithDragAndDrop()
                            ->grid(3)
                            ->defaultItems(0)
                            ->addActionLabel(__('Add image'))
                            ->schema([MediaPicker::make('media_id', 'Image')->required()]),
                    ]),
                ]),
                Tab::make('Specifications')->icon('heroicon-o-clipboard-document-list')->schema([
                    TagsInput::make('spec_values')->label('Sizes / counts')->placeholder(__('Add a value and press Enter'))->reorderable(),
                    Translations::repeater(Repeater::make('varieties')
                        ->relationship('varieties', fn (Builder $query) => $query->with('translations'))
                        ->orderColumn('sort')
                        ->reorderableWithDragAndDrop()
                        ->collapsible()
                        ->defaultItems(0)
                        ->itemLabel(fn (array $state) => $state[Language::defaultCode()]['name'] ?? null)
                        ->addActionLabel(__('Add variety'))
                        ->schema([Translations::tabs(fn (Language $language, bool $isDefault) => [TextInput::make('name')->required($isDefault)->maxLength(150)], 'Variety')])),
                ]),
                Tab::make('Packaging')->icon('heroicon-o-cube')->schema([
                    Translations::repeater(Repeater::make('packagings')
                        ->hiddenLabel()
                        ->relationship('packagings', fn (Builder $query) => $query->with(['translations', 'specs.translations']))
                        ->orderColumn('sort')
                        ->reorderableWithDragAndDrop()
                        ->collapsible()
                        ->defaultItems(0)
                        ->itemLabel(fn (array $state) => $state[Language::defaultCode()]['title'] ?? null)
                        ->addActionLabel(__('Add packaging'))
                        ->schema([
                            Grid::make(2)->schema([
                                MediaPicker::make('icon_media_id', 'Icon'),
                                Translations::tabs(fn (Language $language, bool $isDefault) => [TextInput::make('title')->required($isDefault)->maxLength(150)], 'Title'),
                            ]),
                            Translations::repeater(Repeater::make('specs')
                                ->relationship('specs', fn (Builder $query) => $query->with('translations'))
                                ->orderColumn('sort')
                                ->defaultItems(0)
                                ->grid(2)
                                ->addActionLabel(__('Add figure'))
                                ->schema([
                                    Grid::make(2)->schema([
                                        TextInput::make('value')->required()->maxLength(50),
                                        MediaPicker::make('icon_media_id', 'Icon'),
                                    ]),
                                    Translations::tabs(fn (Language $language, bool $isDefault) => [TextInput::make('label')->required($isDefault)->maxLength(150)], 'Label'),
                                ])),
                        ])),
                ]),
                Tab::make('Seasonality')->icon('heroicon-o-calendar')->schema([
                    Translations::repeater(Repeater::make('seasons')
                        ->hiddenLabel()
                        ->relationship('seasons', fn (Builder $query) => $query->with('translations'))
                        ->orderColumn('sort')
                        ->reorderableWithDragAndDrop()
                        ->defaultItems(0)
                        ->itemLabel(fn (array $state) => $state[Language::defaultCode()]['label'] ?? null)
                        ->addActionLabel(__('Add season row'))
                        ->schema([
                            Translations::tabs(fn (Language $language, bool $isDefault) => [TextInput::make('label')->label('Variety / label')->required($isDefault)->maxLength(150)], 'Label'),
                            CheckboxList::make('months')->options($months)->columns(6)->required(),
                        ])),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['translations', 'category.translations', 'card']))
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                ImageColumn::make('card_url')->label('')->state(fn (Product $record) => $record->card?->url(480))->imageHeight(40),
                TextColumn::make('name')->searchable(query: fn (Builder $query, string $search) => $query->whereTranslationLike('name', "%{$search}%")),
                TextColumn::make('category.name')->label('Category')->placeholder('—'),
                TextColumn::make('status')->badge()->color(fn (ContentStatus $state) => $state === ContentStatus::Published ? 'success' : 'gray'),
                IconColumn::make('is_featured')->label('Featured')->boolean(),
                TextColumn::make('updated_at')->since()->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('category_id')->label('Category')->options(fn () => CategoryResource::parentOptions(null)),
                SelectFilter::make('status')->options(ContentStatus::class),
                TernaryFilter::make('is_featured')->label('Featured'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('view')->icon('heroicon-o-arrow-top-right-on-square')->url(fn (Product $record) => SiteIndex::url($record))->openUrlInNewTab()->visible(fn (Product $record) => SiteIndex::url($record) !== null),
                EditAction::make(),
                ReplicateAction::make()
                    ->label('Duplicate')
                    ->using(fn (Product $record) => self::duplicate($record))
                    ->successRedirectUrl(fn (Product $replica) => self::getUrl('edit', ['record' => $replica])),
                DeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('publish')->icon('heroicon-o-eye')->requiresConfirmation()->action(fn (Collection $records) => $records->each->update(['status' => ContentStatus::Published]))->deselectRecordsAfterCompletion(),
                    BulkAction::make('unpublish')->icon('heroicon-o-eye-slash')->requiresConfirmation()->action(fn (Collection $records) => $records->each->update(['status' => ContentStatus::Draft]))->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Deep copy (translations, varieties, packaging, seasons, gallery) as an unpublished draft.
     */
    public static function duplicate(Product $product): Product
    {
        return DB::transaction(function () use ($product) {
            $product->load(['translations', 'varieties.translations', 'packagings.translations', 'packagings.specs.translations', 'seasons.translations', 'imageItems']);
            $copy = $product->replicate(['status']);
            $copy->status = ContentStatus::Draft;
            foreach ($product->translations as $translation) {
                $copy->translateOrNew($translation->locale)->fill([
                    ...$translation->only($product->translatedAttributes),
                    'name' => $translation->name.' (copy)',
                    'slug' => $translation->slug.'-copy-'.substr(uniqid(), -4),
                ]);
            }
            $copy->save();

            $copyChildren = function (iterable $items, callable $create, ?callable $after = null) {
                foreach ($items as $item) {
                    $new = $create($item->replicate()->attributesToArray());
                    foreach ($item->translations ?? [] as $translation) {
                        $new->translateOrNew($translation->locale)->fill($translation->only($item->translatedAttributes));
                    }
                    $new->save();
                    $after && $after($item, $new);
                }
            };
            $copyChildren($product->varieties, fn ($attributes) => $copy->varieties()->make($attributes));
            $copyChildren($product->seasons, fn ($attributes) => $copy->seasons()->make($attributes));
            $copyChildren($product->packagings, fn ($attributes) => $copy->packagings()->make($attributes), fn ($old, $new) => $copyChildren($old->specs, fn ($attributes) => $new->specs()->make($attributes)));
            foreach ($product->imageItems as $image) {
                $copy->imageItems()->create($image->only(['media_id', 'sort']));
            }

            return $copy;
        });
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}

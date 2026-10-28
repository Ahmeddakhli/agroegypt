<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Support\MediaPicker;
use App\Filament\Support\TranslatesResourceLabels;
use App\Filament\Support\Translations;
use App\Models\Category;
use App\Models\Language;
use App\Support\CatalogSlug;
use App\Support\SiteIndex;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class CategoryResource extends Resource
{
    use TranslatesResourceLabels;

    protected static ?string $model = Category::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Translations::tabs(fn (Language $language, bool $isDefault) => [
                Translations::nameField('name', $isDefault),
                Translations::slugField($language, $isDefault, fn (string $locale, string $slug, Get $get, ?Model $record) => CatalogSlug::isTaken(
                    $locale, $slug, $get('../parent_id') ? (int) $get('../parent_id') : null, $record,
                )),
                TextInput::make('menu_label')->label('Menu label')->helperText(__('Optional shorter name for menus.'))->maxLength(100),
                RichEditor::make('description')->label('Intro text above the cards')->toolbarButtons(['bold', 'italic', 'link', 'bulletList', 'orderedList']),
                TextInput::make('meta_title')->label('SEO title')->maxLength(190),
                Textarea::make('meta_description')->label('SEO description')->rows(2)->maxLength(300),
            ]),
            Section::make(__('Settings'))->columns(2)->schema([
                Select::make('parent_id')
                    ->label('Parent category')
                    ->placeholder(__('— Top level —'))
                    ->options(fn (?Model $record) => self::parentOptions($record))
                    ->live(),
                TextInput::make('sort')->numeric()->default(0)->minValue(0),
                Toggle::make('is_active')->label('Visible on the website')->default(true),
            ]),
            Section::make(__('Images'))->columns(3)->schema([
                MediaPicker::make('card_media_id', 'Card image (listing)'),
                MediaPicker::make('icon_media_id', 'Icon'),
                MediaPicker::make('hero_media_id', 'Page header image'),
            ]),
        ]);
    }

    /**
     * Every category except the record itself and its descendants (prevents loops).
     *
     * @return array<int, string>
     */
    public static function parentOptions(?Model $record): array
    {
        $all = Category::with('translations')->orderBy('sort')->get();
        $excluded = $record ? [$record->getKey()] : [];
        do {
            $before = count($excluded);
            $excluded = array_unique([...$excluded, ...$all->whereIn('parent_id', $excluded)->pluck('id')->all()]);
        } while (count($excluded) > $before);

        $label = function (Category $category) use ($all, &$label): string {
            $parent = $all->firstWhere('id', $category->parent_id);

            return ($parent ? $label($parent).' › ' : '').$category->name;
        };

        return $all->whereNotIn('id', $excluded)->mapWithKeys(fn (Category $c) => [$c->id => $label($c)])->sort()->all();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['translations', 'parent.translations', 'card'])->withCount('products'))
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                ImageColumn::make('card_url')->label('')->state(fn (Category $record) => $record->card?->url(480))->imageHeight(40),
                TextColumn::make('name')
                    ->searchable(query: fn (Builder $query, string $search) => $query->whereTranslationLike('name', "%{$search}%")),
                TextColumn::make('parent.name')->label('Parent')->placeholder('—'),
                TextColumn::make('products_count')->label('Products')->badge(),
                ToggleColumn::make('is_active')->label('Visible'),
            ])
            ->filters([
                SelectFilter::make('parent_id')->label('Parent')->options(fn () => self::parentOptions(null)),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('view')->icon('heroicon-o-arrow-top-right-on-square')->url(fn (Category $record) => SiteIndex::url($record))->openUrlInNewTab()->visible(fn (Category $record) => SiteIndex::url($record) !== null),
                EditAction::make(),
                DeleteAction::make()->disabled(fn (Category $record) => $record->products_count > 0 || $record->children()->exists())
                    ->tooltip(fn (Category $record) => $record->products_count > 0 || $record->children()->exists() ? __('Move or delete its products and subcategories first.') : null),
                RestoreAction::make(),
            ]);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }
}

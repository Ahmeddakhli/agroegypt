<?php

namespace App\Filament\Resources\Pages;

use App\Enums\ContentStatus;
use App\Enums\SectionType;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Filament\Support\MediaPicker;
use App\Filament\Support\TranslatesResourceLabels;
use App\Filament\Support\Translations;
use App\Models\Document;
use App\Models\Language;
use App\Models\Page;
use App\Models\Slider;
use App\Support\SiteIndex;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use UnitEnum;

class PageResource extends Resource
{
    use TranslatesResourceLabels;

    protected static ?string $model = Page::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 1;

    /** @var array<string, string> */
    public const TEMPLATES = [
        Page::TEMPLATE_DEFAULT => 'Standard page',
        Page::TEMPLATE_HOME => 'Home page',
        Page::TEMPLATE_PRODUCTS => 'Products hub (catalog URLs start here)',
        Page::TEMPLATE_GALLERY => 'Gallery hub (album URLs start here)',
    ];

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('page')->persistTabInQueryString()->columnSpanFull()->tabs([
                Tab::make('Content & SEO')->icon('heroicon-o-document-text')->schema([
                    Translations::tabs(fn (Language $language, bool $isDefault) => [
                        Grid::make(2)->schema([
                            Translations::nameField('title', $isDefault, 'Title'),
                            Translations::slugField($language, $isDefault, fn (string $locale, string $slug, Get $get, ?Model $record) => Page::withTrashed()
                                ->when($record, fn (Builder $q) => $q->whereKeyNot($record->getKey()))
                                ->whereHas('translations', fn (Builder $q) => $q->where('locale', $locale)->where('slug', $slug))->exists()),
                            TextInput::make('meta_title')->label('SEO title')->helperText(__('Empty = generated from the title.'))->maxLength(190),
                            TextInput::make('meta_keywords')->label('SEO keywords')->maxLength(255),
                        ]),
                        Textarea::make('meta_description')->label('SEO description')->rows(2)->maxLength(300),
                        MediaPicker::make('og_media_id', 'Social sharing image')->helperText(__('Shown when the page is shared on social media. Empty = the default sharing image.')),
                    ]),
                ]),
                Tab::make('Sections')->icon('heroicon-o-squares-plus')->schema([self::sectionsRepeater()]),
                Tab::make('Publishing')->icon('heroicon-o-calendar')->columns(2)->schema([
                    Select::make('template')->options(array_map('__', self::TEMPLATES))->default(Page::TEMPLATE_DEFAULT)->required()
                        ->rule(fn (?Model $record) => function (string $attribute, mixed $value, Closure $fail) use ($record) {
                            if ($value !== Page::TEMPLATE_DEFAULT && Page::where('template', $value)->when($record, fn ($q) => $q->whereKeyNot($record->getKey()))->exists()) {
                                $fail(__('Another page already uses this template.'));
                            }
                        }),
                    ToggleButtons::make('status')->options(ContentStatus::class)->default(ContentStatus::Draft)->inline()->required(),
                    DateTimePicker::make('published_at')->label('Publish at')->helperText(__('Optional: schedule a published page for later.'))->seconds(false),
                    TextInput::make('sort')->numeric()->default(0),
                    Toggle::make('show_hero')->label('Show page header with title and breadcrumb')->default(true),
                    MediaPicker::make('hero_media_id', 'Page header image'),
                ]),
            ]),
        ]);
    }

    private static function sectionsRepeater(): Repeater
    {
        $type = fn (Get $get): ?SectionType => self::sectionType($get);
        $uses = fn (string $field) => fn (Get $get) => (bool) $type($get)?->uses($field);
        $itemUses = fn (string $field) => fn (Get $get) => (bool) $type($get)?->uses('item.'.$field);
        $canUseHtml = fn () => auth()->user()?->can('update settings') ?? false;

        return Translations::repeater(Repeater::make('sections')
            ->hiddenLabel()
            ->relationship('sections', fn (Builder $query) => $query->with(['translations', 'items.translations']))
            ->orderColumn('sort')
            ->reorderableWithDragAndDrop()
            ->reorderableWithButtons()
            ->collapsible()
            ->collapsed()
            ->cloneable()
            ->defaultItems(0)
            ->addActionLabel(__('Add section'))
            ->itemLabel(fn (array $state) => trim(($state['type'] instanceof SectionType ? $state['type']->label() : (SectionType::tryFrom((string) $state['type'])?->label() ?? __('New section')))
                .' — '.($state[Language::defaultCode()]['title'] ?? ''), ' —').(($state['is_visible'] ?? true) ? '' : ' '.__('(hidden)')))
            ->schema([
                Grid::make(4)->schema([
                    Select::make('type')
                        ->options(collect(SectionType::cases())->mapWithKeys(fn (SectionType $t) => [$t->value => $t->label()])->all())
                        ->disableOptionWhen(fn (string $value) => $value === SectionType::CustomHtml->value && ! $canUseHtml())
                        ->required()
                        ->live()
                        ->columnSpan(2),
                    Toggle::make('is_visible')->label('Visible')->default(true)->inline(false),
                    Select::make('settings.background')->label('Background')
                        ->options(['white' => __('White'), 'dark' => __('Dark green'), 'dark-2' => __('Secondary dark'), 'deep' => __('Deepest green'), 'olive' => __('Olive band')])
                        ->placeholder(__('Default for this section')),
                    Select::make('settings.animation')->label('Entrance animation')
                        ->options(['none' => __('None'), 'fade' => __('Fade in'), 'slide' => __('Slide up'), 'zoom' => __('Zoom in')])->default('none'),
                    Select::make('settings.slider_key')->label('Slider')->options(fn () => Slider::pluck('name', 'key')->all())->visible($uses('slider_key')),
                    MediaPicker::make('media_id', 'Image')->visible($uses('media')),
                    Select::make('settings.document_id')
                        ->label('Button downloads document')
                        ->options(fn () => Document::with('translations')->orderBy('type')->orderBy('sort')->get()->mapWithKeys(fn (Document $document) => [$document->id => __(ucfirst($document->type)).': '.$document->title])->all())
                        ->placeholder(__('— Use the button link instead —'))
                        ->helperText(__('Linking a document keeps the button working when its file is replaced.'))
                        ->visible($uses('document')),
                    Toggle::make('settings.media_as_background')->label('Use image as background')->visible($uses('media_as_background'))->inline(false),
                    Select::make('settings.image_position')->label('Image side')->options(['start' => __('Start (left in English)'), 'end' => __('End')])->default('start')->visible($uses('image_position')),
                    TextInput::make('settings.image_offset')->label('Image overlap (px)')->numeric()->minValue(0)->maxValue(400)->visible($uses('image_offset')),
                    Select::make('settings.layout')->label('Layout')->options(['default' => __('Image + text'), 'split_heading' => __('Heading beside text')])->default('default')->visible($uses('layout')),
                    Select::make('settings.columns')->label('Columns')->options(array_combine(range(2, 6), range(2, 6)))->default(3)->visible($uses('columns')),
                    Select::make('settings.caption')->label('Captions')->options(['bold' => __('Bold'), 'normal' => __('Normal')])->default('bold')->visible($uses('caption')),
                    Toggle::make('settings.rounded')->label('Rounded images')->visible($uses('rounded'))->inline(false),
                    Toggle::make('settings.divider')->label('Divider below text')->visible($uses('divider'))->inline(false),
                    Toggle::make('settings.divider_after')->label('Divider after grid')->visible($uses('divider_after'))->inline(false),
                    Select::make('settings.align')->label('Text alignment')->options(['start' => __('Start'), 'center' => __('Center')])->visible($uses('align')),
                    Toggle::make('settings.boxed')->label('Contained width')->default(true)->visible($uses('boxed'))->inline(false),
                ]),
                Translations::tabs(fn (Language $language) => [
                    Grid::make(2)->schema([
                        TextInput::make('title')->maxLength(255)->visible($uses('title')),
                        TextInput::make('subtitle')->label('Subtitle / caption')->maxLength(255)->visible($uses('subtitle')),
                        TextInput::make('button_label')->label('Button label')->maxLength(100)->visible($uses('button_label')),
                        TextInput::make('button_url')
                            ->label(fn (Get $get) => match ($type($get)) {
                                SectionType::Map => 'Google Maps embed URL',
                                SectionType::Video => 'YouTube / Vimeo URL',
                                default => 'Link',
                            })
                            ->placeholder('/contact-us or https://…')
                            ->maxLength(1000)
                            ->regex(Translations::LINK_PATTERN)
                            ->visible($uses('button_url')),
                    ]),
                    RichEditor::make('body')->label('Text')->visible($uses('body'))
                        ->toolbarButtons(['bold', 'italic', 'link', 'h3', 'bulletList', 'orderedList']),
                    Textarea::make('body_html')->label('HTML (sanitized: scripts, styles, iframes and event handlers are removed)')
                        ->rows(10)->visible($uses('html'))->extraInputAttributes(['class' => 'font-mono', 'dir' => 'ltr']),
                ], 'Section text'),
                Translations::repeater(Repeater::make('items')
                    ->relationship('items', fn (Builder $query) => $query->with('translations'))
                    ->visible($uses('items'))
                    ->orderColumn('sort')
                    ->reorderableWithDragAndDrop()
                    ->collapsible()
                    ->defaultItems(0)
                    ->grid(2)
                    ->itemLabel(fn (array $state) => $state[Language::defaultCode()]['title'] ?? null)
                    ->addActionLabel(__('Add item'))
                    ->schema([
                        Grid::make(2)->schema([
                            MediaPicker::make('icon_media_id', 'Icon')->visible($itemUses('icon')),
                            MediaPicker::make('media_id', 'Image')->visible($itemUses('media')),
                            TextInput::make('url')->label('Link')->maxLength(300)->regex(Translations::LINK_PATTERN)->visible($itemUses('url')),
                            TextInput::make('value')->label('Value (%)')->numeric()->minValue(0)->maxValue(100)->visible($itemUses('value')),
                        ]),
                        Translations::tabs(fn (Language $language) => [
                            TextInput::make('title')->maxLength(255)->visible($itemUses('title')),
                            Textarea::make('body')->label('Text (one country per line for market regions)')->rows(3)->visible($itemUses('body')),
                        ], 'Item text'),
                    ])),
            ]), afterFill: self::htmlToForm(...), beforeSave: self::htmlFromForm(...));
    }

    /**
     * Custom HTML is edited in its own textarea ("body_html") because two fields cannot share the
     * "body" state path: the hidden one would erase the visible one's value on save.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function htmlToForm(array $data): array
    {
        foreach (Language::cached() as $language) {
            if (is_array($data[$language->code] ?? null)) {
                $data[$language->code]['body_html'] = $data[$language->code]['body'] ?? null;
            }
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function htmlFromForm(array $data): array
    {
        $type = $data['type'] instanceof SectionType ? $data['type'] : SectionType::tryFrom((string) ($data['type'] ?? ''));

        foreach (Language::cached() as $language) {
            if (! is_array($data[$language->code] ?? null)) {
                continue;
            }
            if ($type === SectionType::CustomHtml) {
                $data[$language->code]['body'] = $data[$language->code]['body_html'] ?? null;
            }
            unset($data[$language->code]['body_html']);
        }

        return $data;
    }

    /**
     * The type of the section a field belongs to. Fields sit at different depths (section level, inside a
     * language group, inside an item, inside an item's language group) and relative paths resolve from the
     * field's container, so walk up until the section's "type" is found.
     */
    private static function sectionType(Get $get): ?SectionType
    {
        foreach (['type', '../type', '../../type', '../../../type'] as $path) {
            $value = $get($path);
            $type = $value instanceof SectionType ? $value : SectionType::tryFrom((string) $value);
            if ($type !== null) {
                return $type;
            }
        }

        return null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('translations')->withCount('sections'))
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('title')->searchable(query: fn (Builder $query, string $search) => $query->whereTranslationLike('title', "%{$search}%")),
                TextColumn::make('path')->label('URL')->state(fn (Page $record) => '/'.(SiteIndex::path($record, Language::defaultCode()) ?? $record->translate(Language::defaultCode())?->slug))->fontFamily('mono')->color('gray'),
                TextColumn::make('template')->formatStateUsing(fn (string $state) => __(self::TEMPLATES[$state] ?? $state))->badge()->color('gray'),
                TextColumn::make('status')->badge()->color(fn (ContentStatus $state) => $state === ContentStatus::Published ? 'success' : 'gray'),
                TextColumn::make('published_at')->label('Scheduled')->dateTime()->placeholder('—')->toggleable(),
                TextColumn::make('sections_count')->label('Sections')->badge(),
                TextColumn::make('updated_at')->since()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(ContentStatus::class),
                SelectFilter::make('template')->options(array_map('__', self::TEMPLATES)),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('preview')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn (Page $record) => self::previewUrl($record))
                    ->openUrlInNewTab(),
                EditAction::make(),
                ReplicateAction::make()->label('Duplicate')->using(fn (Page $record) => self::duplicate($record))
                    ->successRedirectUrl(fn (Page $replica) => self::getUrl('edit', ['record' => $replica])),
                DeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('publish')->icon('heroicon-o-eye')->requiresConfirmation()->action(fn (Collection $records) => $records->each->update(['status' => ContentStatus::Published]))->deselectRecordsAfterCompletion(),
                    BulkAction::make('unpublish')->icon('heroicon-o-eye-slash')->requiresConfirmation()->action(fn (Collection $records) => $records->each->update(['status' => ContentStatus::Draft]))->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Signed, expiring link that renders the page as saved, even when it is a draft or scheduled.
     */
    public static function previewUrl(Page $page, ?string $locale = null): string
    {
        return URL::temporarySignedRoute('pages.preview', now()->addHour(), ['locale' => $locale ?? Language::defaultCode(), 'page' => $page->getKey()]);
    }

    /**
     * Deep copy with sections, items and translations, as a draft with "-copy" slugs.
     */
    public static function duplicate(Page $page): Page
    {
        return DB::transaction(function () use ($page) {
            $page->load(['translations', 'sections.translations', 'sections.items.translations']);
            $copy = $page->replicate(['status', 'template']);
            $copy->status = ContentStatus::Draft;
            $copy->template = Page::TEMPLATE_DEFAULT;
            foreach ($page->translations as $translation) {
                $copy->translateOrNew($translation->locale)->fill([
                    ...$translation->only($page->translatedAttributes),
                    'title' => $translation->title.' (copy)',
                    'slug' => $translation->slug.'-copy-'.substr(uniqid(), -4),
                ]);
            }
            $copy->save();

            foreach ($page->sections as $section) {
                $newSection = $copy->sections()->make($section->only(['type', 'sort', 'is_visible', 'media_id', 'settings']));
                foreach ($section->translations as $translation) {
                    $newSection->translateOrNew($translation->locale)->fill($translation->only($section->translatedAttributes));
                }
                $newSection->save();

                foreach ($section->items as $item) {
                    $newItem = $newSection->items()->make($item->only(['sort', 'media_id', 'icon_media_id', 'url', 'value']));
                    foreach ($item->translations as $translation) {
                        $newItem->translateOrNew($translation->locale)->fill($translation->only($item->translatedAttributes));
                    }
                    $newItem->save();
                }
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
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }
}

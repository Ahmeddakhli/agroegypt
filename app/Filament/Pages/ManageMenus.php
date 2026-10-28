<?php

namespace App\Filament\Pages;

use App\Filament\Support\TranslatesPageLabels;
use App\Filament\Support\Translations;
use App\Models\Category;
use App\Models\Gallery;
use App\Models\Language;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Product;
use App\Support\MenuTree;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page as FilamentPage;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * Visual menu editor: the selected menu as an indented tree, with linking, ordering among siblings,
 * per-language labels and a live preview of the resulting navigation.
 */
class ManageMenus extends FilamentPage implements HasTable
{
    use InteractsWithTable, TranslatesPageLabels;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Menus';

    protected static ?string $title = 'Menus';

    protected static ?int $navigationSort = 2;

    /** @var array<string, array{0: class-string<Model>, 1: string}> link type => [model, label] */
    private const LINK_TYPES = [
        'page' => [Page::class, 'Page'],
        'category' => [Category::class, 'Product category'],
        'product' => [Product::class, 'Product'],
        'gallery' => [Gallery::class, 'Gallery album'],
    ];

    #[Url]
    public string $location = 'header';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view menus') ?? false;
    }

    public function mount(): void
    {
        foreach (['header' => 'Header', 'footer' => 'Footer'] as $location => $name) {
            Menu::firstOrCreate(['location' => $location], ['name' => $name]);
        }
    }

    public function menu(): Menu
    {
        return Menu::where('location', $this->location)->firstOrFail();
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('menus')
                ->tabs(Menu::orderBy('id')->get()->map(fn (Menu $menu) => Tab::make($menu->name)
                    ->key('menu-'.$menu->location)
                    ->extraAttributes(['wire:click' => "\$set('location', '{$menu->location}')"]))->all())
                ->activeTab(Menu::orderBy('id')->pluck('location')->search($this->location) + 1)
                ->contained(false),
            EmbeddedTable::make(),
            Section::make(__('Preview'))
                ->description(__('How the saved menu renders on the website.'))
                ->collapsible()
                ->schema([View::make('admin.menu-preview')->viewData(fn () => ['tree' => $this->previewTree()])]),
        ]);
    }

    /**
     * @return array<string, list<array<string, mixed>>> language native name => menu tree
     */
    public function previewTree(): array
    {
        $current = app()->getLocale();
        $trees = [];
        foreach (Language::active() as $language) {
            app()->setLocale($language->code);
            $trees[$language->native_name] = MenuTree::for($this->location);
        }
        app()->setLocale($current);

        return $trees;
    }

    public function table(Table $table): Table
    {
        $ordered = $this->treeOrder();

        return $table
            ->query(fn () => MenuItem::query()->where('menu_id', $this->menu()->id)->with(['translations', 'linkable.translations']))
            ->modifyQueryUsing(fn (Builder $query) => $ordered === [] ? $query : $query->orderByRaw(
                'CASE id '.collect($ordered)->map(fn ($id, $position) => 'WHEN '.(int) $id.' THEN '.(int) $position)->implode(' ').' ELSE 99999 END'
            ))
            ->paginated(false)
            ->columns([
                TextColumn::make('label')
                    ->label('Label')
                    ->formatStateUsing(fn (?string $state, MenuItem $record) => str_repeat('— ', $this->depth($record)).$state)
                    ->weight(fn (MenuItem $record) => $record->parent_id ? null : 'bold'),
                TextColumn::make('target_description')
                    ->label('Links to')
                    ->state(fn (MenuItem $record) => $record->linkable
                        ? __(self::LINK_TYPES[$this->linkType($record)][1] ?? 'Item').': '.($record->linkable->name ?? $record->linkable->title)
                        : ($record->url ?: '—'))
                    ->color('gray'),
                IconColumn::make('target')->label('New tab')->state(fn (MenuItem $record) => $record->target === '_blank')->boolean(),
                ToggleColumn::make('is_visible')->label('Visible')->disabled(fn () => ! $this->canEdit()),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add menu item')
                    ->model(MenuItem::class)
                    ->schema($this->itemForm())
                    ->visible(fn () => $this->canEdit())
                    ->mutateDataUsing(function (array $data): array {
                        $data = $this->prepare($data);
                        Translations::strip($data);

                        return $data + ['menu_id' => $this->menu()->id, 'sort' => (int) MenuItem::where('menu_id', $this->menu()->id)->max('sort') + 1];
                    }),
            ])
            ->recordActions([
                Action::make('up')->icon('heroicon-o-arrow-up')->iconButton()->color('gray')->tooltip(__('Move up'))
                    ->visible(fn () => $this->canEdit())->action(fn (MenuItem $record) => $this->move($record, -1)),
                Action::make('down')->icon('heroicon-o-arrow-down')->iconButton()->color('gray')->tooltip(__('Move down'))
                    ->visible(fn () => $this->canEdit())->action(fn (MenuItem $record) => $this->move($record, 1)),
                EditAction::make()
                    ->schema($this->itemForm())
                    ->visible(fn () => $this->canEdit())
                    ->fillForm(fn (MenuItem $record) => [
                        ...$record->only(['parent_id', 'url', 'target', 'is_visible']),
                        'link_type' => $record->linkable_type ? $this->linkType($record) : 'url',
                        'linkable_id' => $record->linkable_id,
                        ...Translations::fill($record),
                    ])
                    ->using(function (MenuItem $record, array $data) {
                        $data = $this->prepare($data);
                        $emptied = Translations::strip($data);
                        $record->update($data);
                        Translations::forget($record, $emptied);

                        return $record;
                    }),
                DeleteAction::make()->visible(fn () => auth()->user()?->can('delete menus'))->modalDescription(__('Sub-items are deleted too.')),
            ]);
    }

    /**
     * @return list<mixed>
     */
    private function itemForm(): array
    {
        return [
            Translations::tabs(fn (Language $language, bool $isDefault) => [TextInput::make('label')->required($isDefault)->maxLength(100)], 'Label'),
            Grid::make(2)->schema([
                Select::make('link_type')
                    ->label('Link to')
                    ->options([...collect(self::LINK_TYPES)->map(fn ($type) => __($type[1]))->all(), 'url' => __('Custom URL')])
                    ->default('page')
                    ->required()
                    ->live(),
                Select::make('linkable_id')
                    ->label('Item')
                    ->options(fn (Get $get) => isset(self::LINK_TYPES[$get('link_type')])
                        ? self::LINK_TYPES[$get('link_type')][0]::with('translations')->get()->mapWithKeys(fn (Model $model) => [$model->getKey() => $model->name ?? $model->title])->all()
                        : [])
                    ->searchable()
                    ->required(fn (Get $get) => $get('link_type') !== 'url')
                    ->visible(fn (Get $get) => $get('link_type') !== 'url'),
                TextInput::make('url')
                    ->label('URL')
                    ->placeholder('/contact-us, https://…, mailto:…')
                    ->regex(Translations::LINK_PATTERN)
                    ->required(fn (Get $get) => $get('link_type') === 'url')
                    ->visible(fn (Get $get) => $get('link_type') === 'url'),
                Select::make('parent_id')
                    ->label('Parent item')
                    ->placeholder(__('— Top level —'))
                    ->options(fn (?MenuItem $record) => MenuItem::where('menu_id', $this->menu()->id)
                        ->when($record, fn (Builder $q) => $q->whereNotIn('id', $this->descendantIds($record)))
                        ->with('translations')->get()->mapWithKeys(fn (MenuItem $item) => [$item->id => str_repeat('— ', $this->depth($item)).$item->label])->all()),
                Select::make('target')->options(['_self' => __('Same tab'), '_blank' => __('New tab')])->default('_self')->required(),
                Toggle::make('is_visible')->label('Visible')->default(true),
            ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepare(array $data): array
    {
        $type = $data['link_type'] ?? 'url';
        unset($data['link_type']);

        if (isset(self::LINK_TYPES[$type])) {
            $data['linkable_type'] = (new (self::LINK_TYPES[$type][0]))->getMorphClass();
            $data['url'] = null;
        } else {
            $data['linkable_type'] = null;
            $data['linkable_id'] = null;
        }

        return $data;
    }

    private function linkType(MenuItem $item): ?string
    {
        foreach (self::LINK_TYPES as $type => [$class]) {
            if ((new $class)->getMorphClass() === $item->linkable_type) {
                return $type;
            }
        }

        return null;
    }

    /**
     * Swap position with the previous/next sibling.
     */
    private function move(MenuItem $item, int $direction): void
    {
        $siblings = MenuItem::where('menu_id', $item->menu_id)->where('parent_id', $item->parent_id)->orderBy('sort')->orderBy('id')->get()->values();
        $index = $siblings->search(fn (MenuItem $sibling) => $sibling->is($item));
        $other = $siblings->get($index + $direction);

        if ($other === null) {
            return;
        }

        DB::transaction(function () use ($siblings, $index, $direction) {
            $ordered = $siblings->all();
            [$ordered[$index], $ordered[$index + $direction]] = [$ordered[$index + $direction], $ordered[$index]];
            foreach ($ordered as $position => $sibling) {
                $sibling->update(['sort' => $position]);
            }
        });
    }

    /**
     * @return list<int> item ids in depth-first menu order
     */
    private function treeOrder(): array
    {
        $menu = Menu::where('location', $this->location)->first();
        if ($menu === null) {
            return [];
        }

        $byParent = MenuItem::where('menu_id', $menu->id)->orderBy('sort')->orderBy('id')->get(['id', 'parent_id'])->groupBy(fn ($item) => $item->parent_id ?? 0);
        $ordered = [];
        $walk = function (int $parent) use (&$walk, &$ordered, $byParent) {
            foreach ($byParent->get($parent, collect()) as $item) {
                $ordered[] = $item->id;
                $walk($item->id);
            }
        };
        $walk(0);

        return $ordered;
    }

    /** @var array<int, Collection<int, int|null>> per-request parent maps by menu id */
    private array $parentMaps = [];

    private function depth(MenuItem $item): int
    {
        $depth = 0;
        $parents = $this->parentMaps[$item->menu_id] ??= MenuItem::where('menu_id', $item->menu_id)->pluck('parent_id', 'id');
        $parent = $item->parent_id;
        while ($parent !== null && $depth < 10) {
            $depth++;
            $parent = $parents[$parent] ?? null;
        }

        return $depth;
    }

    /**
     * @return list<int>
     */
    private function descendantIds(MenuItem $item): array
    {
        $ids = [$item->id];
        $children = MenuItem::where('menu_id', $item->menu_id)->pluck('parent_id', 'id');
        do {
            $before = count($ids);
            $ids = array_values(array_unique([...$ids, ...$children->filter(fn ($parent) => in_array($parent, $ids, true))->keys()->all()]));
        } while (count($ids) > $before);

        return $ids;
    }

    private function canEdit(): bool
    {
        return auth()->user()?->can('update menus') ?? false;
    }
}

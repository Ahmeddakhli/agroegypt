<?php

namespace App\Filament\Resources\Sliders;

use App\Filament\Resources\Sliders\Pages\CreateSlider;
use App\Filament\Resources\Sliders\Pages\EditSlider;
use App\Filament\Resources\Sliders\Pages\ListSliders;
use App\Filament\Support\MediaPicker;
use App\Filament\Support\TranslatesResourceLabels;
use App\Filament\Support\Translations;
use App\Models\Language;
use App\Models\Slider;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class SliderResource extends Resource
{
    use TranslatesResourceLabels;

    protected static ?string $model = Slider::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFilm;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Sliders';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('Behaviour'))->columns(4)->schema([
                TextInput::make('name')->required()->maxLength(100),
                TextInput::make('key')->label('Key')->helperText(__('Referenced by the "Hero slider" page section.'))->required()->alphaDash()->maxLength(50)->unique(ignoreRecord: true),
                ToggleButtons::make('effect')->label('Transition')->options(['zoom' => __('Zoom (original)'), 'fade' => __('Fade'), 'slide' => __('Slide')])->default('zoom')->inline()->required(),
                Toggle::make('autoplay')->default(true),
                TextInput::make('interval_ms')->label('Time per slide (ms)')->numeric()->minValue(1500)->maxValue(30000)->default(5000)->required(),
                TextInput::make('transition_ms')->label('Transition duration (ms)')->numeric()->minValue(200)->maxValue(5000)->default(2000)->required(),
                Toggle::make('show_arrows')->label('Show arrows')->default(true),
                Grid::make(3)->columnSpanFull()->schema([
                    TextInput::make('height_desktop')->label('Height desktop (px)')->numeric()->minValue(200)->maxValue(1200)->default(813)->required(),
                    TextInput::make('height_tablet')->label('Height tablet (px)')->numeric()->minValue(200)->maxValue(1200)->default(600)->required(),
                    TextInput::make('height_mobile')->label('Height mobile (px)')->numeric()->minValue(150)->maxValue(1200)->default(450)->required(),
                ]),
            ]),
            Section::make(__('Slides'))->description(__('Drag to reorder.'))->schema([
                Translations::repeater(Repeater::make('slides')
                    ->hiddenLabel()
                    ->relationship('slides', fn (Builder $query) => $query->with('translations'))
                    ->orderColumn('sort')
                    ->reorderableWithDragAndDrop()
                    ->collapsible()
                    ->defaultItems(0)
                    ->itemLabel(fn (array $state) => $state[Language::defaultCode()]['title'] ?? __('Slide'))
                    ->addActionLabel(__('Add slide'))
                    ->schema([
                        Grid::make(4)->schema([
                            MediaPicker::make('media_id', 'Image')->required(),
                            MediaPicker::make('mobile_media_id', 'Mobile image (optional)'),
                            TextInput::make('url')->label('Button link')->placeholder('/contact-us or https://…')->maxLength(300)->regex(Translations::LINK_PATTERN),
                            Toggle::make('is_active')->label('Active')->default(true),
                        ]),
                        Translations::tabs(fn (Language $language) => [
                            Grid::make(3)->schema([
                                TextInput::make('title')->maxLength(150),
                                TextInput::make('subtitle')->maxLength(255),
                                TextInput::make('button_label')->maxLength(60),
                            ]),
                        ], 'Text'),
                    ])),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('slides'))
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('key')->badge(),
                TextColumn::make('effect'),
                TextColumn::make('slides_count')->label('Slides')->badge(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSliders::route('/'),
            'create' => CreateSlider::route('/create'),
            'edit' => EditSlider::route('/{record}/edit'),
        ];
    }
}

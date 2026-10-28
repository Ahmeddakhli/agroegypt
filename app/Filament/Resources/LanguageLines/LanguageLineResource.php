<?php

namespace App\Filament\Resources\LanguageLines;

use App\Filament\Resources\LanguageLines\Pages\ManageLanguageLines;
use App\Filament\Support\TranslatesResourceLabels;
use App\Models\Language;
use App\Models\LanguageLine;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Translation editor for UI strings: one editable column per language, side by side.
 */
class LanguageLineResource extends Resource
{
    use TranslatesResourceLabels;

    protected static ?string $model = LanguageLine::class;

    protected static ?string $recordTitleAttribute = 'key';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Localization';

    protected static ?string $navigationLabel = 'Interface translations';

    protected static ?string $modelLabel = 'translation';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view translations') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create translations') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('update translations') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete translations') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        $languages = Language::cached();

        return $schema->components([
            Grid::make(2)->schema([
                TextInput::make('group')->required()->maxLength(100)->default('site')->datalist(LanguageLine::GROUPS)->alphaDash(),
                TextInput::make('key')->required()->maxLength(190)->regex('/^[\w.\-]+$/'),
            ]),
            Grid::make(min(3, max(1, $languages->count())))->schema(
                $languages->map(fn (Language $language) => Textarea::make("text.{$language->code}")
                    ->label($language->native_name.($language->is_default ? ' '.__('(default)') : ''))
                    ->rows(3)
                    ->maxLength(5000)
                    ->extraInputAttributes(['dir' => $language->direction, 'lang' => $language->code]))->all()
            ),
        ]);
    }

    public static function table(Table $table): Table
    {
        $languages = Language::cached();

        return $table
            ->defaultSort('group')
            ->defaultPaginationPageOption(50)
            ->columns([
                TextColumn::make('group')->badge()->sortable(),
                TextColumn::make('key')->searchable()->sortable()->wrap()->fontFamily('mono')->size('xs'),
                ...$languages->map(fn (Language $language) => TextInputColumn::make('lang_'.$language->code)
                    ->label($language->native_name)
                    ->getStateUsing(fn (LanguageLine $record) => $record->text[$language->code] ?? null)
                    ->updateStateUsing(function (LanguageLine $record, ?string $state) use ($language) {
                        $text = $record->text;
                        if (blank($state)) {
                            unset($text[$language->code]);
                        } else {
                            $text[$language->code] = $state;
                        }
                        $record->update(['text' => $text]);

                        return $state;
                    })
                    ->placeholder(__('Missing'))
                    ->extraInputAttributes(['dir' => $language->direction])
                    ->disabled(fn () => ! (auth()->user()?->can('update translations') ?? false)))->all(),
            ])
            ->searchable()
            ->searchUsing(fn (Builder $query, string $search) => $query->where(fn (Builder $q) => $q
                ->where('key', 'like', "%{$search}%")
                ->orWhere('text', 'like', '%'.addcslashes($search, '%_').'%')))
            ->filters([
                SelectFilter::make('group')->options(fn () => LanguageLine::query()->distinct()->orderBy('group')->pluck('group', 'group')->all()),
                SelectFilter::make('missing')
                    ->label('Missing in')
                    ->options($languages->pluck('native_name', 'code')->all())
                    ->query(fn (Builder $query, array $data) => filled($data['value'])
                        ? $query->where(fn (Builder $q) => $q->whereNull("text->{$data['value']}")->orWhere("text->{$data['value']}", ''))
                        : $query),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageLanguageLines::route('/'),
        ];
    }
}

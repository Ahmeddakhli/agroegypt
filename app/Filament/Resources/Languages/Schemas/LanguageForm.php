<?php

namespace App\Filament\Resources\Languages\Schemas;

use App\Models\Language;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class LanguageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Language'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('code')
                            ->label('Locale code')
                            ->helperText(fn (?Model $record) => __($record?->isInUse()
                                ? 'Locked: content, settings or interface strings already use this code.'
                                : 'Used in URLs, e.g. "fr" → /fr/about-us. Format: en, fr, pt-BR, zh-Hans.'))
                            ->disabled(fn (?Model $record) => (bool) $record?->isInUse())
                            ->required()
                            ->maxLength(12)
                            ->regex('/^'.Language::CODE_PATTERN.'$/')
                            ->unique(ignoreRecord: true),
                        TextInput::make('name')->label('English name')->required()->maxLength(100),
                        TextInput::make('native_name')->label('Native name')->helperText(__('Shown in the language switcher.'))->required()->maxLength(100),
                        ToggleButtons::make('direction')
                            ->label('Text direction')
                            ->options(['ltr' => __('Left to right'), 'rtl' => __('Right to left')])
                            ->default('ltr')
                            ->inline()
                            ->required(),
                        Select::make('fallback_code')
                            ->label('Fallback language')
                            ->helperText(__('Shown when content is missing in this language. Empty = the default language.'))
                            ->options(fn (?Model $record) => Language::cached()->where('code', '!=', $record?->code)->pluck('name', 'code')->all())
                            ->placeholder(__('Default language')),
                        TextInput::make('sort')->numeric()->default(0)->minValue(0),
                        FileUpload::make('flag_path')
                            ->label('Flag icon')
                            ->image()
                            ->disk('public')
                            ->directory('flags')
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                            ->maxSize(512),
                        Toggle::make('is_active')->label('Enabled on the website')->default(true),
                        Toggle::make('is_default')->label('Default language')->helperText(__('Only one language can be the default; it is always enabled.')),
                    ]),
            ]);
    }
}

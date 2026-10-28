<?php

namespace App\Filament\Pages;

use App\Models\Language;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ManageSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Site settings';

    protected static ?string $title = 'Site settings';

    protected static ?int $navigationSort = 1;

    protected function permission(): string
    {
        return 'update settings';
    }

    protected function globalKeys(): array
    {
        return [
            'contact.phone', 'contact.fax', 'contact.email',
            'social.facebook', 'social.instagram', 'social.linkedin', 'social.youtube', 'social.whatsapp',
            'seo.indexing', 'seo.robots_disallow', 'seo.og_image',
            'mail.notifications_enabled', 'mail.notify_contact', 'mail.notify_careers',
            'analytics.ga_measurement_id', 'cookies.enabled',
            'general.timezone', 'general.maintenance',
        ];
    }

    protected function localizedKeys(): array
    {
        return ['company.name', 'contact.address', 'seo.title_format', 'seo.description', 'cookies.message'];
    }

    protected function formComponents(): array
    {
        return [
            Tabs::make('settings')->persistTabInQueryString()->columnSpanFull()->tabs([
                Tab::make('Company & contact')->icon('heroicon-o-building-office')->schema([
                    $this->localizedTabs(fn (Language $language, bool $isDefault) => [
                        TextInput::make('company.name')->label('Company name')->required($isDefault)->maxLength(200),
                        Textarea::make('contact.address')->label('Address')->rows(2)->maxLength(500),
                    ], 'Per language'),
                    Section::make()->columns(3)->schema([
                        TextInput::make('contact.phone')->label('Phone')->tel()->telRegex('/^[0-9+()\-.\s\/]{5,50}$/')->maxLength(50),
                        TextInput::make('contact.fax')->label('Fax')->regex('/^[0-9+()\-.\s\/]{5,50}$/')->maxLength(50),
                        TextInput::make('contact.email')->label('Email')->email()->maxLength(190),
                    ]),
                ]),
                Tab::make('Social media')->icon('heroicon-o-share')->columns(2)->schema([
                    TextInput::make('social.facebook')->url()->prefixIcon('heroicon-o-link')->maxLength(300),
                    TextInput::make('social.instagram')->url()->prefixIcon('heroicon-o-link')->maxLength(300),
                    TextInput::make('social.linkedin')->label('LinkedIn')->url()->prefixIcon('heroicon-o-link')->maxLength(300),
                    TextInput::make('social.youtube')->label('YouTube')->url()->prefixIcon('heroicon-o-link')->maxLength(300),
                    TextInput::make('social.whatsapp')->label('WhatsApp number')->helperText(__('International format, e.g. +201001234567. Empty hides the chat button.'))->regex('/^\+?[0-9 ]{6,20}$/'),
                ]),
                Tab::make('SEO')->icon('heroicon-o-magnifying-glass')->schema([
                    $this->localizedTabs(fn (Language $language) => [
                        TextInput::make('seo.title_format')->label('Page title format')->helperText(__('%s is replaced by the page name.'))->maxLength(200),
                        Textarea::make('seo.description')->label('Default meta description')->rows(2)->maxLength(300),
                    ], 'Per language'),
                    Section::make()->schema([
                        Toggle::make('seo.indexing')->label('Allow search engines to index the site')->default(true),
                        Textarea::make('seo.robots_disallow')->label('Extra paths to disallow in robots.txt')->helperText(__('One path per line, e.g. /en/careers'))->rows(3),
                        FileUpload::make('seo.og_image')->label('Default social sharing image')->image()->disk('public')->directory('settings')->maxSize(4096),
                    ]),
                ]),
                Tab::make('Email notifications')->icon('heroicon-o-envelope')->schema([
                    Toggle::make('mail.notifications_enabled')->label('Send email notifications for new submissions')->default(true)
                        ->helperText(__('Mail server credentials live in the server .env file (MAIL_*) and are never stored in the database.')),
                    TextInput::make('mail.notify_contact')->label('Contact form recipients')->helperText(__('Comma-separated email addresses.'))->maxLength(500),
                    TextInput::make('mail.notify_careers')->label('Job application recipients')->helperText(__('Comma-separated email addresses.'))->maxLength(500),
                ]),
                Tab::make('Analytics & cookies')->icon('heroicon-o-chart-bar')->schema([
                    TextInput::make('analytics.ga_measurement_id')->label('Google Analytics 4 measurement ID')->placeholder('G-XXXXXXXXXX')->regex('/^G-[A-Z0-9]{4,12}$/'),
                    Toggle::make('cookies.enabled')->label('Show cookie consent banner (analytics loads only after consent)'),
                    $this->localizedTabs(fn () => [Textarea::make('cookies.message')->label('Banner text')->rows(2)->maxLength(300)], 'Banner text'),
                ]),
                Tab::make('System')->icon('heroicon-o-wrench-screwdriver')->schema([
                    Select::make('general.timezone')->options(fn () => array_combine(timezone_identifiers_list(), timezone_identifiers_list()))->searchable(),
                    Toggle::make('general.maintenance')
                        ->label('Maintenance mode')
                        ->helperText(__('Visitors see a maintenance page; signed-in administrators can still browse the site.')),
                ]),
            ]),
        ];
    }
}

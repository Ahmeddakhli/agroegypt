<?php

namespace App\Filament\Pages;

use App\Filament\Support\Translations;
use App\Models\Language;
use App\Support\MediaLibrary;
use App\View\Composers\SiteComposer;
use BackedEnum;
use Closure;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\View;
use Filament\Support\Icons\Heroicon;
use Illuminate\Http\UploadedFile;
use UnitEnum;

class ManageAppearance extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaintBrush;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Appearance, header & footer';

    protected static ?string $title = 'Appearance';

    protected static ?int $navigationSort = 2;

    protected function permission(): string
    {
        return 'update appearance';
    }

    protected function globalKeys(): array
    {
        return [
            ...array_keys(SiteComposer::COLOR_VARIABLES),
            'theme.font_latin', 'theme.font_arabic', 'theme.radius', 'theme.section_spacing', 'theme.shadow',
            'theme.animations', 'theme.hover_effects',
            'theme.logo', 'theme.footer_logo', 'theme.favicon', 'theme.default_hero', 'theme.divider_icon',
            'header.show_language_switcher', 'header.sticky', 'header.button_url',
            'footer.icon_address', 'footer.icon_phone', 'footer.icon_fax', 'footer.icon_email',
        ];
    }

    protected function localizedKeys(): array
    {
        return ['header.button_label', 'footer.tagline', 'footer.links_title', 'footer.contact_title', 'footer.social_title', 'footer.copyright', 'footer.credit'];
    }

    protected function formComponents(): array
    {
        $image = fn (string $key, string $label) => FileUpload::make($key)->label($label)->image()->disk('public')->directory('theme')->maxSize(4096)
            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'])
            ->rule(fn () => function (string $attribute, mixed $file, Closure $fail) {
                if ($file instanceof UploadedFile && ! MediaLibrary::isSafeSvg((string) file_get_contents($file->getRealPath()))) {
                    $fail(__('SVG files containing scripts, event handlers or external content are not allowed.'));
                }
            });

        return [
            Tabs::make('appearance')->persistTabInQueryString()->columnSpanFull()->tabs([
                Tab::make('Colours')->icon('heroicon-o-swatch')->columns(3)->schema([
                    ColorPicker::make('theme.color_dark')->live()->label('Dark green (header text, footer, sections)')->placeholder('#182719')->regex('/^#[0-9a-fA-F]{3,8}$/'),
                    ColorPicker::make('theme.color_dark_2')->live()->label('Secondary dark (cards, features)')->placeholder('#2d3c2e')->regex('/^#[0-9a-fA-F]{3,8}$/'),
                    ColorPicker::make('theme.color_primary')->live()->label('Primary (buttons, breadcrumbs)')->placeholder('#789915')->regex('/^#[0-9a-fA-F]{3,8}$/'),
                    ColorPicker::make('theme.color_primary_hover')->live()->label('Primary hover')->placeholder('#63800f')->regex('/^#[0-9a-fA-F]{3,8}$/'),
                    ColorPicker::make('theme.color_accent')->live()->label('Accent orange (product names, icons)')->placeholder('#eaa84e')->regex('/^#[0-9a-fA-F]{3,8}$/'),
                    ColorPicker::make('theme.color_band')->live()->label('Certificates band')->placeholder('#858533')->regex('/^#[0-9a-fA-F]{3,8}$/'),
                    ColorPicker::make('theme.color_text')->live()->label('Body text')->placeholder('#182719')->regex('/^#[0-9a-fA-F]{3,8}$/'),
                    ColorPicker::make('theme.color_body_bg')->live()->label('Page background')->placeholder('#ffffff')->regex('/^#[0-9a-fA-F]{3,8}$/'),
                ]),
                Tab::make('Typography & layout')->icon('heroicon-o-adjustments-horizontal')->columns(2)->schema([
                    Select::make('theme.font_latin')->live()->label('Latin font')->options(SiteComposer::FONTS['latin'])->placeholder(__('Heebo (original)')),
                    Select::make('theme.font_arabic')->live()->label('Arabic font')->options(SiteComposer::FONTS['arabic'])->placeholder(__('Cairo')),
                    TextInput::make('theme.radius')->live(onBlur: true)->label('Card corner radius (px)')->numeric()->minValue(0)->maxValue(40)->placeholder('6'),
                    TextInput::make('theme.section_spacing')->live(onBlur: true)->label('Section spacing factor')->numeric()->step(0.05)->minValue(0.25)->maxValue(3)->placeholder('1'),
                    Select::make('theme.shadow')->live()->label('Card shadow')->options(['none' => __('None'), 'soft' => __('Soft'), 'medium' => __('Medium (original)'), 'strong' => __('Strong')])->placeholder(__('Medium (original)')),
                    Toggle::make('theme.animations')->live()->label('Enable animations (fade-ins, slider transitions, hover effects)')->default(true)
                        ->helperText(__('Visitors who ask their device for reduced motion never see animations.')),
                    Toggle::make('theme.hover_effects')->live()->label('Hover effects on cards and icons ("hang" motion)')->default(true),
                ]),
                Tab::make('Logos & images')->icon('heroicon-o-photo')->columns(3)->schema([
                    $image('theme.logo', 'Header logo'),
                    $image('theme.footer_logo', 'Footer logo (light)'),
                    $image('theme.favicon', 'Favicon'),
                    $image('theme.default_hero', 'Default page header image'),
                    $image('theme.divider_icon', 'Divider icon'),
                ]),
                Tab::make('Header')->icon('heroicon-o-bars-3')->schema([
                    Toggle::make('header.show_language_switcher')->label('Show the language switcher')->default(true),
                    Toggle::make('header.sticky')->label('Keep the header visible while scrolling (sticky)'),
                    Section::make(__('Header button'))
                        ->description(__('Optional call-to-action shown after the menu (hidden when label or link is empty).'))
                        ->schema([
                            TextInput::make('header.button_url')->label('Link')->placeholder('/contact-us or https://…')->maxLength(300)->regex(Translations::LINK_PATTERN),
                            $this->localizedTabs(fn () => [TextInput::make('header.button_label')->label('Label')->maxLength(40)], 'Button label'),
                        ]),
                    Section::make()->description(__('The navigation itself is managed under Menus.')),
                ]),
                Tab::make('Footer')->icon('heroicon-o-rectangle-group')->schema([
                    $this->localizedTabs(fn (Language $language) => [
                        TextInput::make('footer.tagline')->label('Tagline under the logo')->maxLength(300),
                        TextInput::make('footer.links_title')->label('Links column title')->maxLength(100),
                        TextInput::make('footer.contact_title')->label('Contact column title')->maxLength(100),
                        TextInput::make('footer.social_title')->label('Social column title')->maxLength(100),
                        TextInput::make('footer.copyright')->label('Copyright line')->maxLength(200),
                        TextInput::make('footer.credit')->label('Credit line (right side)')->maxLength(200),
                    ], 'Footer texts'),
                    Section::make(__('Contact icons'))->columns(4)->schema([
                        $image('footer.icon_address', 'Address'),
                        $image('footer.icon_phone', 'Phone'),
                        $image('footer.icon_fax', 'Fax'),
                        $image('footer.icon_email', 'Email'),
                    ]),
                ]),
            ]),
        ];
    }

    public string $previewLanguage = '';

    /**
     * Signed link showing the site with the colours/typography/layout currently in the form (not yet saved).
     */
    public function previewUrl(): string
    {
        $values = [];
        foreach (SiteComposer::PREVIEWABLE as $key) {
            $value = data_get($this->data, $key);
            if ($value !== null && $value !== '') {
                $values[$key] = $value;
            }
        }

        $language = Language::active()->firstWhere('code', $this->previewLanguage) ? $this->previewLanguage : Language::defaultCode();

        return SiteComposer::previewUrl($language, $values);
    }

    protected function extraContent(): array
    {
        return [
            Section::make(__('Live preview'))
                ->description(__('Updates as you change colours, fonts and layout — before saving. Logos and texts appear after saving.'))
                ->collapsible()
                ->schema([View::make('admin.site-preview')]),
        ];
    }
}

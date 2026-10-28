<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageAppearance;
use App\Filament\Pages\ManageSettings;
use App\Models\Page;
use App\Models\Setting;
use App\View\Composers\SiteComposer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\AdminTestCase;

class SettingsAdminTest extends AdminTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Page::factory()->template(Page::TEMPLATE_HOME)->create(['en' => ['title' => 'Home', 'slug' => 'home']]);
    }

    public function test_site_settings_are_saved_and_shown_on_the_website(): void
    {
        Livewire::test(ManageSettings::class)
            ->fillForm([
                'localized' => [
                    'en' => ['company' => ['name' => 'Agro Egypt'], 'contact' => ['address' => "Plot 2-3\n6th of October"]],
                    'ar' => ['company' => ['name' => 'أجرو إيجيبت'], 'contact' => ['address' => 'السادس من أكتوبر']],
                ],
                'contact' => ['phone' => '(+202) 38642048', 'fax' => '(+202) 38642049', 'email' => 'sales@example.com'],
                'social' => ['facebook' => 'https://facebook.com/agro', 'whatsapp' => '+201001234567'],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('أجرو إيجيبت', Setting::get('company.name', locale: 'ar'));
        $this->assertSame('(+202) 38642048', Setting::get('contact.phone'));

        $this->get('/en')->assertOk()
            ->assertSee('Plot 2-3<br />', false)
            ->assertSee('tel:+20238642048', false)
            ->assertSee('mailto:sales@example.com', false)
            ->assertSee('https://facebook.com/agro')
            ->assertSee('https://wa.me/201001234567');
        $this->get('/ar')->assertSee('السادس من أكتوبر');
    }

    public function test_clearing_a_localized_setting_falls_back_to_the_default_language(): void
    {
        Setting::put('company.name', 'Agro Egypt');
        Setting::put('company.name', 'أجرو', 'ar');

        Livewire::test(ManageSettings::class)
            ->assertFormSet(['localized.ar.company.name' => 'أجرو'])
            ->fillForm(['localized' => ['ar' => ['company' => ['name' => null]]]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(0, Setting::where('key', 'name')->where('locale', 'ar')->count());
        $this->assertSame('Agro Egypt', Setting::get('company.name', locale: 'ar', fallbackLocale: 'en'));
    }

    public function test_settings_are_validated(): void
    {
        Livewire::test(ManageSettings::class)
            ->fillForm([
                'contact' => ['email' => 'not-an-email'],
                'social' => ['facebook' => 'javascript:alert(1)', 'whatsapp' => 'call me'],
                'analytics' => ['ga_measurement_id' => '<script>'],
            ])
            ->call('save')
            ->assertHasFormErrors(['contact.email', 'social.facebook', 'social.whatsapp', 'analytics.ga_measurement_id']);
    }

    public function test_maintenance_mode_hides_the_site_from_visitors_but_not_from_staff(): void
    {
        Setting::put('company.name', 'Agro Egypt');

        Livewire::test(ManageSettings::class)
            ->fillForm(['general' => ['maintenance' => true]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get('/en')->assertOk();

        auth()->logout();
        $this->get('/en')->assertStatus(503)->assertHeader('Retry-After');
        $this->get('/')->assertStatus(503);
    }

    public function test_analytics_only_loads_with_a_valid_id_and_consent_when_required(): void
    {
        auth()->logout();
        Setting::put('analytics.ga_measurement_id', 'G-ABC123XYZ');

        $this->get('/en')->assertSee('googletagmanager.com/gtag/js?id=G-ABC123XYZ', false);

        Setting::put('cookies.enabled', '1');
        $this->get('/en')->assertDontSee('googletagmanager', false)->assertSee('cookie_consent=accepted', false);

        $this->withUnencryptedCookie('cookie_consent', 'accepted')->get('/en')->assertSee('googletagmanager', false);
    }

    public function test_appearance_colours_typography_and_logos_are_applied(): void
    {
        Livewire::test(ManageAppearance::class)
            ->fillForm([
                'theme' => [
                    'color_primary' => '#1d4ed8',
                    'radius' => 12,
                    'section_spacing' => 1.5,
                    'font_latin' => 'system-ui',
                    'animations' => false,
                    'logo' => UploadedFile::fake()->image('logo.png', 380, 140),
                ],
                'header' => ['show_language_switcher' => false],
                'localized' => ['en' => ['footer' => ['tagline' => 'Fresh from Egypt']]],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $logo = Setting::get('theme.logo');
        Storage::disk('public')->assertExists($logo);

        $this->get('/en')->assertOk()
            ->assertSee('--c-primary:#1d4ed8', false)
            ->assertSee('--radius-card:12px', false)
            ->assertSee('--section-space:1.5', false)
            ->assertSee('--font-latin:system-ui', false)
            ->assertSee('no-animations', false)
            ->assertSee('storage/'.$logo, false)
            ->assertSee('Fresh from Egypt')
            ->assertDontSee('aria-label="Change language"', false);
    }

    public function test_header_button_sticky_header_shadow_background_and_hover_options(): void
    {
        Livewire::test(ManageAppearance::class)
            ->fillForm([
                'header' => ['sticky' => true, 'button_url' => '/contact-us'],
                'localized' => ['en' => ['header' => ['button_label' => 'Request a quote']], 'ar' => ['header' => ['button_label' => 'اطلب عرض سعر']]],
                'theme' => ['shadow' => 'strong', 'color_body_bg' => '#fafaf5', 'hover_effects' => false],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get('/en')->assertOk()
            ->assertSee('Request a quote')
            ->assertSee(url('/en/contact-us'))
            ->assertSee('sticky top-0', false)
            ->assertSee('--shadow-card:0 12px 60px rgba(0,0,0,.25)', false)
            ->assertSee('--c-body-bg:#fafaf5', false)
            ->assertSee('no-hover-effects', false);
        $this->get('/ar')->assertSee('اطلب عرض سعر')->assertSee(url('/ar/contact-us'));
    }

    public function test_untouched_toggles_keep_their_defaults_when_saving(): void
    {
        Livewire::test(ManageAppearance::class)
            ->assertFormSet(['theme.hover_effects' => true, 'theme.animations' => true, 'header.show_language_switcher' => true])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('1', Setting::get('theme.hover_effects'));
        $this->get('/en')->assertDontSee('no-hover-effects', false)->assertDontSee('no-animations', false);
    }

    public function test_footer_hides_empty_columns(): void
    {
        $this->get('/en')->assertDontSee('Social Media')->assertDontSee('Contact Info');

        Setting::put('social.youtube', 'https://youtube.com/@agro');
        Setting::put('contact.email', 'info@example.com');

        $this->get('/en')->assertSee('Social Media')->assertSee('Contact Info');
    }

    public function test_live_preview_shows_unsaved_appearance_values_only_on_signed_urls(): void
    {
        Setting::put('theme.color_primary', '#789915');

        $livewire = Livewire::test(ManageAppearance::class)
            ->fillForm(['theme' => ['color_primary' => '#ff0000', 'radius' => 20, 'animations' => false]]);
        $url = $livewire->instance()->previewUrl();

        $this->assertNotSame('#ff0000', Setting::get('theme.color_primary'));
        auth()->logout();

        $this->get($url)->assertOk()
            ->assertSee('--c-primary:#ff0000', false)
            ->assertSee('--radius-card:20px', false)
            ->assertSee('no-animations', false);

        $tampered = preg_replace('/theme_preview=[^&]+/', 'theme_preview='.base64_encode(json_encode(['theme.color_primary' => '#000000'])), $url);
        $this->get($tampered)->assertOk()->assertSee('--c-primary:#789915', false)->assertDontSee('#000000', false);

        $unsafe = SiteComposer::previewUrl('en', ['theme.color_primary' => 'red;}body{display:none', 'theme.logo' => 'x.png']);
        $this->get($unsafe)->assertOk()->assertDontSee('display:none', false)->assertSee('--c-primary:#789915', false);

        $this->get('/en')->assertSee('--c-primary:#789915', false);
    }

    public function test_invalid_theme_values_are_rejected(): void
    {
        Livewire::test(ManageAppearance::class)
            ->fillForm(['theme' => ['color_primary' => 'red;}body{display:none', 'radius' => 400]])
            ->call('save')
            ->assertHasFormErrors(['theme.color_primary', 'theme.radius']);
    }

    public function test_logo_svgs_with_scripts_are_rejected(): void
    {
        $svg = fn (string $body) => UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg">'.$body.'</svg>');

        Livewire::test(ManageAppearance::class)
            ->fillForm(['theme' => ['logo' => $svg('<script>alert(1)</script>')]])
            ->call('save')
            ->assertHasFormErrors(['theme.logo']);

        Livewire::test(ManageAppearance::class)
            ->fillForm(['theme' => ['logo' => $svg('<rect width="10" height="10"/>')]])
            ->call('save')
            ->assertHasNoFormErrors();
    }

    public function test_only_permitted_roles_open_settings_screens(): void
    {
        $this->signIn('editor');
        $this->get(ManageSettings::getUrl())->assertForbidden();
        $this->get(ManageAppearance::getUrl())->assertForbidden();

        $this->signIn('admin');
        $this->get(ManageSettings::getUrl())->assertOk();
        $this->get(ManageAppearance::getUrl())->assertOk();
    }
}

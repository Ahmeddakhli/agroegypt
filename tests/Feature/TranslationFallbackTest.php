<?php

namespace Tests\Feature;

use App\Models\Language;
use App\Models\Page;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationFallbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Language::factory()->english()->create();
        Language::factory()->arabic()->create();
    }

    public function test_translated_attributes_resolve_for_the_current_locale(): void
    {
        $product = Product::factory()->create(['ar' => ['name' => 'برتقال', 'slug' => 'برتقال']]);

        app()->setLocale('ar');
        $this->assertSame('برتقال', $product->fresh()->name);

        app()->setLocale('en');
        $this->assertNotSame('برتقال', $product->fresh()->name);
    }

    public function test_missing_translation_falls_back_to_the_configured_fallback_language(): void
    {
        Language::factory()->french()->create();
        $product = Product::factory()->create(['en' => ['name' => 'Navels', 'slug' => 'navels']]);

        app()->setLocale('fr');

        $this->assertSame('Navels', $product->fresh()->name);
    }

    public function test_new_language_is_usable_immediately_without_code_changes(): void
    {
        $this->assertNotContains('de', config('translatable.locales'));

        Language::factory()->create(['code' => 'de', 'name' => 'German', 'native_name' => 'Deutsch']);

        $this->assertContains('de', config('translatable.locales'));
        $product = Product::factory()->create(['de' => ['name' => 'Orangen', 'slug' => 'orangen']]);
        $this->assertSame('Orangen', $product->fresh()->translate('de')->name);
    }

    public function test_only_one_language_can_be_default(): void
    {
        $french = Language::factory()->french()->create(['is_default' => true]);

        $this->assertSame('fr', Language::defaultCode());
        $this->assertSame(1, Language::where('is_default', true)->count());
        $this->assertTrue($french->fresh()->is_active);
    }

    public function test_english_settings_imported_as_neutral_survive_arabic_becoming_the_default(): void
    {
        Page::factory()->template(Page::TEMPLATE_HOME)->create([
            'en' => ['title' => 'Home', 'slug' => 'home'],
            'ar' => ['title' => 'الرئيسية', 'slug' => 'الرئيسية'],
        ]);
        Setting::put('footer.copyright', '© AgroEgypt');
        Setting::put('footer.copyright', '© أجرو إيجيبت', 'ar');
        Setting::put('footer.social_title', 'Follow us', 'en');
        Setting::put('footer.social_title', 'Old neutral text');
        Setting::put('contact.phone', '(+202) 38642048');
        Language::where('code', 'ar')->firstOrFail()->update(['is_default' => true]);

        (require database_path('migrations/2026_10_07_212615_move_neutral_localized_settings_to_english.php'))->up();
        Setting::flush();

        $this->assertSame('© AgroEgypt', Setting::get('footer.copyright', locale: 'en', fallbackLocale: 'ar'));
        $this->assertSame('Follow us', Setting::get('footer.social_title', locale: 'en', fallbackLocale: 'ar'));
        $this->assertSame('(+202) 38642048', Setting::get('contact.phone'));
        $this->get('/en')->assertOk()->assertSee('© AgroEgypt')->assertDontSee('أجرو إيجيبت');
        $this->get('/ar')->assertOk()->assertSee('© أجرو إيجيبت');
    }
}

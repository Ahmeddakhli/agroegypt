<?php

namespace Tests\Feature;

use App\Filament\Pages\ContentTranslations;
use App\Filament\Resources\LanguageLines\Pages\ManageLanguageLines;
use App\Filament\Resources\Languages\Pages\CreateLanguage;
use App\Filament\Resources\Languages\Pages\EditLanguage;
use App\Filament\Resources\Languages\Pages\ListLanguages;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Models\Language;
use App\Models\LanguageLine;
use App\Models\Page;
use App\Models\Product;
use App\Support\TranslationCoverage;
use Filament\Actions\Testing\TestAction;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\AdminTestCase;

class LocalizationAdminTest extends AdminTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Page::factory()->template(Page::TEMPLATE_HOME)->create(['en' => ['title' => 'Home', 'slug' => 'home']]);
    }

    public function test_a_language_added_in_the_admin_is_live_everywhere(): void
    {
        Livewire::test(CreateLanguage::class)
            ->fillForm(['code' => 'fr', 'name' => 'French', 'native_name' => 'Français', 'direction' => 'ltr', 'fallback_code' => 'en', 'is_active' => true, 'sort' => 3])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->get('/fr')->assertOk()->assertSee('<html lang="fr" dir="ltr"', false);
        $this->get('/en')->assertSee('Français')->assertSee(url('/fr'));

        Livewire::test(CreateProduct::class)->assertFormFieldExists('lang-fr.fr.name');
    }

    public function test_a_right_to_left_language_can_be_added_and_disabled(): void
    {
        Livewire::test(CreateLanguage::class)
            ->fillForm(['code' => 'ur', 'name' => 'Urdu', 'native_name' => 'اردو', 'direction' => 'rtl', 'is_active' => true])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->get('/ur')->assertOk()->assertSee('dir="rtl"', false);

        $urdu = Language::firstWhere('code', 'ur');
        Livewire::test(EditLanguage::class, ['record' => $urdu->getRouteKey()])
            ->fillForm(['is_active' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get('/ur')->assertNotFound();
        $this->get('/en')->assertDontSee('اردو');
    }

    public function test_language_codes_are_validated(): void
    {
        Livewire::test(CreateLanguage::class)
            ->fillForm(['code' => 'EN!', 'name' => 'X', 'native_name' => 'X', 'direction' => 'ltr'])
            ->call('create')
            ->assertHasFormErrors(['code']);

        Livewire::test(CreateLanguage::class)
            ->fillForm(['code' => 'ar', 'name' => 'Arabic again', 'native_name' => 'X', 'direction' => 'rtl'])
            ->call('create')
            ->assertHasFormErrors(['code']);
    }

    public function test_switching_the_default_language(): void
    {
        $arabic = Language::firstWhere('code', 'ar');
        $arabic->update(['is_active' => false]);

        Livewire::test(EditLanguage::class, ['record' => $arabic->getRouteKey()])
            ->fillForm(['is_default' => true])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('ar', Language::defaultCode());
        $this->assertTrue($arabic->fresh()->is_active);
        $this->assertFalse(Language::firstWhere('code', 'en')->is_default);
        $this->get('/', ['Accept-Language' => 'de-DE,de;q=0.9'])->assertRedirect(url('/ar'));
        $this->get('/', ['Accept-Language' => 'en-US,en;q=0.9'])->assertRedirect(url('/en'));
    }

    public function test_languages_with_content_or_the_default_cannot_be_deleted(): void
    {
        $english = Language::firstWhere('code', 'en');
        $arabic = Language::firstWhere('code', 'ar');
        $german = Language::factory()->create(['code' => 'de', 'name' => 'German', 'native_name' => 'Deutsch']);
        Product::factory()->create(['ar' => ['name' => 'منتج', 'slug' => 'muntaj']]);

        Livewire::test(ListLanguages::class)
            ->assertActionDisabled(TestAction::make('delete')->table($english))
            ->assertActionDisabled(TestAction::make('delete')->table($arabic))
            ->callAction(TestAction::make('delete')->table($german));

        $this->assertModelMissing($german);
        $this->assertModelExists($arabic);
    }

    public function test_language_codes_are_locked_once_in_use(): void
    {
        $arabic = Language::firstWhere('code', 'ar');
        $german = Language::factory()->create(['code' => 'de', 'name' => 'German', 'native_name' => 'Deutsch']);
        Product::factory()->create(['ar' => ['name' => 'منتج', 'slug' => 'muntaj']]);

        Livewire::test(EditLanguage::class, ['record' => $arabic->getRouteKey()])
            ->assertFormFieldDisabled('code');

        Livewire::test(EditLanguage::class, ['record' => $german->getRouteKey()])
            ->assertFormFieldEnabled('code')
            ->fillForm(['code' => 'de-AT'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('de-AT', $german->fresh()->code);
        $this->get('/de-AT')->assertOk();
    }

    public function test_deleting_a_language_cleans_up_its_strings_and_fallback_references(): void
    {
        $german = Language::factory()->create(['code' => 'de', 'name' => 'German', 'native_name' => 'Deutsch']);
        $swiss = Language::factory()->create(['code' => 'rm', 'name' => 'Romansh', 'native_name' => 'Rumantsch', 'fallback_code' => 'de']);
        $line = LanguageLine::create(['group' => 'site', 'key' => 'promo', 'text' => ['en' => 'Offer', 'de' => 'Angebot']]);

        $this->assertTrue($german->isInUse());
        $this->assertTrue($german->canBeDeleted());

        Livewire::test(ListLanguages::class)->callAction(TestAction::make('delete')->table($german));

        $this->assertSame(['en' => 'Offer'], $line->fresh()->text);
        $this->assertNull($swiss->fresh()->fallback_code);
    }

    public function test_interface_strings_are_edited_inline_and_used_by_the_site(): void
    {
        LanguageLine::syncFromFiles();
        $line = LanguageLine::where('group', 'site')->where('key', 'back_to_top')->firstOrFail();

        Livewire::test(ManageLanguageLines::class)
            ->call('updateTableColumnState', 'lang_ar', (string) $line->getKey(), 'إلى الأعلى')
            ->call('updateTableColumnState', 'lang_en', (string) $line->getKey(), 'Scroll up');

        $this->assertSame('إلى الأعلى', $line->fresh()->text['ar']);
        $this->get('/ar')->assertSee('aria-label="إلى الأعلى"', false);
        $this->get('/en')->assertSee('aria-label="Scroll up"', false);
    }

    public function test_missing_filter_lists_untranslated_strings(): void
    {
        $missing = LanguageLine::create(['group' => 'site', 'key' => 'promo', 'text' => ['en' => 'Offer']]);
        $complete = LanguageLine::create(['group' => 'site', 'key' => 'promo2', 'text' => ['en' => 'Deal', 'ar' => 'عرض']]);

        Livewire::test(ManageLanguageLines::class)
            ->filterTable('missing', 'ar')
            ->assertCanSeeTableRecords([$missing])
            ->assertCanNotSeeTableRecords([$complete]);
    }

    public function test_content_translation_overview_lists_missing_and_partial_records(): void
    {
        $translated = Product::factory()->create(['en' => ['name' => 'Navels', 'slug' => 'navel', 'description' => 'Sweet'], 'ar' => ['name' => 'أبو سرة', 'slug' => 'navel', 'description' => 'حلو']]);
        $partial = Product::factory()->create(['en' => ['name' => 'Lemons', 'slug' => 'lemon', 'description' => 'Sour'], 'ar' => ['name' => 'ليمون', 'slug' => 'lemon']]);
        $missing = Product::factory()->create(['en' => ['name' => 'Mango', 'slug' => 'mango', 'description' => 'Juicy']]);

        $rows = collect(TranslationCoverage::rows('ar', 'products'))->keyBy('id');
        $this->assertSame('complete', $rows[$translated->id]['status']);
        $this->assertSame('partial', $rows[$partial->id]['status']);
        $this->assertSame('missing', $rows[$missing->id]['status']);
        $this->assertStringEndsWith("/admin/products/{$missing->id}/edit", $rows[$missing->id]['edit_url']);

        Livewire::test(ContentTranslations::class)
            ->assertSet('locale', 'ar')
            ->assertSee('Mango')
            ->assertSee('Lemons')
            ->assertDontSee('Navels')
            ->set('status', 'complete')
            ->assertSee('Navels')
            ->set('module', 'pages')
            ->assertDontSee('Navels');

        $this->signIn('translator');
        $this->get(ContentTranslations::getUrl())->assertOk();
        $this->signIn('inbox');
        $this->get(ContentTranslations::getUrl())->assertForbidden();
    }

    public function test_translations_are_exported_and_imported(): void
    {
        LanguageLine::create(['group' => 'site', 'key' => 'promo', 'text' => ['en' => 'Offer', 'ar' => 'عرض']]);

        Livewire::test(ManageLanguageLines::class)
            ->callAction('exportCsv')
            ->assertFileDownloaded('translations-'.now()->format('Y-m-d').'.csv');

        $csv = "\xEF\xBB\xBFgroup,key,en,ar\nsite,promo,Special offer,عرض خاص\nsite,new_key,Hello,<b>مرحبا</b>\n\"bad key!\",x,y,z\n";
        Livewire::test(ManageLanguageLines::class)
            ->callAction('import', ['file' => UploadedFile::fake()->createWithContent('t.csv', $csv), 'overwrite' => true]);

        $this->assertSame(['en' => 'Special offer', 'ar' => 'عرض خاص'], LanguageLine::firstWhere('key', 'promo')->text);
        $this->assertSame('مرحبا', LanguageLine::firstWhere('key', 'new_key')->text['ar']);
        $this->assertNull(LanguageLine::firstWhere('key', 'x'));

        $json = json_encode([['group' => 'site', 'key' => 'promo', 'text' => ['ar' => 'لن يتغير']]]);
        Livewire::test(ManageLanguageLines::class)
            ->callAction('import', ['file' => UploadedFile::fake()->createWithContent('t.json', $json), 'overwrite' => false]);

        $this->assertSame('عرض خاص', LanguageLine::firstWhere('key', 'promo')->text['ar']);
    }

    public function test_translators_edit_strings_but_editors_cannot(): void
    {
        LanguageLine::create(['group' => 'site', 'key' => 'promo', 'text' => ['en' => 'Offer']]);

        $this->signIn('translator');
        $this->get(route('filament.admin.resources.language-lines.index'))->assertOk();

        $this->signIn('editor');
        $this->get(route('filament.admin.resources.language-lines.index'))->assertForbidden();
    }

    public function test_the_dashboard_can_switch_to_arabic(): void
    {
        // Filament caches the built navigation for the app instance that tests share, so only the first render is checked.
        $this->from('/admin')->get(route('filament.admin.locale', 'ar'))->assertRedirect('/admin');
        $this->get('/admin')->assertSee('dir="rtl"', false)->assertSee('إعدادات الموقع')->assertDontSee('Site settings');
        $this->assertSame('ar', session('admin_locale'));

        $this->get('/admin/locale/fr')->assertNotFound();
    }
}

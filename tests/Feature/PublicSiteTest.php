<?php

namespace Tests\Feature;

use App\Enums\SectionType;
use App\Mail\NewSubmissionMail;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\JobApplication;
use App\Models\Language;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Product;
use App\Models\Redirect;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    private Product $navel;

    protected function setUp(): void
    {
        parent::setUp();

        Language::factory()->english()->create();
        Language::factory()->arabic()->create();

        Page::factory()->template(Page::TEMPLATE_HOME)->create([
            'en' => ['title' => 'Home', 'slug' => 'home'],
            'ar' => ['title' => 'الرئيسية', 'slug' => 'home'],
        ]);
        Page::factory()->template(Page::TEMPLATE_PRODUCTS)->create([
            'en' => ['title' => 'Our Products', 'slug' => 'our-products'],
            'ar' => ['title' => 'منتجاتنا', 'slug' => 'our-products'],
        ]);
        $contact = Page::factory()->create(['en' => ['title' => 'Contact us', 'slug' => 'contact-us']]);
        $contact->sections()->create(['type' => SectionType::ContactForm, 'sort' => 0, 'en' => ['title' => 'Get in touch']]);
        $careers = Page::factory()->create(['en' => ['title' => 'Careers', 'slug' => 'careers']]);
        $careers->sections()->create(['type' => SectionType::CareersForm, 'sort' => 0, 'en' => ['title' => 'Careers']]);

        $citrus = Category::factory()->create(['en' => ['name' => 'Citrus', 'slug' => 'citrus'], 'ar' => ['name' => 'الموالح', 'slug' => 'citrus']]);
        $oranges = Category::factory()->create(['parent_id' => $citrus->id, 'en' => ['name' => 'Oranges', 'slug' => 'orange']]);
        $this->navel = Product::factory()->create([
            'category_id' => $oranges->id,
            'en' => ['name' => 'Navels', 'slug' => 'navel', 'description' => 'Seedless and easy to peel.', 'spec_label' => 'Counts'],
            'ar' => ['name' => 'أبو سرة', 'slug' => 'navel', 'description' => 'خالٍ من البذور.', 'spec_label' => 'الأحجام'],
        ]);
        $this->navel->seasons()->create(['months' => [12, 1], 'sort' => 0, 'en' => ['label' => 'Washington Navels']]);
        $packaging = $this->navel->packagings()->create(['sort' => 0, 'en' => ['title' => 'Open Top Carton (Net Weight 15 Kgs)']]);
        $packaging->specs()->create(['value' => '65', 'sort' => 0, 'en' => ['label' => 'No. of Cartons/Pallet']]);
    }

    public function test_root_redirects_to_the_visitor_language_or_the_default(): void
    {
        $this->get('/')->assertRedirect(url('/en'));
        $this->get('/', ['Accept-Language' => 'ar,en;q=0.8'])->assertRedirect(url('/ar'));
    }

    public function test_pages_render_with_language_and_direction(): void
    {
        $this->get('/en')->assertOk()->assertSee('<html lang="en" dir="ltr"', false);
        $this->get('/ar')->assertOk()->assertSee('<html lang="ar" dir="rtl"', false);
    }

    public function test_unknown_and_inactive_languages_are_not_routable(): void
    {
        $this->get('/de')->assertNotFound();

        Language::factory()->french()->inactive()->create();
        $this->get('/fr')->assertNotFound();
    }

    public function test_product_detail_resolves_through_the_category_path(): void
    {
        $this->get('/en/our-products/citrus/orange/navel')
            ->assertOk()
            ->assertSee('Navels')
            ->assertSee('Seedless and easy to peel.')
            ->assertSee('Open Top Carton (Net Weight 15 Kgs)')
            ->assertSee('Washington Navels')
            ->assertSeeInOrder(['Home', 'Our Products', 'Citrus', 'Oranges', 'Navels']);

        $this->get('/ar/our-products/citrus/orange/navel')->assertOk()->assertSee('أبو سرة')->assertSee('الأحجام');
    }

    public function test_category_lists_its_children(): void
    {
        $this->get('/en/our-products/citrus')->assertOk()->assertSee('Oranges');
        $this->get('/en/our-products/citrus/orange')->assertOk()->assertSee('Navels');
    }

    public function test_draft_products_are_hidden(): void
    {
        $this->navel->update(['status' => 'draft']);

        $this->get('/en/our-products/citrus/orange/navel')->assertNotFound();
    }

    public function test_missing_translation_falls_back_but_is_not_advertised_in_hreflang(): void
    {
        $response = $this->get('/ar/our-products/citrus/orange')->assertOk();

        $response->assertSee('Oranges'); // English fallback content
        $response->assertSee('<link rel="alternate" hreflang="en"', false);
        $response->assertDontSee('<link rel="alternate" hreflang="ar"', false);

        $this->get('/en/our-products/citrus/orange/navel')
            ->assertSee('<link rel="alternate" hreflang="ar" href="'.url('/ar/our-products/citrus/orange/navel').'">', false)
            ->assertSee('<link rel="canonical" href="'.url('/en/our-products/citrus/orange/navel').'">', false);
    }

    public function test_a_language_added_in_the_database_works_without_code_changes(): void
    {
        Language::factory()->french()->create();
        $this->navel->update(['fr' => ['name' => 'Oranges Navel', 'slug' => 'navel']]);

        $this->get('/fr/our-products/citrus/orange/navel')->assertOk()->assertSee('Oranges Navel')->assertSee('<html lang="fr" dir="ltr"', false);
        $this->get('/en')->assertSee('Français');
    }

    public function test_rtl_languages_can_be_added(): void
    {
        Language::factory()->create(['code' => 'he', 'name' => 'Hebrew', 'native_name' => 'עברית', 'direction' => 'rtl']);

        $this->get('/he')->assertOk()->assertSee('dir="rtl"', false);
    }

    public function test_legacy_urls_redirect_permanently(): void
    {
        Redirect::create(['from_path' => '/our-products/citrus/mandarine-2/', 'to_path' => '/en/our-products/citrus/orange/navel']);

        $this->get('/our-products/citrus/mandarine-2/')->assertStatus(301)->assertRedirect(url('/en/our-products/citrus/orange/navel'));
        $this->assertSame(1, Redirect::first()->hits);
    }

    public function test_menus_link_to_published_records_only(): void
    {
        $menu = Menu::create(['name' => 'Header', 'location' => 'header']);
        MenuItem::create(['menu_id' => $menu->id, 'linkable_type' => $this->navel->getMorphClass(), 'linkable_id' => $this->navel->id, 'sort' => 0, 'en' => ['label' => 'Navel oranges']]);

        $this->get('/en')->assertSee('Navel oranges')->assertSee(url('/en/our-products/citrus/orange/navel'));

        $this->navel->update(['status' => 'draft']);
        $this->get('/en')->assertDontSee('Navel oranges');
    }

    public function test_sitemap_and_robots(): void
    {
        $this->get('/sitemap.xml')->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(url('/en/our-products/citrus/orange/navel'))
            ->assertSee('hreflang="ar"', false);

        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap: '.url('sitemap.xml'));

        Setting::put('seo.indexing', '0');
        $this->get('/robots.txt')->assertSee('Disallow: /');
    }

    public function test_contact_form_stores_the_message_and_notifies(): void
    {
        Mail::fake();
        Setting::put('mail.notify_contact', 'sales@example.com, not-an-email');

        $this->from('/en/contact-us')->post('/en/forms/contact', ['name' => 'Jane', 'email' => 'jane@example.com', 'message' => 'Price list please'])
            ->assertRedirect('/en/contact-us#contact-form')
            ->assertSessionHas('form_success', 'contact');

        $this->assertDatabaseHas('contact_messages', ['email' => 'jane@example.com', 'locale' => 'en', 'status' => 'new']);
        Mail::assertQueued(NewSubmissionMail::class, fn ($mail) => $mail->hasTo('sales@example.com') && ! $mail->hasTo('not-an-email'));
    }

    public function test_contact_form_validates_and_rejects_bots(): void
    {
        $this->post('/en/forms/contact', ['name' => '', 'email' => 'nope'])->assertSessionHasErrors(['name', 'email']);
        $this->post('/en/forms/contact', ['name' => 'Bot', 'email' => 'bot@example.com', 'website' => 'http://spam'])->assertSessionHasErrors('website');

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_validation_messages_are_localized(): void
    {
        $this->post('/ar/forms/contact', ['name' => '', 'email' => 'x@example.com'])
            ->assertSessionHasErrors(['name' => 'حقل الاسم مطلوب.']);
    }

    public function test_contact_form_is_rate_limited(): void
    {
        foreach (range(1, 5) as $i) {
            $this->post('/en/forms/contact', ['name' => "N{$i}", 'email' => "n{$i}@example.com"]);
        }

        $this->post('/en/forms/contact', ['name' => 'N6', 'email' => 'n6@example.com'])->assertStatus(429);
        $this->assertSame(5, ContactMessage::count());
    }

    public function test_career_applications_store_the_cv_privately(): void
    {
        Storage::fake('local');
        Mail::fake();

        $this->post('/en/forms/careers', [
            'name' => 'Omar', 'email' => 'omar@example.com', 'phone' => '+20 100 000 0000', 'job_title' => 'Quality engineer',
            'cv' => UploadedFile::fake()->create('cv.pdf', 200, 'application/pdf'),
        ])->assertSessionHas('form_success', 'careers');

        $application = JobApplication::firstOrFail();
        Storage::disk('local')->assertExists($application->cv_path);
        $this->assertStringStartsWith('applications/', $application->cv_path);
    }

    public function test_career_applications_reject_unsafe_files(): void
    {
        Storage::fake('local');

        $this->post('/en/forms/careers', [
            'name' => 'Omar', 'email' => 'omar@example.com', 'phone' => '0100000000', 'job_title' => 'X',
            'cv' => UploadedFile::fake()->create('shell.php', 10, 'application/x-php'),
        ])->assertSessionHasErrors('cv');

        $this->assertSame(0, JobApplication::count());
    }

    public function test_theme_settings_are_validated_before_reaching_css(): void
    {
        Setting::put('theme.color_primary', '#123456');
        Setting::put('theme.color_accent', 'red;}</style><script>alert(1)</script>');

        $this->get('/en')->assertSee('--c-primary:#123456', false)->assertDontSee('alert(1)', false);
    }
}

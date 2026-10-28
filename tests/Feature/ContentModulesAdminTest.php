<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\SectionType;
use App\Filament\Pages\ManageMenus;
use App\Filament\Resources\Documents\Pages\CreateDocument;
use App\Filament\Resources\Galleries\Pages\CreateGallery;
use App\Filament\Resources\Galleries\Pages\ListGalleries;
use App\Filament\Resources\Media\Pages\ListMedia;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Sliders\Pages\CreateSlider;
use App\Filament\Resources\Sliders\Pages\EditSlider;
use App\Models\Document;
use App\Models\Gallery;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Slider;
use App\Support\MediaLibrary;
use Filament\Actions\Testing\TestAction;
use Filament\Forms\Components\Repeater;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\AdminTestCase;

class ContentModulesAdminTest extends AdminTestCase
{
    private Page $home;

    protected function setUp(): void
    {
        parent::setUp();

        $this->home = Page::factory()->template(Page::TEMPLATE_HOME)->create(['en' => ['title' => 'Home', 'slug' => 'home']]);
    }

    public function test_slider_and_slides_are_managed_and_shown_on_the_home_page(): void
    {
        $this->home->sections()->create(['type' => SectionType::HeroSlider, 'sort' => 0, 'is_visible' => true, 'settings' => ['slider_key' => 'home']]);
        $undoRepeaterFake = Repeater::fake();

        Livewire::test(CreateSlider::class)
            ->fillForm([
                'name' => 'Home hero', 'key' => 'home', 'effect' => 'fade', 'autoplay' => true,
                'interval_ms' => 6000, 'transition_ms' => 800, 'show_arrows' => true,
                'height_desktop' => 700, 'height_tablet' => 500, 'height_mobile' => 400,
                'slides' => [
                    ['media_id' => $this->image('one.jpg')->id, 'is_active' => true, 'url' => '/contact-us', 'en' => ['title' => 'QUALITY', 'button_label' => 'Contact'], 'ar' => ['title' => 'الجودة']],
                    ['media_id' => $this->image('two.jpg')->id, 'is_active' => false, 'en' => ['title' => 'HIDDEN SLIDE']],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $undoRepeaterFake();

        $this->get('/en')->assertOk()
            ->assertSee('QUALITY')
            ->assertDontSee('HIDDEN SLIDE')
            ->assertSee('interval: 6000', false)
            ->assertSee('--h-d: 700px', false)
            ->assertSee(url('/en/contact-us'));
        $this->get('/ar')->assertSee('الجودة');

        $slider = Slider::firstOrFail();
        Livewire::test(EditSlider::class, ['record' => $slider->getRouteKey()])
            ->fillForm(['key' => 'Bad Key!'])
            ->call('save')
            ->assertHasFormErrors(['key']);
    }

    public function test_certificates_are_managed_as_documents_and_shown_on_the_site(): void
    {
        $this->home->sections()->create(['type' => SectionType::Certificates, 'sort' => 0, 'is_visible' => true]);

        Livewire::test(CreateDocument::class)
            ->fillForm([
                'type' => 'certificate',
                'image_media_id' => $this->image('brc.png', 400, 260)->id,
                'is_active' => true,
                'en' => ['title' => 'BRC Global Standards'],
                'ar' => ['title' => 'شهادة BRC'],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->get('/en')->assertOk()->assertSee('BRC Global Standards');
        $this->get('/ar')->assertOk()->assertSee('شهادة BRC');
    }

    public function test_download_buttons_follow_their_document_when_the_file_is_replaced(): void
    {
        $file = MediaLibrary::store(UploadedFile::fake()->createWithContent('catalogue.pdf', "%PDF-1.4\n%%EOF"));
        $catalogue = Document::factory()->create(['type' => 'catalogue', 'file_media_id' => $file->id]);
        $this->home->sections()->create([
            'type' => SectionType::CtaBackground, 'sort' => 0, 'is_visible' => true,
            'settings' => ['document_id' => $catalogue->id],
            'en' => ['title' => 'Download our Catalogue', 'button_label' => 'Download'],
        ]);

        $this->get('/en')->assertSee('href="'.$file->url().'"  download', false);

        Livewire::test(ListMedia::class)
            ->callAction(TestAction::make('replace')->table($file), [
                'file' => UploadedFile::fake()->createWithContent('catalogue-2026.pdf', "%PDF-1.7\nnew\n%%EOF"),
            ]);

        $file->refresh();
        $this->assertStringContainsString('catalogue-2026', $file->path);
        $this->get('/en')->assertSee('href="'.$file->url().'"', false);
    }

    public function test_gallery_albums_are_created_and_extended_by_bulk_upload(): void
    {
        Page::factory()->template(Page::TEMPLATE_GALLERY)->create(['en' => ['title' => 'Gallery', 'slug' => 'gallery']])
            ->sections()->create(['type' => SectionType::GalleryGrid, 'sort' => 0, 'is_visible' => true]);
        $first = $this->image('a.jpg');
        $undoRepeaterFake = Repeater::fake();

        Livewire::test(CreateGallery::class)
            ->fillForm([
                'en' => ['title' => 'Packing Station', 'slug' => 'pack-house'],
                'is_active' => true,
                'cover_media_id' => $first->id,
                'imageItems' => [['media_id' => $first->id]],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $undoRepeaterFake();
        $gallery = Gallery::firstOrFail();

        Livewire::test(ListGalleries::class)
            ->callAction(TestAction::make('bulkUpload')->table($gallery), [
                'files' => [UploadedFile::fake()->image('b.jpg', 800, 600), UploadedFile::fake()->image('c.jpg', 640, 480)],
            ]);

        $this->assertSame(3, $gallery->imageItems()->count());
        $this->get('/en/gallery')->assertOk()->assertSee('Packing Station');
        $this->get('/en/gallery/pack-house')->assertOk()->assertSee($first->url(), false);
    }

    public function test_menu_items_are_created_edited_reordered_and_deleted(): void
    {
        $about = Page::factory()->create(['en' => ['title' => 'About us', 'slug' => 'about-us']]);
        $menu = Menu::create(['name' => 'Header', 'location' => 'header']);
        $existing = MenuItem::create(['menu_id' => $menu->id, 'sort' => 0, 'url' => 'https://example.org', 'target' => '_blank', 'en' => ['label' => 'Partner']]);

        Livewire::test(ManageMenus::class)
            ->callAction(TestAction::make('create')->table(), [
                'en' => ['label' => 'Who we are'],
                'link_type' => 'page',
                'linkable_id' => $about->id,
                'target' => '_self',
                'is_visible' => true,
            ])
            ->assertHasNoFormErrors();

        $item = MenuItem::whereTranslation('label', 'Who we are')->firstOrFail();
        $this->assertTrue($item->linkable->is($about));
        $this->get('/en')->assertSee('Who we are')->assertSee(url('/en/about-us'))->assertSee('https://example.org');
        $this->assertFalse($item->hasTranslation('ar'));

        Livewire::test(ManageMenus::class)
            ->callAction(TestAction::make('up')->table($item));
        $this->assertSame([$item->id, $existing->id], MenuItem::orderBy('sort')->pluck('id')->all());

        Livewire::test(ManageMenus::class)
            ->callAction(TestAction::make('edit')->table($item), [
                'en' => ['label' => 'About'],
                'ar' => ['label' => 'من نحن'],
                'link_type' => 'page',
                'linkable_id' => $about->id,
                'parent_id' => $existing->id,
                'target' => '_self',
                'is_visible' => true,
            ])
            ->assertHasNoFormErrors();

        $this->assertSame($existing->id, $item->fresh()->parent_id);
        $this->get('/ar')->assertSee('من نحن');

        Livewire::test(ManageMenus::class)
            ->callAction(TestAction::make('delete')->table($existing));

        $this->assertSame(0, MenuItem::count());
        $this->get('/en')->assertDontSee('Partner');
    }

    public function test_custom_menu_urls_must_be_safe(): void
    {
        Menu::create(['name' => 'Header', 'location' => 'header']);

        Livewire::test(ManageMenus::class)
            ->callAction(TestAction::make('create')->table(), [
                'en' => ['label' => 'Bad'],
                'link_type' => 'url',
                'url' => 'javascript:alert(1)',
                'target' => '_self',
            ])
            ->assertHasFormErrors(['url']);

        $this->assertSame(0, MenuItem::count());
    }

    public function test_brands_and_markets_content_is_managed_through_page_sections(): void
    {
        $brands = Page::factory()->create(['en' => ['title' => 'Our Brands', 'slug' => 'brands']]);
        $grid = $brands->sections()->create(['type' => SectionType::ImageCardGrid, 'sort' => 0, 'is_visible' => true, 'settings' => ['columns' => 3]]);
        $brand = $grid->items()->create(['sort' => 0, 'media_id' => $this->image('box.png', 400, 400)->id, 'en' => ['title' => 'Four Brothers']]);
        $markets = $brands->sections()->create(['type' => SectionType::MarketRegions, 'sort' => 1, 'is_visible' => true]);
        $region = $markets->items()->create(['sort' => 0, 'en' => ['title' => 'Gulf Region', 'body' => "Oman\nQatar"]]);

        Livewire::test(EditPage::class, ['record' => $brands->getRouteKey()])
            ->fillForm([
                "sections.record-{$grid->id}.items.record-{$brand->id}.en.title" => 'Five Brothers',
                "sections.record-{$markets->id}.items.record-{$region->id}.en.body" => "Oman\nQatar\nKuwait",
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get('/en/brands')->assertOk()->assertSee('Five Brothers')->assertSee('Kuwait')->assertDontSee('Four Brothers');
    }

    public function test_unpublished_pages_disappear_from_menus(): void
    {
        $about = Page::factory()->create(['en' => ['title' => 'About us', 'slug' => 'about-us']]);
        $menu = Menu::create(['name' => 'Header', 'location' => 'header']);
        MenuItem::create(['menu_id' => $menu->id, 'sort' => 0, 'linkable_type' => $about->getMorphClass(), 'linkable_id' => $about->id, 'en' => ['label' => 'About']]);

        $this->get('/en')->assertSee(url('/en/about-us'));

        $about->update(['status' => ContentStatus::Draft]);

        $this->get('/en')->assertDontSee(url('/en/about-us'));
    }
}

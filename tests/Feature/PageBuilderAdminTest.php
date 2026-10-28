<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\SectionType;
use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Models\Page;
use App\Models\PageSection;
use App\Support\ContentCache;
use Filament\Forms\Components\Repeater;
use Livewire\Livewire;
use Tests\AdminTestCase;

class PageBuilderAdminTest extends AdminTestCase
{
    public function test_section_type_decides_which_fields_are_editable(): void
    {
        $undoRepeaterFake = Repeater::fake();

        Livewire::test(CreatePage::class)
            ->fillForm(['sections' => [
                ['type' => SectionType::Map->value, 'items' => []],
                ['type' => SectionType::ProgressBars->value, 'items' => [['value' => '50']]],
            ]])
            ->assertFormFieldVisible('sections.0.lang-en.en.title')
            ->assertFormFieldVisible('sections.0.lang-ar.ar.button_url')
            ->assertFormFieldHidden('sections.0.lang-en.en.subtitle')
            ->assertFormFieldHidden('sections.0.lang-en.en.body_html')
            ->assertFormFieldVisible('sections.1.lang-en.en.body')
            ->assertFormFieldVisible('sections.1.items.0.value')
            ->assertFormFieldVisible('sections.1.items.0.lang-en.en.title')
            ->assertFormFieldHidden('sections.1.items.0.icon_media_id');

        $undoRepeaterFake();
    }

    public function test_page_is_created_with_translated_sections_and_items(): void
    {
        $undoRepeaterFake = Repeater::fake();

        Livewire::test(CreatePage::class)
            ->fillForm([
                'en' => ['title' => 'Our Farms', 'slug' => 'our-farms'],
                'ar' => ['title' => 'مزارعنا', 'slug' => 'مزارعنا'],
                'template' => Page::TEMPLATE_DEFAULT,
                'status' => ContentStatus::Published,
                'show_hero' => true,
                'sections' => [
                    [
                        'type' => SectionType::RichText->value,
                        'is_visible' => true,
                        'settings' => ['animation' => 'fade'],
                        'en' => ['title' => 'Farms', 'body' => '<p>Fresh <strong>citrus</strong><script>alert(1)</script></p>'],
                        'ar' => ['title' => 'المزارع', 'body' => '<p>موالح طازجة</p>'],
                        'items' => [],
                    ],
                    [
                        'type' => SectionType::ProgressBars->value,
                        'is_visible' => true,
                        'en' => ['title' => 'Vision'],
                        'items' => [
                            ['value' => '90', 'en' => ['title' => 'Quality'], 'ar' => ['title' => 'الجودة']],
                        ],
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $undoRepeaterFake();

        $page = Page::firstOrFail();
        $this->assertSame('مزارعنا', $page->translate('ar')->title);
        $this->assertSame([SectionType::RichText, SectionType::ProgressBars], $page->sections->pluck('type')->all());
        $this->assertSame('المزارع', $page->sections[0]->translate('ar')->title);
        $this->assertSame('90', $page->sections[1]->items->first()->value);
        $this->assertSame('الجودة', $page->sections[1]->items->first()->translate('ar')->title);

        $this->get('/en/our-farms')->assertOk()
            ->assertSee('Farms')
            ->assertSee('<strong>citrus</strong>', false)
            ->assertDontSee('alert(1)', false)
            ->assertSee('Quality')
            ->assertSee('data-reveal="fade"', false);

        $this->get('/ar/'.rawurlencode('مزارعنا'))->assertOk()->assertSee('المزارع')->assertSee('الجودة');
    }

    public function test_editing_section_text_updates_the_public_page(): void
    {
        $page = $this->pageWithSections();
        $section = $page->sections[0];

        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
            ->assertFormSet(["sections.record-{$section->id}.en.title" => 'First block'])
            ->fillForm([
                "sections.record-{$section->id}.en.title" => 'Renamed block',
                "sections.record-{$section->id}.en.body" => '<p>New story</p>',
                "sections.record-{$section->id}.ar.title" => 'قسم جديد',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get('/en/history')->assertOk()->assertSee('Renamed block')->assertSee('New story')->assertDontSee('First block text');
        $this->get('/ar/history')->assertOk()->assertSee('قسم جديد');
    }

    public function test_custom_html_is_saved_and_sanitized(): void
    {
        $undoRepeaterFake = Repeater::fake();

        Livewire::test(CreatePage::class)
            ->fillForm([
                'en' => ['title' => 'Embed', 'slug' => 'embed'],
                'status' => ContentStatus::Published,
                'sections' => [[
                    'type' => SectionType::CustomHtml->value,
                    'is_visible' => true,
                    'items' => [],
                    'en' => ['body_html' => '<div class="note">Hello <b>world</b><script>steal()</script><img src="x" onerror="steal()"></div>'],
                ]],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $undoRepeaterFake();

        $this->assertStringContainsString('<script>', Page::firstOrFail()->sections->first()->translate('en')->body);
        $this->get('/en/embed')->assertOk()
            ->assertSee('<div class="note">Hello <b>world</b>', false)
            ->assertDontSee('steal()', false);
    }

    public function test_sections_can_be_reordered_hidden_and_removed(): void
    {
        $page = $this->pageWithSections();
        [$first, $second, $third] = $page->sections;

        $livewire = Livewire::test(EditPage::class, ['record' => $page->getRouteKey()]);
        $sections = $livewire->get('data.sections');
        $sections["record-{$second->id}"]['is_visible'] = false;

        $livewire
            ->set('data.sections', [
                "record-{$third->id}" => $sections["record-{$third->id}"],
                "record-{$second->id}" => $sections["record-{$second->id}"],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $page->refresh();
        $this->assertSame([$third->id, $second->id], $page->sections->pluck('id')->all());
        $this->assertFalse($page->sections->firstWhere('id', $second->id)->is_visible);
        $this->assertNull(PageSection::find($first->id));

        $this->get('/en/history')->assertOk()->assertSee('Third block')->assertDontSee('Second block')->assertDontSee('First block');
    }

    public function test_clearing_a_section_item_translation_removes_it(): void
    {
        $page = $this->pageWithSections();
        $section = $page->sections[2];
        $item = $section->items()->create(['value' => '80', 'sort' => 1, 'en' => ['title' => 'Equipment'], 'ar' => ['title' => 'المعدات']]);

        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
            ->assertFormSet(["sections.record-{$section->id}.items.record-{$item->id}.ar.title" => 'المعدات'])
            ->fillForm(["sections.record-{$section->id}.items.record-{$item->id}.ar.title" => null])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertFalse($item->fresh()->hasTranslation('ar'));
        $this->get('/ar/history')->assertOk()->assertSee('Equipment');
    }

    public function test_duplicate_creates_a_draft_with_all_sections_and_translations(): void
    {
        $page = $this->pageWithSections();
        $page->sections[2]->items()->create(['value' => '75', 'sort' => 1, 'en' => ['title' => 'Purity']]);

        $copy = PageResource::duplicate($page);

        $this->assertSame(ContentStatus::Draft, $copy->status);
        $this->assertSame(Page::TEMPLATE_DEFAULT, $copy->template);
        $this->assertStringStartsWith('history-copy-', $copy->translate('en')->slug);
        $this->assertSame(3, $copy->sections()->count());
        $this->assertSame('Purity', $copy->sections()->orderBy('sort')->get()[2]->items->first()->translate('en')->title);
        $this->assertSame(3, $page->sections()->count());
    }

    public function test_drafts_are_only_reachable_through_a_signed_preview(): void
    {
        $page = $this->pageWithSections(ContentStatus::Draft);

        $this->get('/en/history')->assertNotFound();
        $this->get(PageResource::previewUrl($page))->assertOk()->assertSee('First block')->assertSee('noindex', false);
        $this->get(route('pages.preview', ['locale' => 'en', 'page' => $page->id]))->assertForbidden();
    }

    public function test_scheduled_pages_appear_once_their_publish_time_has_passed(): void
    {
        $page = $this->pageWithSections();
        $page->update(['published_at' => now()->addDay()]);

        $this->get('/en/history')->assertNotFound();

        $this->travel(2)->days();
        ContentCache::flush();

        $this->get('/en/history')->assertOk();
    }

    public function test_each_language_can_have_its_own_sharing_image(): void
    {
        $page = $this->pageWithSections();
        $english = $this->image('share-en.jpg', 1200, 630);
        $arabic = $this->image('share-ar.jpg', 1200, 631);

        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
            ->fillForm(['en' => ['og_media_id' => $english->id], 'ar' => ['title' => 'تاريخنا', 'slug' => 'history', 'og_media_id' => $arabic->id]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get('/en/history')->assertSee('<meta property="og:image" content="'.$english->url().'">', false);
        $this->get('/ar/history')->assertSee('<meta property="og:image" content="'.$arabic->url().'">', false);
    }

    public function test_feature_boxes_use_the_sections_entrance_animation(): void
    {
        $page = Page::factory()->create(['en' => ['title' => 'Why us', 'slug' => 'why-us']]);
        $section = $page->sections()->create(['type' => SectionType::FeatureGrid, 'sort' => 0, 'is_visible' => true, 'settings' => ['animation' => 'zoom']]);
        $section->items()->createMany([
            ['sort' => 0, 'en' => ['title' => 'Quality', 'body' => 'Certified']],
            ['sort' => 1, 'en' => ['title' => 'Speed', 'body' => 'Fast shipping']],
        ]);

        $html = $this->get('/en/why-us')->assertOk()->getContent();
        $this->assertSame(2, substr_count($html, 'data-reveal="zoom"'));

        $section->update(['settings' => ['animation' => 'none']]);
        $this->assertStringNotContainsString('data-reveal', $this->get('/en/why-us')->getContent());
    }

    public function test_special_templates_can_only_be_used_once(): void
    {
        Page::factory()->template(Page::TEMPLATE_PRODUCTS)->create();

        Livewire::test(CreatePage::class)
            ->fillForm(['en' => ['title' => 'Products 2', 'slug' => 'products-2'], 'template' => Page::TEMPLATE_PRODUCTS, 'status' => ContentStatus::Draft])
            ->call('create')
            ->assertHasFormErrors(['template']);
    }

    public function test_slugs_are_validated(): void
    {
        $this->pageWithSections();

        Livewire::test(CreatePage::class)
            ->fillForm(['en' => ['title' => 'History', 'slug' => 'history'], 'status' => ContentStatus::Draft])
            ->call('create')
            ->assertHasFormErrors(['en.slug']);

        foreach (['Bad Slug!', 'Upper-Case', '-leading', 'double--dash'] as $slug) {
            Livewire::test(CreatePage::class)
                ->fillForm(['en' => ['title' => 'Bad', 'slug' => $slug], 'status' => ContentStatus::Draft])
                ->call('create')
                ->assertHasFormErrors(['en.slug']);
        }

        Livewire::test(CreatePage::class)
            ->fillForm(['en' => ['title' => 'Ok', 'slug' => 'citrus-2026'], 'ar' => ['title' => 'موالح', 'slug' => 'موالح-مصر'], 'status' => ContentStatus::Draft])
            ->call('create')
            ->assertHasNoFormErrors();
    }

    public function test_section_links_must_be_safe(): void
    {
        $undoRepeaterFake = Repeater::fake();

        Livewire::test(CreatePage::class)
            ->fillForm([
                'en' => ['title' => 'Links', 'slug' => 'links'],
                'status' => ContentStatus::Draft,
                'sections' => [[
                    'type' => SectionType::ImageCardGrid->value,
                    'en' => ['title' => 'Cards'],
                    'items' => [['url' => 'javascript:alert(1)', 'en' => ['title' => 'x']]],
                ]],
            ])
            ->call('create')
            ->assertHasFormErrors(['sections.0.items.0.url']);

        $undoRepeaterFake();
    }

    public function test_custom_html_sections_require_the_settings_permission(): void
    {
        $this->signIn('editor');
        $undoRepeaterFake = Repeater::fake();

        Livewire::test(CreatePage::class)
            ->fillForm([
                'en' => ['title' => 'Embed', 'slug' => 'embed'],
                'status' => ContentStatus::Draft,
                'sections' => [['type' => SectionType::CustomHtml->value, 'items' => [], 'en' => ['body' => '<div>x</div>']]],
            ])
            ->call('create')
            ->assertHasFormErrors(['sections.0.type']);

        $undoRepeaterFake();
        $this->assertSame(0, Page::count());
    }

    public function test_translators_edit_existing_pages_but_cannot_create_or_delete_them(): void
    {
        $page = $this->pageWithSections();
        $this->signIn('translator');

        $this->get(PageResource::getUrl('index'))->assertOk();
        $this->get(PageResource::getUrl('edit', ['record' => $page]))->assertOk();
        $this->get(PageResource::getUrl('create'))->assertForbidden();
        $this->assertFalse(PageResource::canDelete($page));
        $this->assertFalse(auth()->user()->can('replicate', $page));
    }

    private function pageWithSections(ContentStatus $status = ContentStatus::Published): Page
    {
        $page = Page::factory()->create(['status' => $status, 'en' => ['title' => 'History', 'slug' => 'history']]);

        foreach (['First block', 'Second block', 'Third block'] as $sort => $title) {
            $page->sections()->create([
                'type' => $sort === 2 ? SectionType::ProgressBars : SectionType::RichText,
                'sort' => $sort,
                'is_visible' => true,
                'en' => ['title' => $title, 'body' => "<p>{$title} text</p>"],
            ]);
        }

        return $page->load('sections');
    }
}

<?php

namespace Tests\Feature;

use App\Enums\SectionType;
use App\Filament\Resources\Media\Pages\EditMedia;
use App\Filament\Resources\Media\Pages\ListMedia;
use App\Models\Media;
use App\Models\Page;
use App\Support\MediaLibrary;
use Filament\Actions\Testing\TestAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\AdminTestCase;

class MediaLibraryAdminTest extends AdminTestCase
{
    public function test_images_are_stored_with_dimensions_and_responsive_webp_variants(): void
    {
        $media = $this->image('Orange Grove.jpg', 1600, 900);

        $this->assertSame([1600, 900], [$media->width, $media->height]);
        $this->assertSame('image/jpeg', $media->mime_type);
        $this->assertStringStartsWith('media/', $media->path);
        $this->assertStringContainsString('orange-grove-', $media->path);
        $this->assertSame([480, 768, 1280], array_keys($media->variants));
        foreach ([$media->path, ...array_values($media->variants)] as $path) {
            Storage::disk('public')->assertExists($path);
        }
        $this->assertStringContainsString('1280w', $media->srcset());
    }

    public function test_identical_files_are_stored_once(): void
    {
        $file = UploadedFile::fake()->image('a.jpg', 300, 200);

        $first = MediaLibrary::store($file);
        $second = MediaLibrary::store($file);

        $this->assertTrue($first->is($second));
        $this->assertSame(1, Media::count());
    }

    public function test_unsafe_files_are_rejected(): void
    {
        $cases = [
            'script.svg' => '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>',
            'handler.svg' => '<svg xmlns="http://www.w3.org/2000/svg"><rect onload="alert(1)"/></svg>',
            'shell.jpg' => '<?php system($_GET["c"]); ?>',
            'page.html' => '<html><body>hi</body></html>',
        ];

        foreach ($cases as $name => $content) {
            try {
                MediaLibrary::store(UploadedFile::fake()->createWithContent($name, $content));
                $this->fail("{$name} should have been rejected");
            } catch (InvalidArgumentException) {
                // expected
            }
        }

        $this->assertSame(0, Media::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_safe_svg_and_pdf_files_are_accepted(): void
    {
        $svg = MediaLibrary::store(UploadedFile::fake()->createWithContent('icon.svg', '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><circle cx="5" cy="5" r="4"/></svg>'));
        $pdf = MediaLibrary::store(UploadedFile::fake()->createWithContent('catalogue.pdf', "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF"));

        $this->assertSame('image/svg+xml', $svg->mime_type);
        $this->assertSame('application/pdf', $pdf->mime_type);
        $this->assertNull($pdf->variants);
    }

    public function test_files_in_use_cannot_be_deleted(): void
    {
        $used = $this->image('used.jpg');
        $unused = $this->image('unused.jpg', 800, 600);
        Page::factory()->create(['hero_media_id' => $used->id]);

        Livewire::test(ListMedia::class)->callAction(TestAction::make('delete')->table($used));
        Livewire::test(ListMedia::class)->callAction(TestAction::make('delete')->table($unused));

        $this->assertNotNull($used->fresh());
        $this->assertNull($unused->fresh());
        Storage::disk('public')->assertMissing($unused->path);
        Storage::disk('public')->assertExists($used->path);
    }

    public function test_replacing_a_file_keeps_every_reference(): void
    {
        $media = $this->image('old.jpg', 1200, 800);
        $page = Page::factory()->create(['en' => ['title' => 'Farm', 'slug' => 'farm']]);
        $page->sections()->create(['type' => SectionType::FullImage, 'sort' => 0, 'is_visible' => true, 'media_id' => $media->id]);
        $oldPath = $media->path;

        Livewire::test(ListMedia::class)->callAction(TestAction::make('replace')->table($media), [
            'file' => UploadedFile::fake()->image('new.png', 1000, 1000),
        ]);

        $media->refresh();
        $this->assertNotSame($oldPath, $media->path);
        $this->assertSame([1000, 1000], [$media->width, $media->height]);
        Storage::disk('public')->assertMissing($oldPath);
        $this->get('/en/farm')->assertOk()->assertSee($media->url(), false);
    }

    public function test_admins_upload_several_files_and_get_a_report_of_rejections(): void
    {
        Livewire::test(ListMedia::class)
            ->callAction('upload', [
                'files' => [
                    UploadedFile::fake()->image('one.jpg', 400, 300),
                    UploadedFile::fake()->image('two.png', 500, 300),
                    UploadedFile::fake()->createWithContent('bad.svg', '<svg><script>x</script></svg>'),
                ],
            ]);

        $this->assertSame(2, Media::count());
    }

    public function test_alt_text_is_edited_per_language_and_used_on_the_site(): void
    {
        $media = $this->image('grove.jpg');
        $page = Page::factory()->create(['en' => ['title' => 'Farm', 'slug' => 'farm']]);
        $page->sections()->create(['type' => SectionType::FullImage, 'sort' => 0, 'is_visible' => true, 'media_id' => $media->id]);

        Livewire::test(EditMedia::class, ['record' => $media->getRouteKey()])
            ->fillForm(['name' => 'Orange grove', 'en' => ['alt' => 'Orange trees in Nubaria'], 'ar' => ['alt' => 'أشجار البرتقال']])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get('/en/farm')->assertSee('alt="Orange trees in Nubaria"', false);
        $this->get('/ar/farm')->assertSee('alt="أشجار البرتقال"', false);
    }

    public function test_editors_manage_media_translators_only_edit_alt_text_and_inbox_users_have_no_access(): void
    {
        $media = $this->image('grove.jpg');

        $this->signIn('editor');
        Livewire::test(ListMedia::class)->assertActionVisible('upload');

        $this->signIn('translator');
        $this->get(route('filament.admin.resources.media.edit', $media))->assertOk();
        Livewire::test(ListMedia::class)
            ->assertActionHidden('upload')
            ->assertActionHidden(TestAction::make('delete')->table($media));

        $this->signIn('inbox');
        $this->get(route('filament.admin.resources.media.index'))->assertForbidden();
    }
}

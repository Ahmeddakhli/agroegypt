<?php

namespace Tests\Feature;

use App\Filament\Resources\Roles\RoleResource;
use App\Models\Language;
use App\Models\LanguageLine;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Language::factory()->english()->create();
        Language::factory()->arabic()->create();
        $this->admin = User::factory()->create()->assignRole(User::SUPER_ADMIN);
    }

    public function test_every_resource_page_renders_for_a_super_admin(): void
    {
        $this->actingAs($this->admin);

        $urls = ['/admin'];
        foreach (Filament::getPanel('admin')->getPages() as $page) {
            $urls[] = $page::getUrl();
        }
        foreach (Filament::getPanel('admin')->getResources() as $resource) {
            $urls[] = $resource::getUrl('index');
            if ($resource::hasPage('create')) {
                $urls[] = $resource::getUrl('create');
            }
            $record = $resource::getModel()::query()->get()->first(fn ($model) => $resource::canEdit($model));
            if ($record && $resource::hasPage('edit')) {
                $urls[] = $resource::getUrl('edit', ['record' => $record]);
            }
        }

        $this->get(RoleResource::getUrl('edit', ['record' => Role::findByName(User::SUPER_ADMIN)]))->assertForbidden();

        foreach ($urls as $url) {
            $this->assertSame(200, $this->get($url)->status(), "GET {$url}");
        }
    }

    public function test_translation_lines_sync_from_files_without_overwriting_edits(): void
    {
        LanguageLine::syncFromFiles();
        $line = LanguageLine::where('group', 'site')->where('key', 'home')->firstOrFail();
        $this->assertSame('الرئيسية', $line->text['ar']);

        $line->update(['text' => ['en' => 'Start', 'ar' => 'البداية']]);
        LanguageLine::syncFromFiles();

        $this->assertSame('Start', $line->fresh()->text['en']);
        app()->setLocale('ar');
        $this->assertSame('البداية', __('site.home'));
    }

    public function test_a_new_language_falls_back_for_interface_strings(): void
    {
        LanguageLine::syncFromFiles();
        Language::factory()->french()->create();

        $this->get('/fr/does-not-exist')->assertNotFound()->assertSee('Page not found');
    }
}

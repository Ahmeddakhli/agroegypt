<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_editor_manages_content_but_not_users(): void
    {
        $editor = User::factory()->create()->assignRole('editor');

        $this->assertTrue($editor->can('update', Page::class));
        $this->assertTrue($editor->can('create', Page::class));
        $this->assertFalse($editor->can('viewAny', User::class));
        $this->assertFalse($editor->can('update', User::class));
    }

    public function test_translator_rewords_content_without_creating_or_deleting_it(): void
    {
        $translator = User::factory()->create()->assignRole('translator');

        $this->assertTrue($translator->can('update translations'));
        $this->assertTrue($translator->can('update', Page::class));
        $this->assertFalse($translator->can('create', Page::class));
        $this->assertFalse($translator->can('delete', Page::class));
        $this->assertFalse($translator->can('viewAny', User::class));
        $this->assertFalse($translator->can('update settings'));
    }

    public function test_super_admin_bypasses_every_check(): void
    {
        $root = User::factory()->create()->assignRole(User::SUPER_ADMIN);

        $this->assertTrue($root->can('delete', User::class));
        $this->assertTrue($root->can('anything-at-all'));
    }

    public function test_admin_panel_requires_an_active_user_with_a_role(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');

        $noRole = User::factory()->create();
        $this->actingAs($noRole)->get('/admin')->assertForbidden();

        $inactive = User::factory()->create(['is_active' => false])->assignRole('editor');
        $this->actingAs($inactive)->get('/admin')->assertForbidden();

        $editor = User::factory()->create()->assignRole('editor');
        $this->actingAs($editor)->get('/admin')->assertOk();
    }

    public function test_create_admin_command_creates_a_super_admin(): void
    {
        $this->artisan('app:create-admin', ['--name' => 'Root', '--email' => 'root@example.com', '--password' => 'correct-horse-42'])
            ->assertSuccessful();

        $user = User::firstWhere('email', 'root@example.com');
        $this->assertTrue($user->hasRole(User::SUPER_ADMIN));
        $this->assertTrue($user->is_active);
    }

    public function test_create_admin_command_rejects_weak_passwords(): void
    {
        $this->artisan('app:create-admin', ['--name' => 'Root', '--email' => 'root@example.com', '--password' => 'short'])
            ->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'root@example.com']);
    }
}

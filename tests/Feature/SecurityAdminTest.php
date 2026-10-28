<?php

namespace Tests\Feature;

use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\UserResource;
use App\Models\AuditLog;
use App\Models\LoginLog;
use App\Models\Page;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Auth\Pages\Login;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\AdminTestCase;

class SecurityAdminTest extends AdminTestCase
{
    public function test_super_admin_creates_users_with_strong_passwords_and_roles(): void
    {
        $editor = Role::findByName('editor');

        Livewire::test(CreateUser::class)
            ->fillForm(['name' => 'Mona', 'email' => 'mona@example.com', 'password' => 'short', 'is_active' => true, 'roles' => [$editor->id]])
            ->call('create')
            ->assertHasFormErrors(['password']);

        Livewire::test(CreateUser::class)
            ->fillForm(['name' => 'Mona', 'email' => 'mona@example.com', 'password' => 'orange-groves-2026', 'is_active' => true, 'roles' => [$editor->id]])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::firstWhere('email', 'mona@example.com');
        $this->assertTrue(Hash::check('orange-groves-2026', $user->password));
        $this->assertTrue($user->hasRole('editor'));
    }

    public function test_inactive_accounts_cannot_sign_in_and_attempts_are_logged(): void
    {
        auth()->logout();
        $active = User::factory()->create(['email' => 'on@example.com', 'password' => 'secret-pass-123'])->assignRole('editor');
        User::factory()->create(['email' => 'off@example.com', 'password' => 'secret-pass-123', 'is_active' => false])->assignRole('editor');

        Livewire::test(Login::class)
            ->fillForm(['email' => 'off@example.com', 'password' => 'secret-pass-123'])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);
        $this->assertGuest();

        Livewire::test(Login::class)
            ->fillForm(['email' => 'on@example.com', 'password' => 'wrong'])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);

        Livewire::test(Login::class)
            ->fillForm(['email' => 'on@example.com', 'password' => 'secret-pass-123'])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($active);
        $this->assertNotNull($active->fresh()->last_login_at);
        $this->assertTrue(LoginLog::where('email', 'on@example.com')->where('successful', false)->exists());
        $this->assertTrue(LoginLog::where('user_id', $active->id)->where('successful', true)->exists());
    }

    public function test_users_cannot_disable_or_delete_themselves(): void
    {
        Livewire::test(ListUsers::class)
            ->assertActionHidden(TestAction::make('delete')->table($this->admin));

        $this->assertFalse(UserResource::canDelete($this->admin));
    }

    public function test_admins_without_user_permissions_cannot_manage_accounts_or_escalate(): void
    {
        $root = User::factory()->create()->assignRole(User::SUPER_ADMIN);
        $admin = $this->signIn('admin');

        $this->get(UserResource::getUrl('index'))->assertForbidden();
        $this->get(RoleResource::getUrl('index'))->assertForbidden();
        $this->assertFalse(UserResource::canEdit($root));
        $this->assertFalse($admin->can('update users'));
    }

    public function test_role_permissions_are_saved_per_module_without_losing_others(): void
    {
        $role = Role::create(['name' => 'catalog-manager', 'guard_name' => 'web']);
        $role->givePermissionTo(['view pages', 'update pages']);
        $products = Permission::whereIn('name', ['view products', 'update products'])->pluck('id')->map(fn ($id) => (string) $id)->all();

        Livewire::test(EditRole::class, ['record' => $role->getRouteKey()])
            ->fillForm(['permissions_products' => $products])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertEqualsCanonicalizing(['view pages', 'update pages', 'view products', 'update products'], $role->fresh()->permissions->pluck('name')->all());

        $user = User::factory()->create()->assignRole('catalog-manager');
        $this->assertTrue($user->can('update products'));
        $this->assertFalse($user->can('delete products'));
    }

    public function test_super_admin_role_is_locked(): void
    {
        $this->get(RoleResource::getUrl('edit', ['record' => Role::findByName(User::SUPER_ADMIN)]))->assertForbidden();
    }

    public function test_content_changes_are_recorded_in_the_activity_log(): void
    {
        $page = Page::factory()->create(['en' => ['title' => 'About', 'slug' => 'about']]);

        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
            ->fillForm(['sort' => 7])
            ->call('save')
            ->assertHasNoFormErrors();

        $log = AuditLog::where('auditable_type', $page->getMorphClass())->where('event', 'updated')->latest('id')->firstOrFail();
        $this->assertSame($this->admin->id, $log->user_id);
        $this->assertSame(7, (int) $log->changes['sort']);
    }

    public function test_translation_only_edits_are_recorded_in_the_activity_log(): void
    {
        $page = Page::factory()->create(['en' => ['title' => 'About', 'slug' => 'about']]);

        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
            ->fillForm(['en' => ['title' => 'About Agro Egypt', 'slug' => 'about'], 'ar' => ['title' => 'من نحن', 'slug' => 'about']])
            ->call('save')
            ->assertHasNoFormErrors();

        $logs = AuditLog::where('user_id', $this->admin->id)
            ->where('auditable_type', $page->getMorphClass())->where('auditable_id', $page->id)->get();
        $this->assertTrue($logs->contains(fn (AuditLog $log) => $log->event === 'updated'
            && ($log->changes['locale'] ?? null) === 'en' && ($log->changes['title'] ?? null) === 'About Agro Egypt'));
        $this->assertTrue($logs->contains(fn (AuditLog $log) => $log->event === 'created'
            && ($log->changes['locale'] ?? null) === 'ar' && ($log->changes['title'] ?? null) === 'من نحن'));
    }

    public function test_activity_and_sign_in_logs_are_read_only(): void
    {
        $this->get(route('filament.admin.resources.audit-logs.index'))->assertOk();
        $this->get(route('filament.admin.resources.login-logs.index'))->assertOk();
        $this->assertFalse(AuditLogResource::canCreate());

        $this->signIn('editor');
        $this->get(route('filament.admin.resources.audit-logs.index'))->assertForbidden();
    }

    public function test_site_and_dashboard_send_security_headers(): void
    {
        Page::factory()->template(Page::TEMPLATE_HOME)->create(['en' => ['title' => 'Home', 'slug' => 'home']]);

        foreach (['/en', '/admin/login'] as $url) {
            $this->get($url)
                ->assertHeader('X-Content-Type-Options', 'nosniff')
                ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
                ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
                ->assertHeaderMissing('Strict-Transport-Security');
        }

        $this->get('https://agroegypt.local/en')->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    }
}

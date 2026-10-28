<?php

namespace Tests;

use App\Models\Language;
use App\Models\Media;
use App\Models\User;
use App\Support\MediaLibrary;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Base for admin dashboard tests: roles, English (default) + Arabic, fake disks, signed-in super-admin.
 */
abstract class AdminTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');

        $this->seed(RolesAndPermissionsSeeder::class);
        Language::factory()->english()->create();
        Language::factory()->arabic()->create();

        $this->admin = $this->signIn(User::SUPER_ADMIN);
    }

    protected function signIn(string $role): User
    {
        $user = User::factory()->create()->assignRole($role);
        $this->actingAs($user);

        return $user;
    }

    protected function image(string $name = 'photo.jpg', int $width = 1600, int $height = 900): Media
    {
        return MediaLibrary::store(UploadedFile::fake()->image($name, $width, $height));
    }
}

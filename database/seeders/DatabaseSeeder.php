<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            LanguageSeeder::class,
            ReferenceContentSeeder::class,
        ]);

        $this->command?->call('app:sync-translations');
    }
}

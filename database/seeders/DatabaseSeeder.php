<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * php artisan migrate --seed   (or)   php artisan db:seed
     * Every seeder uses updateOrCreate, so running it again is safe.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            SettingsSeeder::class,
            HomeContentSeeder::class,
        ]);
    }
}

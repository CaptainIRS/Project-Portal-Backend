<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            StatusSeeder::class,
            TypeSeeder::class,
            StackSeeder::class,
            UserSeeder::class,
            ProjectSeeder::class,
            ProjectStackSeeder::class,
            ProjectUserSeeder::class
        ]);
    }
}

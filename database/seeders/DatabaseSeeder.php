<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed in correct order (dependencies first)
        $this->call([
            ContinentSeeder::class,
            CountrySeeder::class,
            LeagueSeeder::class,
            TeamSeeder::class,
            CategorySeeder::class,
            SizeSeeder::class,
            SpecialCategorySeeder::class,
            ProductSeeder::class,
            CouponSeeder::class,
            ImageSeeder::class,
        ]);

        // Create demo users
        User::factory()->create([
            'name' => 'Demo Admin',
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);

        User::factory()->create([
            'name' => 'Demo User',
            'email' => 'user@example.com',
            'role' => 'client',
        ]);

        User::factory(10)->create();
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Category::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $categories = [
            ['name' => 'Home Jerseys'],
            ['name' => 'Away Jerseys'],
            ['name' => 'Third Jerseys'],
            ['name' => 'Training Kits'],
            ['name' => 'Warm-up Jackets'],
            ['name' => 'Track Jackets'],
            ['name' => 'Shorts'],
            ['name' => 'T-Shirts'],
            ['name' => 'Polo Shirts'],
            ['name' => 'Hoodies'],
            ['name' => 'Sweatshirts'],
            ['name' => 'Caps'],
            ['name' => 'Beanies'],
            ['name' => 'Scarves'],
            ['name' => 'Gloves'],
            ['name' => 'Socks'],
            ['name' => 'Shin Guards'],
            ['name' => 'Balls'],
            ['name' => 'Backpacks'],
            ['name' => 'Bags'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}

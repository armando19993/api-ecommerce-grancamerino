<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SpecialCategory;
use Illuminate\Support\Facades\DB;

class SpecialCategorySeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        SpecialCategory::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $categories = [
            ['name' => 'Player Edition'],
            ['name' => 'Limited Edition'],
            ['name' => 'Retro Collection'],
            ['name' => 'Vintage'],
            ['name' => 'Autographed'],
            ['name' => 'Match Worn'],
            ['name' => 'Special Edition'],
            ['name' => 'Anniversary Edition'],
            ['name' => 'Champions Edition'],
            ['name' => 'Fan Favorites'],
            ['name' => 'Clearance'],
            ['name' => 'New Arrival'],
            ['name' => 'Best Seller'],
            ['name' => 'Exclusive'],
        ];

        foreach ($categories as $category) {
            SpecialCategory::create($category);
        }
    }
}

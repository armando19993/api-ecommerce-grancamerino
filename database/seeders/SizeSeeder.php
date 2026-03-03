<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Size;
use Illuminate\Support\Facades\DB;

class SizeSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Size::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $sizes = [
            ['name' => 'XS'],
            ['name' => 'S'],
            ['name' => 'M'],
            ['name' => 'L'],
            ['name' => 'XL'],
            ['name' => 'XXL'],
            ['name' => 'XXXL'],
            ['name' => 'Youth XS'],
            ['name' => 'Youth S'],
            ['name' => 'Youth M'],
            ['name' => 'Youth L'],
            ['name' => 'Youth XL'],
        ];

        foreach ($sizes as $size) {
            Size::create($size);
        }
    }
}

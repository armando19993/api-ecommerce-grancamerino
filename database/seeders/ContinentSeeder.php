<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Continent;
use Illuminate\Support\Facades\DB;

class ContinentSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Continent::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $continents = [
            ['name' => 'Africa'],
            ['name' => 'Asia'],
            ['name' => 'Europe'],
            ['name' => 'North America'],
            ['name' => 'South America'],
            ['name' => 'Oceania'],
            ['name' => 'Antarctica']
        ];

        foreach ($continents as $continent) {
            Continent::create($continent);
        }
    }
}

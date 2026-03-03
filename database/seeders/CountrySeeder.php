<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Country;
use App\Models\Continent;
use Illuminate\Support\Facades\DB;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Country::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $continents = Continent::all()->keyBy('name');

        $countries = [
            // South America
            ['name' => 'Argentina', 'continent' => 'South America'],
            ['name' => 'Brazil', 'continent' => 'South America'],
            ['name' => 'Colombia', 'continent' => 'South America'],
            ['name' => 'Chile', 'continent' => 'South America'],
            ['name' => 'Peru', 'continent' => 'South America'],
            ['name' => 'Uruguay', 'continent' => 'South America'],
            ['name' => 'Ecuador', 'continent' => 'South America'],
            ['name' => 'Venezuela', 'continent' => 'South America'],
            
            // Europe
            ['name' => 'Spain', 'continent' => 'Europe'],
            ['name' => 'Italy', 'continent' => 'Europe'],
            ['name' => 'Germany', 'continent' => 'Europe'],
            ['name' => 'France', 'continent' => 'Europe'],
            ['name' => 'England', 'continent' => 'Europe'],
            ['name' => 'Netherlands', 'continent' => 'Europe'],
            ['name' => 'Portugal', 'continent' => 'Europe'],
            ['name' => 'Belgium', 'continent' => 'Europe'],
            
            // North America
            ['name' => 'United States', 'continent' => 'North America'],
            ['name' => 'Canada', 'continent' => 'North America'],
            ['name' => 'Mexico', 'continent' => 'North America'],
            
            // Asia
            ['name' => 'Japan', 'continent' => 'Asia'],
            ['name' => 'South Korea', 'continent' => 'Asia'],
            ['name' => 'China', 'continent' => 'Asia'],
            ['name' => 'India', 'continent' => 'Asia'],
        ];

        foreach ($countries as $country) {
            Country::create([
                'name' => $country['name'],
                'continent_id' => $continents[$country['continent']]->id
            ]);
        }
    }
}

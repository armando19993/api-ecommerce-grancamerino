<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\League;
use App\Models\Country;
use Illuminate\Support\Facades\DB;

class LeagueSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        League::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $countries = Country::all()->keyBy('name');

        $leagues = [
            // South America
            ['name' => 'Liga BetPlay', 'country' => 'Colombia'],
            ['name' => 'Brasileirão', 'country' => 'Brazil'],
            ['name' => 'Liga Profesional', 'country' => 'Argentina'],
            ['name' => 'Primera División', 'country' => 'Chile'],
            ['name' => 'Liga 1', 'country' => 'Peru'],
            
            // Europe
            ['name' => 'La Liga', 'country' => 'Spain'],
            ['name' => 'Serie A', 'country' => 'Italy'],
            ['name' => 'Bundesliga', 'country' => 'Germany'],
            ['name' => 'Ligue 1', 'country' => 'France'],
            ['name' => 'Premier League', 'country' => 'England'],
            ['name' => 'Eredivisie', 'country' => 'Netherlands'],
            ['name' => 'Primeira Liga', 'country' => 'Portugal'],
            
            // North America
            ['name' => 'MLS', 'country' => 'United States'],
            ['name' => 'Liga MX', 'country' => 'Mexico'],
            
            // Asia
            ['name' => 'J1 League', 'country' => 'Japan'],
            ['name' => 'K League 1', 'country' => 'South Korea'],
        ];

        foreach ($leagues as $league) {
            League::create([
                'name' => $league['name'],
                'country_id' => $countries[$league['country']]->id
            ]);
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Team;
use App\Models\Country;
use App\Models\League;
use Illuminate\Support\Facades\DB;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Team::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $countries = Country::all()->keyBy('name');
        $leagues = League::all()->keyBy('name');

        $teams = [
            // Colombia
            ['name' => 'Millonarios', 'country' => 'Colombia', 'league' => 'Liga BetPlay', 'type' => 'club'],
            ['name' => 'Nacional', 'country' => 'Colombia', 'league' => 'Liga BetPlay', 'type' => 'club'],
            ['name' => 'America', 'country' => 'Colombia', 'league' => 'Liga BetPlay', 'type' => 'club'],
            ['name' => 'Junior', 'country' => 'Colombia', 'league' => 'Liga BetPlay', 'type' => 'club'],
            ['name' => 'Colombia', 'country' => 'Colombia', 'league' => null, 'type' => 'national_team'],
            
            // Brazil
            ['name' => 'Flamengo', 'country' => 'Brazil', 'league' => 'Brasileirão', 'type' => 'club'],
            ['name' => 'Palmeiras', 'country' => 'Brazil', 'league' => 'Brasileirão', 'type' => 'club'],
            ['name' => 'Corinthians', 'country' => 'Brazil', 'league' => 'Brasileirão', 'type' => 'club'],
            ['name' => 'São Paulo', 'country' => 'Brazil', 'league' => 'Brasileirão', 'type' => 'club'],
            ['name' => 'Brazil', 'country' => 'Brazil', 'league' => null, 'type' => 'national_team'],
            
            // Argentina
            ['name' => 'River Plate', 'country' => 'Argentina', 'league' => 'Liga Profesional', 'type' => 'club'],
            ['name' => 'Boca Juniors', 'country' => 'Argentina', 'league' => 'Liga Profesional', 'type' => 'club'],
            ['name' => 'Independiente', 'country' => 'Argentina', 'league' => 'Liga Profesional', 'type' => 'club'],
            ['name' => 'Racing', 'country' => 'Argentina', 'league' => 'Liga Profesional', 'type' => 'club'],
            ['name' => 'Argentina', 'country' => 'Argentina', 'league' => null, 'type' => 'national_team'],
            
            // Spain
            ['name' => 'Real Madrid', 'country' => 'Spain', 'league' => 'La Liga', 'type' => 'club'],
            ['name' => 'Barcelona', 'country' => 'Spain', 'league' => 'La Liga', 'type' => 'club'],
            ['name' => 'Atlético Madrid', 'country' => 'Spain', 'league' => 'La Liga', 'type' => 'club'],
            ['name' => 'Sevilla', 'country' => 'Spain', 'league' => 'La Liga', 'type' => 'club'],
            ['name' => 'Spain', 'country' => 'Spain', 'league' => null, 'type' => 'national_team'],
            
            // England
            ['name' => 'Manchester United', 'country' => 'England', 'league' => 'Premier League', 'type' => 'club'],
            ['name' => 'Manchester City', 'country' => 'England', 'league' => 'Premier League', 'type' => 'club'],
            ['name' => 'Liverpool', 'country' => 'England', 'league' => 'Premier League', 'type' => 'club'],
            ['name' => 'Chelsea', 'country' => 'England', 'league' => 'Premier League', 'type' => 'club'],
            ['name' => 'Arsenal', 'country' => 'England', 'league' => 'Premier League', 'type' => 'club'],
            ['name' => 'England', 'country' => 'England', 'league' => null, 'type' => 'national_team'],
            
            // Italy
            ['name' => 'Juventus', 'country' => 'Italy', 'league' => 'Serie A', 'type' => 'club'],
            ['name' => 'AC Milan', 'country' => 'Italy', 'league' => 'Serie A', 'type' => 'club'],
            ['name' => 'Inter', 'country' => 'Italy', 'league' => 'Serie A', 'type' => 'club'],
            ['name' => 'Napoli', 'country' => 'Italy', 'league' => 'Serie A', 'type' => 'club'],
            ['name' => 'Italy', 'country' => 'Italy', 'league' => null, 'type' => 'national_team'],
            
            // Germany
            ['name' => 'Bayern Munich', 'country' => 'Germany', 'league' => 'Bundesliga', 'type' => 'club'],
            ['name' => 'Borussia Dortmund', 'country' => 'Germany', 'league' => 'Bundesliga', 'type' => 'club'],
            ['name' => 'RB Leipzig', 'country' => 'Germany', 'league' => 'Bundesliga', 'type' => 'club'],
            ['name' => 'Germany', 'country' => 'Germany', 'league' => null, 'type' => 'national_team'],
            
            // France
            ['name' => 'PSG', 'country' => 'France', 'league' => 'Ligue 1', 'type' => 'club'],
            ['name' => 'Lyon', 'country' => 'France', 'league' => 'Ligue 1', 'type' => 'club'],
            ['name' => 'Marseille', 'country' => 'France', 'league' => 'Ligue 1', 'type' => 'club'],
            ['name' => 'France', 'country' => 'France', 'league' => null, 'type' => 'national_team'],
        ];

        foreach ($teams as $team) {
            Team::create([
                'name' => $team['name'],
                'country_id' => $countries[$team['country']]->id,
                'league_id' => $team['league'] ? $leagues[$team['league']]->id : null,
                'type' => $team['type']
            ]);
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\League;
use App\Models\Country;
use App\Models\Team;
use Illuminate\Support\Facades\DB;

class ImageSeeder extends Seeder
{
    public function run(): void
    {
        // Categories - Use placeholder images
        $categories = Category::all();
        foreach ($categories as $category) {
            $category->update([
                'image_url' => $this->getCategoryImageUrl($category->name)
            ]);
        }

        // Countries - Use flag images
        $countries = Country::all();
        foreach ($countries as $country) {
            $country->update([
                'image_url' => $this->getCountryImageUrl($country->name)
            ]);
        }

        // Leagues - Use logo images
        $leagues = League::all();
        foreach ($leagues as $league) {
            $league->update([
                'image_url' => $this->getLeagueImageUrl($league->name)
            ]);
        }

        // Teams - Use team logo images
        $teams = Team::all();
        foreach ($teams as $team) {
            $team->update([
                'image_url' => $this->getTeamImageUrl($team->name)
            ]);
        }
    }

    private function getCategoryImageUrl($categoryName): string
    {
        $categoryImages = [
            'Home Jerseys' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/categories/home-jerseys.webp',
            'Away Jerseys' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/categories/away-jerseys.webp',
            'Third Jerseys' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/categories/third-jerseys.webp',
            'Training Kits' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/categories/training-kits.webp',
            'Warm-up Jackets' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/categories/warmup-jackets.webp',
            'Track Jackets' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/categories/track-jackets.webp',
            'Shorts' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/categories/shorts.webp',
            'T-Shirts' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/categories/tshirts.webp',
            'Polo Shirts' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/categories/polo-shirts.webp',
            'Hoodies' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/categories/hoodies.webp',
            'Sweatshirts' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/categories/sweatshirts.webp',
            'Caps' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/categories/caps.webp',
            'Beanies' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/categories/beanies.webp',
            'Scarves' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/categories/scarves.webp',
            'Gloves' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/categories/gloves.webp',
            'Socks' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/categories/socks.webp',
            'Shin Guards' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/categories/shin-guards.webp',
            'Balls' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/categories/balls.webp',
            'Backpacks' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/categories/backpacks.webp',
            'Bags' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/categories/bags.webp',
        ];

        return $categoryImages[$categoryName] ?? 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/categories/default.webp';
    }

    private function getCountryImageUrl($countryName): string
    {
        $countryImages = [
            'Colombia' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/countries/colombia-flag.webp',
            'Brazil' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/countries/brazil-flag.webp',
            'Argentina' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/countries/argentina-flag.webp',
            'Chile' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/countries/chile-flag.webp',
            'Peru' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/countries/peru-flag.webp',
            'Uruguay' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/countries/uruguay-flag.webp',
            'Ecuador' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/countries/ecuador-flag.webp',
            'Venezuela' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/countries/venezuela-flag.webp',
            'Spain' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/countries/spain-flag.webp',
            'Italy' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/countries/italy-flag.webp',
            'Germany' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/countries/germany-flag.webp',
            'France' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/countries/france-flag.webp',
            'England' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/countries/england-flag.webp',
            'Netherlands' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/countries/netherlands-flag.webp',
            'Portugal' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/countries/portugal-flag.webp',
            'Belgium' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/countries/belgium-flag.webp',
            'United States' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/countries/usa-flag.webp',
            'Canada' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/countries/canada-flag.webp',
            'Mexico' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/countries/mexico-flag.webp',
            'Japan' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/countries/japan-flag.webp',
            'South Korea' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/countries/south-korea-flag.webp',
            'China' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/countries/china-flag.webp',
            'India' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/countries/india-flag.webp',
        ];

        return $countryImages[$countryName] ?? 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/countries/default-flag.webp';
    }

    private function getLeagueImageUrl($leagueName): string
    {
        $leagueImages = [
            'Liga BetPlay' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/leagues/liga-betplay.webp',
            'Brasileirão' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/leagues/brasileirao.webp',
            'Liga Profesional' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/leagues/liga-profesional.webp',
            'La Liga' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/leagues/la-liga.webp',
            'Premier League' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/leagues/premier-league.webp',
            'Serie A' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/leagues/serie-a.webp',
            'Bundesliga' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/leagues/bundesliga.webp',
            'Ligue 1' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/leagues/ligue-1.webp',
            'MLS' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/leagues/mls.webp',
            'J1 League' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/leagues/j1-league.webp',
            'K League' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/leagues/k-league.webp',
            'Eredivisie' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/leagues/eredivisie.webp',
            'Primeira Liga' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/leagues/primeira-liga.webp',
            'Chinese Super League' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/leagues/chinese-super-league.webp',
            'Indian Super League' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/leagues/indian-super-league.webp',
        ];

        return $leagueImages[$leagueName] ?? 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/leagues/default.webp';
    }

    private function getTeamImageUrl($teamName): string
    {
        $teamImages = [
            // Colombia
            'Millonarios' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/teams/millonarios.webp',
            'Nacional' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/teams/nacional.webp',
            'America' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/teams/america.webp',
            'Junior' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/teams/junior.webp',
            'Colombia' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/teams/colombia.webp',
            
            // Brazil
            'Flamengo' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/teams/flamengo.webp',
            'Palmeiras' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/teams/palmeiras.webp',
            'Corinthians' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/teams/corinthians.webp',
            'São Paulo' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/teams/sao-paulo.webp',
            'Brazil' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/teams/brazil.webp',
            
            // Argentina
            'River Plate' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/teams/river-plate.webp',
            'Boca Juniors' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/teams/boca-juniors.webp',
            'Independiente' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/teams/independiente.webp',
            'Racing' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/teams/racing.webp',
            'Argentina' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/teams/argentina.webp',
            
            // Spain
            'Real Madrid' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/teams/real-madrid.webp',
            'Barcelona' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/teams/barcelona.webp',
            'Atlético Madrid' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/teams/atletico-madrid.webp',
            'Sevilla' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/teams/sevilla.webp',
            'Spain' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/teams/spain.webp',
            
            // England
            'Manchester United' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/teams/manchester-united.webp',
            'Manchester City' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/teams/manchester-city.webp',
            'Liverpool' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/teams/liverpool.webp',
            'Chelsea' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/teams/chelsea.webp',
            'Arsenal' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/teams/arsenal.webp',
            'England' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/teams/england.webp',
            
            // Italy
            'Juventus' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/teams/juventus.webp',
            'AC Milan' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/teams/ac-milan.webp',
            'Inter' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/teams/inter.webp',
            'Napoli' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/teams/napoli.webp',
            'Italy' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/teams/italy.webp',
            
            // Germany
            'Bayern Munich' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/teams/bayern-munich.webp',
            'Borussia Dortmund' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/teams/borussia-dortmund.webp',
            'RB Leipzig' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/teams/rb-leipzig.webp',
            'Germany' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/teams/germany.webp',
            
            // France
            'PSG' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/teams/psg.webp',
            'Lyon' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/teams/lyon.webp',
            'Marseille' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/teams/marseille.webp',
            'France' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/teams/france.webp',
        ];

        return $teamImages[$teamName] ?? 'https://res.cloudinary.com/demo/image/upload/v1234567890/api-shirt/teams/default.webp';
    }
}

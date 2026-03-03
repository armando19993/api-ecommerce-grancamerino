<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;
use App\Models\Team;
use App\Models\Size;
use App\Models\ProductVariant;
use App\Models\ProductImage;
use App\Models\SpecialCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Product::truncate();
        ProductVariant::truncate();
        ProductImage::truncate();
        DB::table('product_special_category')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $categories = Category::all()->keyBy('name');
        $teams = Team::all();
        $sizes = Size::all()->keyBy('name');
        $specialCategories = SpecialCategory::all()->keyBy('name');

        $products = [];

        // Generate products for each team
        foreach ($teams as $team) {
            // Home Jersey
            $products[] = $this->createProduct($team, $categories['Home Jerseys'], 'Home', [
                'price_usd' => 89.99,
                'price_cop' => 350000,
                'special_categories' => ['New Arrival', 'Best Seller']
            ]);

            // Away Jersey
            $products[] = $this->createProduct($team, $categories['Away Jerseys'], 'Away', [
                'price_usd' => 89.99,
                'price_cop' => 350000,
                'special_categories' => ['New Arrival']
            ]);

            // Third Jersey (only for popular teams)
            if (in_array($team->name, ['Real Madrid', 'Barcelona', 'Manchester United', 'Liverpool', 'Juventus', 'Bayern Munich'])) {
                $products[] = $this->createProduct($team, $categories['Third Jerseys'], 'Third', [
                    'price_usd' => 99.99,
                    'price_cop' => 380000,
                    'special_categories' => ['Limited Edition', 'Special Edition']
                ]);
            }

            // Training Kit
            $products[] = $this->createProduct($team, $categories['Training Kits'], 'Training', [
                'price_usd' => 59.99,
                'price_cop' => 220000,
                'special_categories' => []
            ]);

            // Warm-up Jacket
            $products[] = $this->createProduct($team, $categories['Warm-up Jackets'], 'Warm-up', [
                'price_usd' => 79.99,
                'price_cop' => 290000,
                'special_categories' => []
            ]);

            // Cap
            $products[] = $this->createProduct($team, $categories['Caps'], 'Cap', [
                'price_usd' => 29.99,
                'price_cop' => 110000,
                'special_categories' => ['Fan Favorites']
            ]);
        }

        // Create products and their variants
        foreach ($products as $productData) {
            $product = Product::create([
                'name' => $productData['name'],
                'slug' => $productData['slug'],
                'category_id' => $productData['category_id'],
                'team_id' => $productData['team_id'],
                'size_id' => $sizes['M']->id, // Default size for product
                'price_usd' => $productData['price_usd'],
                'price_cop' => $productData['price_cop']
            ]);

            // Create variants for different sizes
            $availableSizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
            foreach ($availableSizes as $sizeName) {
                ProductVariant::create([
                    'product_id' => $product->id,
                    'size_id' => $sizes[$sizeName]->id,
                    'stock' => rand(10, 50)
                ]);
            }

            // Create product images
            for ($i = 1; $i <= 3; $i++) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'url' => "https://example.com/images/{$product->slug}-{$i}.jpg",
                    'is_primary' => $i === 1
                ]);
            }

            // Attach special categories
            foreach ($productData['special_categories'] as $categoryName) {
                if (isset($specialCategories[$categoryName])) {
                    $product->specialCategories()->attach($specialCategories[$categoryName]);
                }
            }
        }
    }

    private function createProduct($team, $category, $type, $options): array
    {
        $name = "{$team->name} {$type} " . $category->name;
        
        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . uniqid(),
            'category_id' => $category->id,
            'team_id' => $team->id,
            'price_usd' => $options['price_usd'],
            'price_cop' => $options['price_cop'],
            'special_categories' => $options['special_categories']
        ];
    }
}

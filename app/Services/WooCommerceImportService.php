<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\Size;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Cloudinary\Cloudinary;

class WooCommerceImportService
{
    protected $cloudinary;

    public function __construct()
    {
        // Usamos tu misma configuración de Cloudinary
        $this->cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => config('cloudinary.cloud_name'),
                'api_key' => config('cloudinary.api_key'),
                'api_secret' => config('cloudinary.api_secret'),
            ],
        ]);
    }

    public function importByCategory($wooCategoryId, $teamId)
    {
        // 1. Obtener la categoría de WooCommerce
        $wooCategory = $this->getWooData("products/categories/{$wooCategoryId}");
        
        // 2. Crear/Actualizar Categoría Local
        $category = Category::updateOrCreate(
            ['slug' => $wooCategory['slug']],
            [
                'name' => $wooCategory['name'],
                'description' => $wooCategory['description'] ?? '',
                'is_active' => true,
                'image_url' => isset($wooCategory['image']['src']) 
                    ? $this->uploadToCloudinary($wooCategory['image']['src'], 'category') 
                    : null,
            ]
        );

        // 3. Obtener Productos
        $products = $this->getWooData("products", ['category' => $wooCategoryId, 'per_page' => 100]);

        foreach ($products as $item) {
            $this->storeProduct($item, $category->id, $teamId);
        }

        return count($products);
    }

    protected function storeProduct($data, $categoryId, $teamId)
    {
        // El precio que viene de WooCommerce es en COP
        $priceCop = floatval($data['price'] ?? 0);
        $priceUsd = $priceCop > 0 ? round($priceCop / 3770, 2) : 0;
        
        // Siguiendo tu lógica de validación/creación
        $product = Product::updateOrCreate(
            ['slug' => $data['slug']],
            [
                'name'        => $data['name'],
                'category_id' => $categoryId,
                'team_id'     => $teamId,
                'price_cop'   => $priceCop,
                'price_usd'   => $priceUsd,
                'description' => $data['description'] ?? '',
            ]
        );

        // Procesar Imágenes con tu lógica de Cloudinary
        if (!empty($data['images'])) {
            foreach ($data['images'] as $index => $img) {
                $secureUrl = $this->uploadToCloudinary($img['src'], 'product');
                
                $product->images()->updateOrCreate(
                    ['url' => $secureUrl],
                    ['is_primary' => $index === 0]
                );
            }
        }

        // Procesar Variantes (Tallas)
        if (!empty($data['attributes'])) {
            foreach ($data['attributes'] as $attr) {
                if (Str::contains(Str::lower($attr['name']), 'talla')) {
                    foreach ($attr['options'] as $option) {
                        $size = Size::firstOrCreate(['name' => $option]);
                        $product->variants()->updateOrCreate(['size_id' => $size->id]);
                    }
                }
            }
        }
    }

    protected function uploadToCloudinary($url, $type)
    {
        // Aplicamos tus opciones de redimensionamiento del Switch
        $options = [
            'folder' => "{$type}s",
            'width'  => ($type == 'category') ? 400 : 1200,
            'height' => ($type == 'category') ? 400 : 1200,
            'crop'   => 'limit',
            'quality' => 'auto',
        ];

        $result = $this->cloudinary->uploadApi()->upload($url, $options);
        return $result['secure_url'];
    }

    private function getWooData($endpoint, $params = [])
    {
        $url = config('services.woocommerce.url');
        $key = config('services.woocommerce.key');
        $secret = config('services.woocommerce.secret');

        // Validar que las credenciales estén configuradas
        if (empty($url) || empty($key) || empty($secret)) {
            throw new \Exception('WooCommerce credentials are not configured. Please set WOOCOMMERCE_URL, WOOCOMMERCE_KEY, and WOOCOMMERCE_SECRET in your .env file.');
        }

        return Http::withBasicAuth($key, $secret)
            ->get("{$url}/wp-json/wc/v3/{$endpoint}", $params)
            ->json();
    }
}
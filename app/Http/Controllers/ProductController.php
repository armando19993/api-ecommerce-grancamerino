<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'team.country', 'team.league', 'images', 'variants.size', 'specialCategories']);
        
        // Colecciones especiales
        $isCollection = false;
        if ($request->has('collection') && !empty($request->collection)) {
            $collection = $request->collection;
            $isCollection = true;
            
            if ($collection === 'new_arrivals') {
                // Últimos 50 productos más recientes
                $query->orderBy('created_at', 'desc');
            } elseif ($collection === 'best_sellers') {
                // Productos más vendidos (últimos 50)
                $query->withCount('orderItems')
                    ->orderBy('order_items_count', 'desc')
                    ->orderBy('created_at', 'desc');
            }
        }
        
        // Filtro por categoría
        if ($request->has('category_id') && !empty($request->category_id)) {
            $query->where('category_id', $request->category_id);
        }
        
        // Filtro por equipo
        if ($request->has('team_id') && !empty($request->team_id)) {
            $query->where('team_id', $request->team_id);
        }
        
        // Filtro por país (a través del equipo)
        if ($request->has('country_id') && !empty($request->country_id)) {
            $query->whereHas('team', function($q) use ($request) {
                $q->where('country_id', $request->country_id);
            });
        }
        
        // Filtro por liga (a través del equipo)
        if ($request->has('league_id') && !empty($request->league_id)) {
            $query->whereHas('team', function($q) use ($request) {
                $q->where('league_id', $request->league_id);
            });
        }
        
        // Filtro por categoría especial
        if ($request->has('special_category_id') && !empty($request->special_category_id)) {
            $query->whereHas('specialCategories', function($q) use ($request) {
                $q->where('special_categories.id', $request->special_category_id);
            });
        }
        
        // Filtro por estado activo/inactivo
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        
        // Búsqueda por nombre o descripción
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }
        
        // Filtro por rango de precio USD
        if ($request->has('min_price_usd') && !empty($request->min_price_usd)) {
            $query->where('price_usd', '>=', $request->min_price_usd);
        }
        if ($request->has('max_price_usd') && !empty($request->max_price_usd)) {
            $query->where('price_usd', '<=', $request->max_price_usd);
        }
        
        // Filtro por rango de precio COP
        if ($request->has('min_price_cop') && !empty($request->min_price_cop)) {
            $query->where('price_cop', '>=', $request->min_price_cop);
        }
        if ($request->has('max_price_cop') && !empty($request->max_price_cop)) {
            $query->where('price_cop', '<=', $request->max_price_cop);
        }
        
        // Ordenamiento (solo si no es una colección especial)
        if (!$isCollection) {
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');
            
            // Validar campos de ordenamiento permitidos
            $allowedSortFields = ['name', 'price_usd', 'price_cop', 'created_at', 'updated_at'];
            if (in_array($sortBy, $allowedSortFields)) {
                $query->orderBy($sortBy, $sortOrder);
            } else {
                $query->orderBy('created_at', 'desc');
            }
        }
        
        // Paginación (solo si no es una colección especial con límite)
        if ($isCollection) {
            // Para colecciones especiales, usar limit si se proporciona, sino 50 por defecto
            $limit = $request->input('limit', 50);
            $limit = min($limit, 100); // Máximo 100 items
            
            $products = $query->take($limit)->get();
            
            return response()->json([
                'status' => 'success',
                'data' => $products,
                'collection' => $request->collection,
                'total' => $products->count()
            ]);
        }
        
        $perPage = $request->input('per_page', 20);
        $perPage = min($perPage, 100); // Máximo 100 items por página
        
        $products = $query->paginate($perPage);
        
        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:products',
            'category_id' => 'required|exists:categories,id',
            'team_id' => 'required|exists:teams,id',
            'price_usd' => 'required|numeric|min:0',
            'price_cop' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'images' => 'nullable|array',
            'variants' => 'required|array|min:1',
            'variants.*.size_id' => 'required|exists:sizes,id',
        ]);

        // Generar slug automáticamente si no se proporciona
        if (empty($validated['slug'])) {
            $validated['slug'] = $this->generateUniqueSlug($validated['name']);
        }

        $product = Product::create($validated);

        // Add images if provided
        if (!empty($validated['images'])) {
            // Procesar imágenes: pueden venir como strings (URLs) o como objetos
            $imageUrls = [];
            
            foreach ($validated['images'] as $image) {
                if (is_string($image)) {
                    // Si es un string, es una URL directa
                    $imageUrls[] = ['url' => $image, 'is_primary' => false];
                } elseif (is_array($image) && isset($image['url'])) {
                    // Si es un array con 'url', extraer la URL y is_primary
                    $imageUrls[] = [
                        'url' => $image['url'],
                        'is_primary' => $image['is_primary'] ?? false
                    ];
                }
            }
            
            // Si no hay ninguna imagen marcada como principal, marcar la primera
            $hasPrimary = collect($imageUrls)->contains('is_primary', true);
            if (!$hasPrimary && !empty($imageUrls)) {
                $imageUrls[0]['is_primary'] = true;
            }
            
            // Agregar imágenes
            foreach ($imageUrls as $imageData) {
                $product->images()->create($imageData);
            }
        }

        // Add variants (required) - solo tallas únicas
        $addedSizes = [];
        foreach ($validated['variants'] as $variant) {
            // Evitar duplicados
            if (!in_array($variant['size_id'], $addedSizes)) {
                $product->variants()->create([
                    'size_id' => $variant['size_id'],
                ]);
                $addedSizes[] = $variant['size_id'];
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => $product->load(['category', 'team', 'images', 'variants.size'])
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        return response()->json([
            'status' => 'success',
            'data' => $product->load(['category', 'team', 'images', 'variants.size', 'specialCategories'])
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|nullable|string|max:255|unique:products,slug,' . $product->id,
            'category_id' => 'sometimes|exists:categories,id',
            'team_id' => 'sometimes|exists:teams,id',
            'price_usd' => 'sometimes|numeric|min:0',
            'price_cop' => 'sometimes|numeric|min:0',
            'description' => 'sometimes|nullable|string',
            'images' => 'sometimes|nullable|array',
            'variants' => 'sometimes|array',
            'variants.*.size_id' => 'required|exists:sizes,id',
        ]);

        // Si se actualiza el nombre pero no el slug, regenerar el slug
        if (isset($validated['name']) && !isset($validated['slug'])) {
            $validated['slug'] = $this->generateUniqueSlug($validated['name'], $product->id);
        }

        $product->update($validated);

        // Update images if provided
        if (isset($validated['images'])) {
            // Procesar imágenes: pueden venir como strings (URLs) o como objetos
            $imageUrls = [];
            
            foreach ($validated['images'] as $image) {
                if (is_string($image)) {
                    // Si es un string, es una URL directa
                    $imageUrls[] = ['url' => $image, 'is_primary' => false];
                } elseif (is_array($image) && isset($image['url'])) {
                    // Si es un array con 'url', extraer la URL y is_primary
                    $imageUrls[] = [
                        'url' => $image['url'],
                        'is_primary' => $image['is_primary'] ?? false
                    ];
                }
            }
            
            // Eliminar imágenes existentes
            $product->images()->delete();
            
            // Si no hay ninguna imagen marcada como principal, marcar la primera
            $hasPrimary = collect($imageUrls)->contains('is_primary', true);
            if (!$hasPrimary && !empty($imageUrls)) {
                $imageUrls[0]['is_primary'] = true;
            }
            
            // Agregar nuevas imágenes
            foreach ($imageUrls as $imageData) {
                $product->images()->create($imageData);
            }
        }

        // Update variants if provided
        if (isset($validated['variants'])) {
            $product->variants()->delete();
            
            // Add variants - solo tallas únicas
            $addedSizes = [];
            foreach ($validated['variants'] as $variant) {
                // Evitar duplicados
                if (!in_array($variant['size_id'], $addedSizes)) {
                    $product->variants()->create([
                        'size_id' => $variant['size_id'],
                    ]);
                    $addedSizes[] = $variant['size_id'];
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => $product->load(['category', 'team', 'images', 'variants.size', 'specialCategories'])
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $product->images()->delete();
        $product->variants()->delete();
        $product->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Product deleted successfully'
        ]);
    }

    /**
     * Bulk update prices for multiple products
     * Only accessible by admin users
     */
    public function bulkPriceUpdate(Request $request)
    {
        $validated = $request->validate([
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'required|uuid|exists:products,id',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'operation' => 'required|in:increase,decrease',
            'currency' => 'nullable|in:usd,cop,both',
        ]);

        $currency = $validated['currency'] ?? 'both';
        $type = $validated['type'];
        $value = $validated['value'];
        $operation = $validated['operation'];

        $products = Product::whereIn('id', $validated['product_ids'])->get();

        if ($products->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No products found with the provided IDs'
            ], 404);
        }

        $updatedProducts = [];
        $errors = [];

        foreach ($products as $product) {
            try {
                $oldPriceUsd = $product->price_usd;
                $oldPriceCop = $product->price_cop;
                $newPriceUsd = $oldPriceUsd;
                $newPriceCop = $oldPriceCop;

                // Calcular nuevo precio USD
                if ($currency === 'usd' || $currency === 'both') {
                    if ($type === 'percentage') {
                        if ($operation === 'increase') {
                            $newPriceUsd = $oldPriceUsd * (1 + $value / 100);
                        } else {
                            $newPriceUsd = $oldPriceUsd * (1 - $value / 100);
                        }
                    } else {
                        if ($operation === 'increase') {
                            $newPriceUsd = $oldPriceUsd + $value;
                        } else {
                            $newPriceUsd = $oldPriceUsd - $value;
                        }
                    }
                    
                    $newPriceUsd = max(0, $newPriceUsd);
                    $newPriceUsd = round($newPriceUsd, 2);
                }

                // Calcular nuevo precio COP
                if ($currency === 'cop' || $currency === 'both') {
                    if ($type === 'percentage') {
                        if ($operation === 'increase') {
                            $newPriceCop = $oldPriceCop * (1 + $value / 100);
                        } else {
                            $newPriceCop = $oldPriceCop * (1 - $value / 100);
                        }
                    } else {
                        if ($operation === 'increase') {
                            $newPriceCop = $oldPriceCop + $value;
                        } else {
                            $newPriceCop = $oldPriceCop - $value;
                        }
                    }
                    
                    $newPriceCop = max(0, $newPriceCop);
                    $newPriceCop = round($newPriceCop, 2);
                }

                $product->update([
                    'price_usd' => $newPriceUsd,
                    'price_cop' => $newPriceCop,
                ]);

                $updatedProducts[] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'old_price_usd' => $oldPriceUsd,
                    'new_price_usd' => $newPriceUsd,
                    'old_price_cop' => $oldPriceCop,
                    'new_price_cop' => $newPriceCop,
                ];
            } catch (\Exception $e) {
                $errors[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => count($updatedProducts) . ' products updated successfully',
            'data' => [
                'updated_products' => $updatedProducts,
                'errors' => $errors,
                'summary' => [
                    'total_requested' => count($validated['product_ids']),
                    'successfully_updated' => count($updatedProducts),
                    'failed' => count($errors),
                    'operation' => $operation,
                    'type' => $type,
                    'value' => $value,
                    'currency' => $currency,
                ]
            ]
        ]);
    }

    /**
     * Add image to product
     */
    public function addImage(Request $request, Product $product)
    {
        $validated = $request->validate([
            'url' => 'required|url',
            'is_primary' => 'boolean',
        ]);

        if ($validated['is_primary'] ?? false) {
            $product->images()->update(['is_primary' => false]);
        }

        $image = $product->images()->create($validated);

        return response()->json([
            'status' => 'success',
            'data' => $image
        ], 201);
    }

    /**
     * Remove image from product
     */
    public function removeImage(Product $product, ProductImage $image)
    {
        if ($image->product_id !== $product->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Image does not belong to this product'
            ], 404);
        }

        $image->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Image removed successfully'
        ]);
    }

    /**
     * Set primary image for product
     */
    public function setPrimaryImage(Product $product, ProductImage $image)
    {
        if ($image->product_id !== $product->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Image does not belong to this product'
            ], 404);
        }

        $product->images()->update(['is_primary' => false]);
        $image->update(['is_primary' => true]);

        return response()->json([
            'status' => 'success',
            'data' => $image
        ]);
    }

    /**
     * Add variant to product
     */
    public function addVariant(Request $request, Product $product)
    {
        $validated = $request->validate([
            'size_id' => 'required|uuid|exists:sizes,id',
        ]);

        $exists = $product->variants()->where('size_id', $validated['size_id'])->exists();
        
        if ($exists) {
            return response()->json([
                'status' => 'error',
                'message' => 'This size variant already exists for this product'
            ], 422);
        }

        $variant = $product->variants()->create($validated);

        return response()->json([
            'status' => 'success',
            'data' => $variant->load('size')
        ], 201);
    }

    /**
     * Update variant
     */
    public function updateVariant(Request $request, Product $product, ProductVariant $variant)
    {
        if ($variant->product_id !== $product->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Variant does not belong to this product'
            ], 404);
        }

        $validated = $request->validate([
            'size_id' => 'required|uuid|exists:sizes,id',
        ]);

        $exists = $product->variants()
            ->where('size_id', $validated['size_id'])
            ->where('id', '!=', $variant->id)
            ->exists();
        
        if ($exists) {
            return response()->json([
                'status' => 'error',
                'message' => 'This size variant already exists for this product'
            ], 422);
        }

        $variant->update($validated);

        return response()->json([
            'status' => 'success',
            'data' => $variant->load('size')
        ]);
    }

    /**
     * Remove variant from product
     */
    public function removeVariant(Product $product, ProductVariant $variant)
    {
        if ($variant->product_id !== $product->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Variant does not belong to this product'
            ], 404);
        }

        $variant->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Variant removed successfully'
        ]);
    }

    /**
     * Generate a unique slug from product name
     */
    protected function generateUniqueSlug(string $name, ?string $excludeId = null): string
    {
        // Convertir a minúsculas y reemplazar espacios y caracteres especiales
        $slug = strtolower($name);
        
        // Reemplazar caracteres especiales comunes del español
        $slug = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü'],
            ['a', 'e', 'i', 'o', 'u', 'n', 'u'],
            $slug
        );
        
        // Reemplazar cualquier caracter que no sea letra, número o espacio con guión
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        
        // Reemplazar múltiples espacios o guiones con un solo guión
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        
        // Eliminar guiones al inicio y final
        $slug = trim($slug, '-');
        
        // Verificar si el slug ya existe
        $originalSlug = $slug;
        $counter = 1;
        
        while (true) {
            $query = Product::where('slug', $slug);
            
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
            
            if (!$query->exists()) {
                break;
            }
            
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
}

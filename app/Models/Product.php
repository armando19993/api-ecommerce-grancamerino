<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['name', 'slug', 'category_id', 'team_id', 'price_usd', 'price_cop', 'description', 'is_active'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }

    public function getBasePriceAttribute()
    {
        return [
            'usd' => $this->price_usd,
            'cop' => $this->price_cop
        ];
    }

    public function getVariantsWithPricesAttribute()
    {
        return $this->variants->map(function ($variant) {
            return [
                'id' => $variant->id,
                'size' => $variant->size->name,
                'sku' => $variant->sku,
                'stock' => $variant->stock,
                'price_usd' => $this->price_usd + ($variant->additional_price_usd ?? 0),
                'price_cop' => $this->price_cop + ($variant->additional_price_cop ?? 0),
                'additional_price_usd' => $variant->additional_price_usd ?? 0,
                'additional_price_cop' => $variant->additional_price_cop ?? 0,
            ];
        });
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function specialCategories(): BelongsToMany
    {
        return $this->belongsToMany(SpecialCategory::class, 'product_special_category');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}

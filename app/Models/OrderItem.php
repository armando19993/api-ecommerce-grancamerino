<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    /** @use HasFactory<\Database\Factories\OrderItemFactory> */
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'product_name',
        'product_size',
        'unit_price',
        'quantity',
        'total_price',
        'customization_name',
        'customization_number'
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2'
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * Get the size through the product variant
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasOneThrough
     */
    public function size()
    {
        return $this->hasOneThrough(
            Size::class,
            ProductVariant::class,
            'id', // Foreign key on ProductVariant table
            'id', // Foreign key on Size table
            'product_variant_id', // Local key on OrderItem table
            'size_id' // Local key on ProductVariant table
        );
    }

    public function hasCustomization(): bool
    {
        return !empty($this->customization_name) || !empty($this->customization_number);
    }
}

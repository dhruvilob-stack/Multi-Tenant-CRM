<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Product extends BaseModel
{

    protected $fillable = [
        'manufacturer_id',
        'category_id',
        'sku',
        'name',
        'description',
        'base_price',
        'unit',
        'images',
        'available_for_distributor',
        'status',
        'slug',
        'price',
        'old_price',
        'cost',
        'barcode',
        'qty',
        'purchased_qty',
        'security_stock',
        'is_visible',
        'featured',
        'backorder',
        'requires_shipping',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'images' => 'array',
            'available_for_distributor' => 'boolean',
            'base_price' => 'decimal:2',
            'price' => 'decimal:2',
            'old_price' => 'decimal:2',
            'cost' => 'decimal:2',
            'qty' => 'integer',
            'purchased_qty' => 'decimal:3',
            'security_stock' => 'integer',
            'is_visible' => 'boolean',
            'featured' => 'boolean',
            'backorder' => 'boolean',
            'requires_shipping' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manufacturer_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function inventoryRecords(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function customerPurchases(): HasMany
    {
        return $this->hasMany(ProductCustomerPurchase::class);
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    protected function price(): Attribute
    {
        return Attribute::make(
            set: function (mixed $value): array {
                $numeric = is_numeric($value) ? (float) $value : 0.0;

                return [
                    'price' => $numeric,
                    'base_price' => $numeric,
                ];
            },
        );
    }

    protected function basePrice(): Attribute
    {
        return Attribute::make(
            set: function (mixed $value): array {
                $numeric = is_numeric($value) ? (float) $value : 0.0;

                return [
                    'base_price' => $numeric,
                    'price' => $this->attributes['price'] ?? $numeric,
                ];
            },
        );
    }
}

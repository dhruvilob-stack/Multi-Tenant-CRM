<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
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
    ];

    protected function casts(): array
    {
        return [
            'images' => 'array',
            'available_for_distributor' => 'boolean',
            'base_price' => 'decimal:2',
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
}

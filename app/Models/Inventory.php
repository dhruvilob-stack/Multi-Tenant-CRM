<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Inventory extends BaseModel
{
    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'owner_id',
        'owner_type',
        'quantity_available',
        'quantity_reserved',
        'warehouse_location',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity_available' => 'decimal:3',
            'quantity_reserved' => 'decimal:3',
            'updated_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }
}

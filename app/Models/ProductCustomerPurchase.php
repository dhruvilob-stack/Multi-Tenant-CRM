<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCustomerPurchase extends BaseModel
{
    protected $fillable = [
        'product_id',
        'consumer_id',
        'purchased_qty',
    ];

    protected function casts(): array
    {
        return [
            'purchased_qty' => 'decimal:3',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function consumer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'consumer_id');
    }
}


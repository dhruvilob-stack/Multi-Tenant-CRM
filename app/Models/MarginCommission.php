<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarginCommission extends BaseModel
{
    protected $fillable = [
        'product_id',
        'category_id',
        'from_role',
        'to_role',
        'commission_type',
        'commission_value',
    ];

    protected function casts(): array
    {
        return [
            'commission_value' => 'decimal:4',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}

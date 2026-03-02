<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationItem extends BaseModel
{
    protected $fillable = [
        'quotation_id',
        'product_id',
        'item_name',
        'qty',
        'selling_price',
        'discount_percent',
        'net_price',
        'total',
        'tax_rate',
        'tax_amount',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'decimal:3',
            'selling_price' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'net_price' => 'decimal:2',
            'total' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
        ];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

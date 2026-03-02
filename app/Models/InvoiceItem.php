<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvoiceItem extends BaseModel
{
    protected $fillable = [
        'invoice_id',
        'product_id',
        'item_name',
        'qty',
        'selling_price',
        'discount_percent',
        'net_price',
        'total',
        'tax_type',
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

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function commissionEntries(): HasMany
    {
        return $this->hasMany(CommissionLedger::class);
    }
}

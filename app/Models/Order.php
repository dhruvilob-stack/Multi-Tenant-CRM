<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends BaseModel
{
    protected $fillable = [
        'order_number',
        'consumer_id',
        'vendor_id',
        'invoice_id',
        'status',
        'currency',
        'payment_method',
        'payment_reference_number',
        'payment_status',
        'total_amount',
        'total_amount_billed',
        'notes',
        'billing_address',
        'shipping_address',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'total_amount_billed' => 'decimal:2',
            'billing_address' => 'array',
            'shipping_address' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function consumer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'consumer_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}

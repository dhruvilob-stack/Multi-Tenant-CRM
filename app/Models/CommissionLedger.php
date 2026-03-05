<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommissionLedger extends BaseModel
{
    protected $table = 'commission_ledger';

    protected $fillable = [
        'invoice_id',
        'invoice_item_id',
        'product_id',
        'from_user_id',
        'to_user_id',
        'from_role',
        'to_role',
        'commission_type',
        'commission_rate',
        'basis_amount',
        'commission_amount',
        'paid_amount',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:4',
            'basis_amount' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function invoiceItem(): BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function payoutItems(): HasMany
    {
        return $this->hasMany(CommissionPayoutItem::class, 'commission_ledger_id');
    }
}

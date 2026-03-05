<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionPayoutItem extends BaseModel
{
    protected $fillable = [
        'payout_id',
        'commission_ledger_id',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function payout(): BelongsTo
    {
        return $this->belongsTo(CommissionPayout::class, 'payout_id');
    }

    public function ledger(): BelongsTo
    {
        return $this->belongsTo(CommissionLedger::class, 'commission_ledger_id');
    }
}


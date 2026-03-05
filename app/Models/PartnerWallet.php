<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerWallet extends BaseModel
{
    protected $fillable = [
        'organization_id',
        'user_id',
        'role',
        'available_balance',
        'pending_balance',
        'total_earned',
        'total_paid',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'available_balance' => 'decimal:2',
            'pending_balance' => 'decimal:2',
            'total_earned' => 'decimal:2',
            'total_paid' => 'decimal:2',
            'last_synced_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}


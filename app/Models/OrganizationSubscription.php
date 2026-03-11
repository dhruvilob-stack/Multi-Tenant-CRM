<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationSubscription extends Model
{
    protected $connection = 'landlord';

    protected $fillable = [
        'organization_id',
        'subscribed_by',
        'plan_key',
        'plan_name',
        'currency',
        'billing_cycle',
        'plan_price',
        'tax_amount',
        'platform_fee',
        'total_amount',
        'status',
        'starts_at',
        'ends_at',
        'next_renew_at',
        'payment_method',
        'payment_reference',
        'billing_details',
        'plan_snapshot',
        'payment_meta',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'next_renew_at' => 'datetime',
            'billing_details' => 'array',
            'plan_snapshot' => 'array',
            'payment_meta' => 'array',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subscribed_by');
    }
}

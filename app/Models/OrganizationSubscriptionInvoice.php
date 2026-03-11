<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationSubscriptionInvoice extends Model
{
    protected $connection = 'landlord';

    protected $fillable = [
        'organization_id',
        'subscription_id',
        'invoice_number',
        'currency',
        'plan_price',
        'tax_amount',
        'platform_fee',
        'total_amount',
        'status',
        'issued_at',
        'payment_method',
        'payment_reference',
        'pdf_path',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(OrganizationSubscription::class, 'subscription_id');
    }
}

<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductChangeRequest extends BaseModel
{
    protected $fillable = [
        'product_id',
        'manufacturer_id',
        'organization_id',
        'requested_changes',
        'status',
        'response_notes',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manufacturer_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}

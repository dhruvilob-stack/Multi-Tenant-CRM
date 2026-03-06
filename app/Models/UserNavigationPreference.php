<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNavigationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'panel_id',
        'order_keys',
    ];

    protected function casts(): array
    {
        return [
            'order_keys' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

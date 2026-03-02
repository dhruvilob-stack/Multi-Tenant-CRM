<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardWidgetPreference extends Model
{
    protected $fillable = [
        'user_id',
        'panel_id',
        'page',
        'widgets',
    ];

    protected function casts(): array
    {
        return [
            'widgets' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}


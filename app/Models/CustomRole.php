<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomRole extends BaseModel
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'permissions',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}


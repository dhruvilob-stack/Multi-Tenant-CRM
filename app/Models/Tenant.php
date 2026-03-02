<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends BaseModel
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'domain',
        'data',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class);
    }
}

<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends BaseModel
{
    protected $connection = 'landlord';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'slug',
        'domain',
        'database',
        'db_host',
        'db_port',
        'db_username',
        'db_password',
        'status',
        'data',
    ];

    protected function casts(): array
    {
        return [
            'db_port' => 'integer',
            'data' => 'array',
        ];
    }

    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class);
    }
}

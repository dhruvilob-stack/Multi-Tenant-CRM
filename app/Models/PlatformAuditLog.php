<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformAuditLog extends Model
{
    protected $connection = 'landlord';

    protected $fillable = [
        'tenant_id',
        'tenant_slug',
        'event',
        'auditable_type',
        'auditable_id',
        'actor_id',
        'actor_email',
        'actor_role',
        'before',
        'after',
        'ip_address',
        'user_agent',
        'route_name',
        'url',
        'method',
    ];

    protected function casts(): array
    {
        return [
            'before' => 'array',
            'after' => 'array',
        ];
    }
}


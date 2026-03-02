<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    protected $fillable = [
        'auditable_type',
        'auditable_id',
        'event',
        'performed_by',
        'performed_role',
        'before',
        'after',
        'ip_address',
    ];

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}

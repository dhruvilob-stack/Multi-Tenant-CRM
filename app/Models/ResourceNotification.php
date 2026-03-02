<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class ResourceNotification extends Model
{
    protected $fillable = [
        'notificationable_type',
        'notificationable_id',
        'recipient_id',
        'recipient_role',
        'action',
        'message',
        'redirect_url',
        'read',
    ];

    protected $casts = [
        'read' => 'boolean',
    ];

    public function notificationable(): MorphTo
    {
        return $this->morphTo();
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }
}

<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invitation extends BaseModel
{
    protected $fillable = [
        'inviter_id',
        'invitee_email',
        'role',
        'token',
        'token_hash',
        'organization_id',
        'expires_at',
        'accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inviter_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function isExpired(): bool
    {
        return now()->greaterThan($this->expires_at);
    }

    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }
}

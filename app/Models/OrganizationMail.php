<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrganizationMail extends BaseModel
{
    protected $fillable = [
        'organization_id',
        'sender_id',
        'sender_email',
        'subject',
        'body',
        'template_key',
        'meta',
        'sent_at',
        'deleted_by_sender_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'sent_at' => 'datetime',
            'deleted_by_sender_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(OrganizationMailRecipient::class, 'mail_id');
    }
}

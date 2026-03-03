<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationMailRecipient extends BaseModel
{
    protected $fillable = [
        'mail_id',
        'recipient_id',
        'recipient_email',
        'recipient_type',
        'read_at',
        'deleted_at',
        'featured',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'deleted_at' => 'datetime',
            'featured' => 'boolean',
        ];
    }

    public function mail(): BelongsTo
    {
        return $this->belongsTo(OrganizationMail::class, 'mail_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }
}

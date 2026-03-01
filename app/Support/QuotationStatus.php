<?php

namespace App\Support;

final class QuotationStatus
{
    public const DRAFT = 'draft';
    public const SENT = 'sent';
    public const NEGOTIATED = 'negotiated';
    public const CONFIRMED = 'confirmed';
    public const REJECTED = 'rejected';
    public const CONVERTED = 'converted';
}

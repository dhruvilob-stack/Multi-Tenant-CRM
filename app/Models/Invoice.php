<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends BaseModel
{
    protected $fillable = [
        'invoice_number',
        'quotation_id',
        'order_id',
        'subject',
        'customer_no',
        'contact_name',
        'invoice_date',
        'due_date',
        'purchase_order',
        'excise_duty',
        'sales_commission',
        'organisation_name',
        'status',
        'assigned_to',
        'opportunity_name',
        'billing_address',
        'shipping_address',
        'terms_conditions',
        'description',
        'currency',
        'tax_region',
        'tax_mode',
        'overall_discount_type',
        'overall_discount_value',
        'shipping_handling',
        'pre_tax_total',
        'group_tax_vat',
        'group_tax_sales',
        'group_tax_service',
        'tax_amount',
        'tax_on_charges',
        'deducted_taxes',
        'adjustment_type',
        'adjustment_amount',
        'grand_total',
        'received_amount',
        'balance',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'billing_address' => 'array',
            'shipping_address' => 'array',
            'excise_duty' => 'decimal:2',
            'sales_commission' => 'decimal:2',
            'overall_discount_value' => 'decimal:2',
            'shipping_handling' => 'decimal:2',
            'pre_tax_total' => 'decimal:2',
            'group_tax_vat' => 'decimal:2',
            'group_tax_sales' => 'decimal:2',
            'group_tax_service' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'tax_on_charges' => 'decimal:2',
            'deducted_taxes' => 'decimal:2',
            'adjustment_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'received_amount' => 'decimal:2',
            'balance' => 'decimal:2',
        ];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function commissionEntries(): HasMany
    {
        return $this->hasMany(CommissionLedger::class);
    }
}

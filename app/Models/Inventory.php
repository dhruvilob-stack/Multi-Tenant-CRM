<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class Inventory extends BaseModel
{
    public $timestamps = true;

    protected $fillable = [
        'product_id',
        'sku',
        'barcode',
        'owner_id',
        'owner_type',
        'quantity_available',
        'quantity_reserved',
        'security_stock',
        'unit_price',
        'discount_percent',
        'warehouse_location',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity_available' => 'decimal:3',
            'quantity_reserved' => 'decimal:3',
            'security_stock' => 'integer',
            'unit_price' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'updated_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    protected static function booted(): void
    {
        parent::booted();

        static::saved(function (Inventory $inventory): void {
            $product = $inventory->product;

            if (! $product && filled($inventory->sku)) {
                $matchedProducts = Product::query()
                    ->where('sku', $inventory->sku)
                    ->limit(2)
                    ->get(['id', 'name', 'slug', 'sku', 'barcode', 'qty', 'security_stock', 'base_price', 'old_price', 'price']);

                if ($matchedProducts->count() === 1) {
                    /** @var Product $product */
                    $product = $matchedProducts->first();

                    $inventory->forceFill([
                        'product_id' => $product->id,
                    ])->saveQuietly();
                }
            }

            if (! $product) {
                return;
            }

            $basePrice = (float) ($inventory->unit_price ?? $product->base_price ?? 0);
            $discount = max(0.0, min(100.0, (float) ($inventory->discount_percent ?? 0)));
            $finalPrice = round($basePrice * (1 - ($discount / 100)), 2);

            $product->forceFill([
                'sku' => (string) ($inventory->sku ?: $product->sku),
                'barcode' => $inventory->barcode ?: $product->barcode,
                'qty' => (int) round((float) ($inventory->quantity_available ?? 0)),
                'security_stock' => (int) ($inventory->security_stock ?? 0),
                'base_price' => $basePrice,
                'old_price' => $basePrice,
                'price' => $finalPrice,
                'slug' => filled($product->slug) ? $product->slug : Str::slug((string) $product->name),
            ])->saveQuietly();
        });
    }

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class OrderItem extends BaseModel
{
    protected $fillable = [
        'order_id',
        'product_id',
        'item_name',
        'qty',
        'unit_price',
        'discount_percent',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected static function booted(): void
    {
        $sync = function (OrderItem $item): void {
            if (! $item->order_id) {
                return;
            }

            $total = (float) static::query()
                ->where('order_id', $item->order_id)
                ->sum('line_total');

            DB::table('orders')
                ->where('id', $item->order_id)
                ->update([
                    'total_amount' => round($total, 2),
                    'total_amount_billed' => round($total, 2),
                    'updated_at' => now(),
                ]);
        };

        static::created($sync);
        static::updated($sync);
        static::deleted($sync);
    }
}

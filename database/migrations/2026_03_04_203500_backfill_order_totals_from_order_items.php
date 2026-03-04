<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            UPDATE orders o
            LEFT JOIN (
                SELECT order_id, COALESCE(SUM(line_total), 0) AS total
                FROM order_items
                GROUP BY order_id
            ) i ON i.order_id = o.id
            SET
                o.total_amount = COALESCE(i.total, 0),
                o.total_amount_billed = COALESCE(i.total, 0)
        ');
    }

    public function down(): void
    {
        // No-op backfill rollback.
    }
};


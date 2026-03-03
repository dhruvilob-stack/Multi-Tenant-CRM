<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventories', function (Blueprint $table): void {
            if (! Schema::hasColumn('inventories', 'sku')) {
                $table->string('sku')->nullable()->after('product_id');
            }

            if (! Schema::hasColumn('inventories', 'barcode')) {
                $table->string('barcode')->nullable()->after('sku');
            }

            if (! Schema::hasColumn('inventories', 'security_stock')) {
                $table->unsignedBigInteger('security_stock')->default(0)->after('quantity_available');
            }

            if (! Schema::hasColumn('inventories', 'unit_price')) {
                $table->decimal('unit_price', 15, 2)->default(0)->after('security_stock');
            }

            if (! Schema::hasColumn('inventories', 'discount_percent')) {
                $table->decimal('discount_percent', 5, 2)->default(0)->after('unit_price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventories', function (Blueprint $table): void {
            $columns = ['discount_percent', 'unit_price', 'security_stock', 'barcode', 'sku'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('inventories', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

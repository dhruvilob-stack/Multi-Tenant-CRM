<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders') || Schema::hasColumn('orders', 'quotation_id')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('quotation_id')
                ->nullable()
                ->after('order_number')
                ->constrained('quotations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders') || ! Schema::hasColumn('orders', 'quotation_id')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('quotation_id');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('inventories') || ! Schema::hasColumn('inventories', 'product_id')) {
            return;
        }

        Schema::table('inventories', function (Blueprint $table): void {
            $table->dropForeign(['product_id']);
        });

        Schema::table('inventories', function (Blueprint $table): void {
            $table->foreignId('product_id')->nullable()->change();
        });

        Schema::table('inventories', function (Blueprint $table): void {
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('inventories') || ! Schema::hasColumn('inventories', 'product_id')) {
            return;
        }

        Schema::table('inventories', function (Blueprint $table): void {
            $table->dropForeign(['product_id']);
        });

        Schema::table('inventories', function (Blueprint $table): void {
            $table->foreignId('product_id')->nullable(false)->change();
        });

        Schema::table('inventories', function (Blueprint $table): void {
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });
    }
};

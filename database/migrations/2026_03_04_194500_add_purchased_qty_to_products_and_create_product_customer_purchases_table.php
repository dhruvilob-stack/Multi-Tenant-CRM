<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            if (! Schema::hasColumn('products', 'purchased_qty')) {
                $table->decimal('purchased_qty', 15, 3)->default(0)->after('qty');
            }
        });

        if (! Schema::hasTable('product_customer_purchases')) {
            Schema::create('product_customer_purchases', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('consumer_id')->constrained('users')->cascadeOnDelete();
                $table->decimal('purchased_qty', 15, 3)->default(0);
                $table->timestamps();

                $table->unique(['product_id', 'consumer_id'], 'product_customer_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_customer_purchases');

        Schema::table('products', function (Blueprint $table): void {
            if (Schema::hasColumn('products', 'purchased_qty')) {
                $table->dropColumn('purchased_qty');
            }
        });
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            if (! Schema::hasColumn('products', 'slug')) {
                $table->string('slug')->nullable()->unique()->after('name');
            }

            if (! Schema::hasColumn('products', 'price')) {
                $table->decimal('price', 15, 2)->default(0)->after('base_price');
            }

            if (! Schema::hasColumn('products', 'old_price')) {
                $table->decimal('old_price', 15, 2)->default(0)->after('price');
            }

            if (! Schema::hasColumn('products', 'cost')) {
                $table->decimal('cost', 15, 2)->default(0)->after('old_price');
            }

            if (! Schema::hasColumn('products', 'barcode')) {
                $table->string('barcode')->nullable()->unique()->after('sku');
            }

            if (! Schema::hasColumn('products', 'qty')) {
                $table->unsignedBigInteger('qty')->default(0)->after('unit');
            }

            if (! Schema::hasColumn('products', 'security_stock')) {
                $table->unsignedBigInteger('security_stock')->default(0)->after('qty');
            }

            if (! Schema::hasColumn('products', 'is_visible')) {
                $table->boolean('is_visible')->default(true)->after('status');
            }

            if (! Schema::hasColumn('products', 'featured')) {
                $table->boolean('featured')->default(false)->after('is_visible');
            }

            if (! Schema::hasColumn('products', 'backorder')) {
                $table->boolean('backorder')->default(false)->after('featured');
            }

            if (! Schema::hasColumn('products', 'requires_shipping')) {
                $table->boolean('requires_shipping')->default(true)->after('backorder');
            }

            if (! Schema::hasColumn('products', 'published_at')) {
                $table->date('published_at')->nullable()->after('requires_shipping');
            }
        });

        if (! Schema::hasTable('comments')) {
            Schema::create('comments', function (Blueprint $table): void {
                $table->id();
                $table->nullableMorphs('commentable');
                $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('title')->nullable();
                $table->text('content');
                $table->boolean('is_visible')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');

        Schema::table('products', function (Blueprint $table): void {
            $drop = static function (string $column) use ($table): void {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            };

            $drop('published_at');
            $drop('requires_shipping');
            $drop('backorder');
            $drop('featured');
            $drop('is_visible');
            $drop('security_stock');
            $drop('qty');
            $drop('barcode');
            $drop('cost');
            $drop('old_price');
            $drop('price');
            $drop('slug');
        });
    }
};

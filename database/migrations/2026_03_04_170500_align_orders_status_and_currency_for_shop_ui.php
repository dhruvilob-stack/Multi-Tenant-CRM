<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('orders', 'currency')) {
                $table->string('currency', 10)->default('EUR')->after('status');
            }
        });

        DB::statement("UPDATE orders SET status = 'new' WHERE status = 'pending'");
        DB::statement("UPDATE orders SET status = 'processing' WHERE status = 'confirmed'");

        DB::statement("ALTER TABLE orders MODIFY status ENUM('new','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'new'");
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        DB::statement("UPDATE orders SET status = 'pending' WHERE status = 'new'");
        DB::statement("UPDATE orders SET status = 'confirmed' WHERE status = 'processing'");
        DB::statement("ALTER TABLE orders MODIFY status ENUM('pending','confirmed','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending'");

        Schema::table('orders', function (Blueprint $table): void {
            if (Schema::hasColumn('orders', 'currency')) {
                $table->dropColumn('currency');
            }
        });
    }
};

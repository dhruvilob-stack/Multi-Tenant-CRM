<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('orders', 'payment_reference_number')) {
                $table->string('payment_reference_number', 120)->nullable()->after('payment_method');
            }

            if (! Schema::hasColumn('orders', 'total_amount_billed')) {
                $table->decimal('total_amount_billed', 15, 2)->default(0)->after('total_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            if (Schema::hasColumn('orders', 'total_amount_billed')) {
                $table->dropColumn('total_amount_billed');
            }

            if (Schema::hasColumn('orders', 'payment_reference_number')) {
                $table->dropColumn('payment_reference_number');
            }
        });
    }
};


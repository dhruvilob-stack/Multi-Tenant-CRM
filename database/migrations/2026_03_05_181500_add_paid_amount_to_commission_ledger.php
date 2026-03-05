<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commission_ledger', function (Blueprint $table): void {
            if (! Schema::hasColumn('commission_ledger', 'paid_amount')) {
                $table->decimal('paid_amount', 15, 2)->default(0)->after('commission_amount');
            }
        });

        DB::table('commission_ledger')
            ->where('status', 'paid')
            ->update([
                'paid_amount' => DB::raw('commission_amount'),
            ]);
    }

    public function down(): void
    {
        Schema::table('commission_ledger', function (Blueprint $table): void {
            if (Schema::hasColumn('commission_ledger', 'paid_amount')) {
                $table->dropColumn('paid_amount');
            }
        });
    }
};


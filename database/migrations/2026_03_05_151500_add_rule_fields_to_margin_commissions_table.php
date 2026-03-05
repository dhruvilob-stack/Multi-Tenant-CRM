<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('margin_commissions', function (Blueprint $table): void {
            if (! Schema::hasColumn('margin_commissions', 'organization_id')) {
                $table->foreignId('organization_id')->nullable()->after('id')->constrained('organizations')->nullOnDelete();
            }

            if (! Schema::hasColumn('margin_commissions', 'rule_type')) {
                $table->string('rule_type', 20)->default('global')->after('category_id');
            }

            if (! Schema::hasColumn('margin_commissions', 'priority')) {
                $table->unsignedInteger('priority')->default(100)->after('rule_type');
            }

            if (! Schema::hasColumn('margin_commissions', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('priority');
            }
        });

        DB::table('margin_commissions')
            ->whereNotNull('product_id')
            ->update(['rule_type' => 'product']);

        DB::table('margin_commissions')
            ->whereNull('product_id')
            ->whereNotNull('category_id')
            ->update(['rule_type' => 'category']);

        DB::table('margin_commissions')
            ->whereNull('product_id')
            ->whereNull('category_id')
            ->update(['rule_type' => 'global']);
    }

    public function down(): void
    {
        Schema::table('margin_commissions', function (Blueprint $table): void {
            if (Schema::hasColumn('margin_commissions', 'organization_id')) {
                $table->dropConstrainedForeignId('organization_id');
            }

            if (Schema::hasColumn('margin_commissions', 'is_active')) {
                $table->dropColumn('is_active');
            }

            if (Schema::hasColumn('margin_commissions', 'priority')) {
                $table->dropColumn('priority');
            }

            if (Schema::hasColumn('margin_commissions', 'rule_type')) {
                $table->dropColumn('rule_type');
            }
        });
    }
};


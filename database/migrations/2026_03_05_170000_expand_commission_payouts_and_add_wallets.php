<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commission_payouts', function (Blueprint $table): void {
            if (! Schema::hasColumn('commission_payouts', 'organization_id')) {
                $table->foreignId('organization_id')->nullable()->after('id')->constrained('organizations')->nullOnDelete();
            }

            if (! Schema::hasColumn('commission_payouts', 'payout_number')) {
                $table->string('payout_number', 40)->nullable()->after('organization_id');
            }

            if (! Schema::hasColumn('commission_payouts', 'status')) {
                $table->string('status', 20)->default('processing')->after('amount');
            }

            if (! Schema::hasColumn('commission_payouts', 'payment_method')) {
                $table->string('payment_method', 40)->nullable()->after('status');
            }

            if (! Schema::hasColumn('commission_payouts', 'currency')) {
                $table->string('currency', 10)->default('USD')->after('payment_method');
            }

            if (! Schema::hasColumn('commission_payouts', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('currency')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('commission_payouts', 'processed_at')) {
                $table->timestamp('processed_at')->nullable()->after('paid_at');
            }
        });

        Schema::table('commission_payouts', function (Blueprint $table): void {
            if (Schema::hasColumn('commission_payouts', 'payout_number')) {
                $table->unique('payout_number');
            }
        });

        if (! Schema::hasTable('commission_payout_items')) {
            Schema::create('commission_payout_items', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('payout_id')->constrained('commission_payouts')->cascadeOnDelete();
                $table->foreignId('commission_ledger_id')->constrained('commission_ledger')->cascadeOnDelete();
                $table->decimal('amount', 15, 2)->default(0);
                $table->timestamps();

                $table->unique(['payout_id', 'commission_ledger_id'], 'commission_payout_items_unique');
                $table->index(['commission_ledger_id'], 'commission_payout_items_ledger_idx');
            });
        }

        if (! Schema::hasTable('partner_wallets')) {
            Schema::create('partner_wallets', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('role', 50);
                $table->decimal('available_balance', 15, 2)->default(0);
                $table->decimal('pending_balance', 15, 2)->default(0);
                $table->decimal('total_earned', 15, 2)->default(0);
                $table->decimal('total_paid', 15, 2)->default(0);
                $table->timestamp('last_synced_at')->nullable();
                $table->timestamps();

                $table->unique(['organization_id', 'user_id'], 'partner_wallets_org_user_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('partner_wallets')) {
            Schema::dropIfExists('partner_wallets');
        }

        if (Schema::hasTable('commission_payout_items')) {
            Schema::dropIfExists('commission_payout_items');
        }

        Schema::table('commission_payouts', function (Blueprint $table): void {
            if (Schema::hasColumn('commission_payouts', 'processed_at')) {
                $table->dropColumn('processed_at');
            }
            if (Schema::hasColumn('commission_payouts', 'created_by')) {
                $table->dropConstrainedForeignId('created_by');
            }
            if (Schema::hasColumn('commission_payouts', 'currency')) {
                $table->dropColumn('currency');
            }
            if (Schema::hasColumn('commission_payouts', 'payment_method')) {
                $table->dropColumn('payment_method');
            }
            if (Schema::hasColumn('commission_payouts', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('commission_payouts', 'payout_number')) {
                $table->dropUnique(['payout_number']);
                $table->dropColumn('payout_number');
            }
            if (Schema::hasColumn('commission_payouts', 'organization_id')) {
                $table->dropConstrainedForeignId('organization_id');
            }
        });
    }
};


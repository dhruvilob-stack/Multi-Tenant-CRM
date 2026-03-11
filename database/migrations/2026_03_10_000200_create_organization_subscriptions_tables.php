<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('subscribed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('plan_key', 80);
            $table->string('plan_name', 120)->nullable();
            $table->string('currency', 10)->default('USD');
            $table->string('billing_cycle', 20)->default('month');
            $table->decimal('plan_price', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('platform_fee', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('status', 30)->default('pending');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('next_renew_at')->nullable();
            $table->string('payment_method', 40)->nullable();
            $table->string('payment_reference', 120)->nullable();
            $table->json('billing_details')->nullable();
            $table->json('plan_snapshot')->nullable();
            $table->json('payment_meta')->nullable();
            $table->timestamps();
        });

        Schema::create('organization_subscription_invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('subscription_id')->constrained('organization_subscriptions')->cascadeOnDelete();
            $table->string('invoice_number', 60)->unique();
            $table->string('currency', 10)->default('USD');
            $table->decimal('plan_price', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('platform_fee', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('status', 30)->default('paid');
            $table->timestamp('issued_at')->nullable();
            $table->string('payment_method', 40)->nullable();
            $table->string('payment_reference', 120)->nullable();
            $table->string('pdf_path', 500)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_subscription_invoices');
        Schema::dropIfExists('organization_subscriptions');
    }
};

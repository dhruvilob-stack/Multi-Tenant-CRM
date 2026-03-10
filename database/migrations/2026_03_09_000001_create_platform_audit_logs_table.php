<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::connection('landlord')->hasTable('platform_audit_logs')) {
            Schema::connection('landlord')->create('platform_audit_logs', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->string('tenant_id')->nullable()->index();
                $table->string('tenant_slug')->nullable()->index();

                $table->string('event')->index();
                $table->string('auditable_type')->nullable()->index();
                $table->string('auditable_id')->nullable()->index();

                $table->unsignedBigInteger('actor_id')->nullable()->index();
                $table->string('actor_email')->nullable()->index();
                $table->string('actor_role')->nullable()->index();

                $table->json('before')->nullable();
                $table->json('after')->nullable();

                $table->string('ip_address')->nullable();
                $table->text('user_agent')->nullable();
                $table->string('route_name')->nullable()->index();
                $table->string('url')->nullable();
                $table->string('method', 16)->nullable();

                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::connection('landlord')->dropIfExists('platform_audit_logs');
    }
};

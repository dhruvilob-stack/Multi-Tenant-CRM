<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            if (! Schema::hasColumn('organizations', 'tenant_id')) {
                $table->char('tenant_id', 36)->nullable()->index()->after('id');
            }

            if (! Schema::hasColumn('organizations', 'email')) {
                $table->string('email')->nullable()->after('name');
            }

            if (! Schema::hasColumn('organizations', 'phone')) {
                $table->string('phone', 50)->nullable()->after('email');
            }

            if (! Schema::hasColumn('organizations', 'address')) {
                $table->text('address')->nullable()->after('phone');
            }

            if (! Schema::hasColumn('organizations', 'logo')) {
                $table->string('logo', 500)->nullable()->after('address');
            }

            if (! Schema::hasColumn('organizations', 'status')) {
                $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->after('logo');
            }

            if (! Schema::hasColumn('organizations', 'settings')) {
                $table->json('settings')->nullable()->after('status');
            }
        });

        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'organization_id')) {
                $table->foreignId('organization_id')->nullable()->after('id')->constrained('organizations')->nullOnDelete();
            }

            if (! Schema::hasColumn('users', 'parent_id')) {
                $table->foreignId('parent_id')->nullable()->after('organization_id')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['super_admin', 'org_admin', 'manufacturer', 'distributor', 'vendor', 'consumer'])->default('consumer')->after('password');
            }

            if (! Schema::hasColumn('users', 'invitation_token')) {
                $table->string('invitation_token')->nullable()->after('role');
            }

            if (! Schema::hasColumn('users', 'invitation_accepted_at')) {
                $table->timestamp('invitation_accepted_at')->nullable()->after('invitation_token');
            }

            if (! Schema::hasColumn('users', 'status')) {
                $table->enum('status', ['pending', 'active', 'inactive'])->default('pending')->after('invitation_accepted_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('parent_id');
            $table->dropConstrainedForeignId('organization_id');
            $table->dropColumn(['role', 'invitation_token', 'invitation_accepted_at', 'status']);
        });

        Schema::table('organizations', function (Blueprint $table): void {
            $table->dropColumn(['tenant_id', 'email', 'phone', 'address', 'logo', 'status', 'settings']);
        });
    }
};

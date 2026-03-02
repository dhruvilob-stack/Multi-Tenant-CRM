<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'custom_role_id')) {
                $table->foreignId('custom_role_id')
                    ->nullable()
                    ->after('role')
                    ->constrained('custom_roles')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'custom_role_id')) {
                $table->dropConstrainedForeignId('custom_role_id');
            }
        });
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            if (! Schema::hasColumn('tenants', 'slug')) {
                $table->string('slug')->nullable()->unique()->after('name');
            }

            if (! Schema::hasColumn('tenants', 'database')) {
                $table->string('database')->nullable()->after('domain');
            }

            if (! Schema::hasColumn('tenants', 'db_host')) {
                $table->string('db_host', 150)->nullable()->after('database');
            }

            if (! Schema::hasColumn('tenants', 'db_port')) {
                $table->string('db_port', 10)->nullable()->after('db_host');
            }

            if (! Schema::hasColumn('tenants', 'db_username')) {
                $table->string('db_username', 150)->nullable()->after('db_port');
            }

            if (! Schema::hasColumn('tenants', 'db_password')) {
                $table->string('db_password', 255)->nullable()->after('db_username');
            }

            if (! Schema::hasColumn('tenants', 'status')) {
                $table->string('status', 30)->default('active')->after('db_password');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            if (Schema::hasColumn('tenants', 'slug')) {
                $table->dropUnique('tenants_slug_unique');
                $table->dropColumn('slug');
            }

            $columns = ['database', 'db_host', 'db_port', 'db_username', 'db_password', 'status'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('tenants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

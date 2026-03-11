<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Prepare the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // in our normal environment the "landlord" connection is MySQL and
        // tests are configured to use an in-memory sqlite database for the
        // default connection. the tenant resolver and various bits of code
        // also look at the landlord connection which means we need to mirror
        // that behaviour during tests. override the landlord connection so it
        // also uses sqlite in memory, then run the migrations so the schema
        // exists.
        config(['database.connections.landlord' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);

        // purge any existing connection so the new config takes effect when
        // the database service is resolved later. we will create a couple of
        // minimal tables manually rather than running the full migration
        // suite, which is written for MySQL and hits information_schema.
        config(['database.connections.tenant' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);

        \DB::purge('landlord');
        \DB::reconnect('landlord');

        \DB::purge('tenant');
        \DB::reconnect('tenant');

        // create a very small tenants table in the landlord database that
        // our tests will use to resolve tenants by slug/ID. also add a dummy
        // `users` table because various model events (audit notifications,
        // etc.) assume it exists even though we don't use it in this test.
        \Schema::connection('landlord')->create('tenants', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->string('slug')->nullable();
            // the platform stores a separate database name for each tenant;
            // we don't actually create those databases in tests, but the
            // attribute must exist lest the manager attempts to update it.
            $table->string('database')->nullable();
            $table->timestamps();
        });

        \Schema::connection('landlord')->create('users', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('role')->nullable();
            $table->timestamps();
        });

        // the Organization model uses the landlord connection; some tests need a
        // lightweight table so we can insert rows without running the full
        // migration suite.
        \Schema::connection('landlord')->create('organizations', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->timestamps();
        });

        // also prepare the tenant-database schema for users and
        // organizations so that login attempts don't blow up.
        \Schema::connection(config('tenancy.tenant_connection', 'tenant'))->create('users', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->string('role');
            $table->timestamps();
        });

        \Schema::connection(config('tenancy.tenant_connection', 'tenant'))->create('organizations', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('slug')->nullable();
            $table->timestamps();
        });
    }
}

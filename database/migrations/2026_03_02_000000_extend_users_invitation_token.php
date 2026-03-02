<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'invitation_token')) {
            return;
        }

        DB::statement('ALTER TABLE users MODIFY invitation_token TEXT NULL');
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'invitation_token')) {
            return;
        }

        DB::statement('ALTER TABLE users MODIFY invitation_token VARCHAR(255) NULL');
    }
};

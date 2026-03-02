<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE `audit_logs` MODIFY `auditable_id` VARCHAR(64) NOT NULL');
        DB::statement('ALTER TABLE `resource_notifications` MODIFY `notificationable_id` VARCHAR(64) NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `audit_logs` MODIFY `auditable_id` BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE `resource_notifications` MODIFY `notificationable_id` BIGINT UNSIGNED NOT NULL');
    }
};


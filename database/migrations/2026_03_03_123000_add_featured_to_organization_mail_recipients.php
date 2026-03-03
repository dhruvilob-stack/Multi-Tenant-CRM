<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organization_mail_recipients', function (Blueprint $table): void {
            if (! Schema::hasColumn('organization_mail_recipients', 'featured')) {
                $table->boolean('featured')->default(false)->after('deleted_at');
                $table->index(['recipient_id', 'featured'], 'org_mail_rcpt_featured_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('organization_mail_recipients', function (Blueprint $table): void {
            if (Schema::hasColumn('organization_mail_recipients', 'featured')) {
                $table->dropIndex('org_mail_rcpt_featured_idx');
                $table->dropColumn('featured');
            }
        });
    }
};


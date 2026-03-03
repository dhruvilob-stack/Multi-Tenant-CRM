<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('organization_mails')) {
            Schema::create('organization_mails', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
                $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('sender_email');
                $table->string('subject');
                $table->longText('body');
                $table->string('template_key')->nullable();
                $table->json('meta')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('deleted_by_sender_at')->nullable();
                $table->timestamps();

                $table->index(['organization_id', 'sent_at'], 'org_mail_org_sent_idx');
            });
        }

        if (! Schema::hasTable('organization_mail_recipients')) {
            Schema::create('organization_mail_recipients', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('mail_id')->constrained('organization_mails')->cascadeOnDelete();
                $table->foreignId('recipient_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('recipient_email');
                $table->enum('recipient_type', ['to', 'cc', 'bcc']);
                $table->timestamp('read_at')->nullable();
                $table->timestamp('deleted_at')->nullable();
                $table->timestamps();

                $table->index(['recipient_id', 'deleted_at', 'read_at'], 'org_mail_rcpt_state_idx');
                $table->index(['recipient_email', 'recipient_type'], 'org_mail_rcpt_email_type_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_mail_recipients');
        Schema::dropIfExists('organization_mails');
    }
};

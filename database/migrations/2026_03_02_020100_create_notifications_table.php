<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_notifications', function (Blueprint $table): void {
            $table->id();
            $table->string('notificationable_type');
            $table->unsignedBigInteger('notificationable_id');
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->string('recipient_role')->nullable();
            $table->string('action');
            $table->text('message');
            $table->string('redirect_url')->nullable();
            $table->boolean('read')->default(false);
            $table->timestamps();
            $table->index(['notificationable_type', 'notificationable_id'], 'res_notifs_notificationable_idx');
            $table->index(['recipient_id', 'read'], 'res_notifs_recipient_read_idx');
            $table->index(['recipient_id', 'created_at'], 'res_notifs_recipient_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_notifications');
    }
};

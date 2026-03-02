<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_change_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('manufacturer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->text('requested_changes');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('response_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_change_requests');
    }
};

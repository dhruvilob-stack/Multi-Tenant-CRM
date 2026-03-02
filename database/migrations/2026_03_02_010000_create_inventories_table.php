<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->string('owner_type')->nullable();
            $table->decimal('quantity_available', 15, 3)->default(0);
            $table->decimal('quantity_reserved', 15, 3)->default(0);
            $table->string('warehouse_location')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};

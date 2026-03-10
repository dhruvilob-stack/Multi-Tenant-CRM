<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::connection('landlord')->hasTable('platform_settings')) {
            Schema::connection('landlord')->create('platform_settings', function (Blueprint $table): void {
                $table->id();
                $table->json('settings')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::connection('landlord')->dropIfExists('platform_settings');
    }
};

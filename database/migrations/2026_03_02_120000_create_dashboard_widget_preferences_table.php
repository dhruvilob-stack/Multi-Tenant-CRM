<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_widget_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('panel_id', 64);
            $table->string('page');
            $table->json('widgets')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'panel_id', 'page'], 'dashboard_widget_preferences_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_widget_preferences');
    }
};


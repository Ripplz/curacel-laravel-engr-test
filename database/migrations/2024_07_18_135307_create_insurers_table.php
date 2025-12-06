<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->decimal('base_cost', 10, 2)->default(100);
            $table->decimal('time_cost_min', 5, 4)->default(0.2);
            $table->decimal('time_cost_max', 5, 4)->default(0.5);
            $table->json('specialty_efficiency')->nullable();
            $table->decimal('priority_cost_multiplier', 5, 4)->default(1.0);
            $table->decimal('value_cost_multiplier', 5, 4)->default(0.01);
            $table->integer('daily_capacity')->default(100);
            $table->integer('min_batch_size')->default(1);
            $table->integer('max_batch_size')->default(50);
            $table->enum('date_preference', ['encounter', 'submission'])->default('encounter');
            $table->string('email')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurers');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->string('provider_name');
            $table->date('date');
            $table->foreignId('insurer_id')->constrained();
            $table->decimal('total_cost', 10, 2)->default(0);
            $table->enum('status', ['pending', 'processed'])->default('pending');
            $table->timestamps();
            $table->unique(['provider_name', 'date', 'insurer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};

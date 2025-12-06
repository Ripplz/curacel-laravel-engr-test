<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insurer_id')->constrained();
            $table->string('provider_name');
            $table->date('encounter_date');
            $table->date('submission_date')->default(DB::raw('CURRENT_DATE'));
            $table->string('specialty');
            $table->integer('priority')->default(1);
            $table->decimal('total_value', 10, 2);
            $table->foreignId('batch_id')->nullable()->constrained();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claims');
    }
};

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
        Schema::create('inspection_measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained('inspections');

            $table->float('voltage_r')->nullable();
            $table->float('voltage_s')->nullable();
            $table->float('voltage_t')->nullable();

            $table->float('current_r')->nullable();
            $table->float('current_s')->nullable();
            $table->float('current_t')->nullable();

            $table->float('power_factor')->nullable();
            $table->float('deviasi')->nullable();
            $table->float('faktor_kali')->nullable();

            $table->timestamps();

            $table->index('inspection_id');
            $table->unique('inspection_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inspection_measurements');
    }
};

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
        Schema::create('meter_readings', function (Blueprint $table) {

            $table->id();

            $table->foreignId('meter_id')->constrained('meters');

            $table->timestamp('reading_time');

            $table->double('voltage_r')->nullable();
            $table->double('voltage_s')->nullable();
            $table->double('voltage_t')->nullable();

            $table->double('current_r')->nullable();
            $table->double('current_s')->nullable();
            $table->double('current_t')->nullable();

            $table->double('import_kwh')->nullable();
            $table->double('export_kwh')->nullable();

            $table->double('power_factor')->nullable();

            $table->double('apparent_power')->nullable();

            $table->index(['meter_id', 'reading_time']);
            $table->unique(['meter_id', 'reading_time']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meter_readings');
    }
};

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

            $table->float('voltage_r')->nullable();
            $table->float('voltage_s')->nullable();
            $table->float('voltage_t')->nullable();

            $table->float('current_r')->nullable();
            $table->float('current_s')->nullable();
            $table->float('current_t')->nullable();

            $table->float('import_kwh')->nullable();
            $table->float('export_kwh')->nullable();

            $table->float('kvarh_total')->nullable();
            $table->float('power_factor')->nullable();

            $table->string('source'); // AMI / AMR / MANUAL

            $table->index(['meter_id', 'reading_time']);
            $table->index('source');

            $table->unique(['meter_id', 'reading_time', 'source']);

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

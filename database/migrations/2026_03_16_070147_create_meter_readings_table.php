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

            $table->decimal('voltage_r', 10, 3)->nullable();
            $table->decimal('voltage_s', 10, 3)->nullable();
            $table->decimal('voltage_t', 10, 3)->nullable();

            $table->decimal('current_r', 10, 3)->nullable();
            $table->decimal('current_s', 10, 3)->nullable();
            $table->decimal('current_t', 10, 3)->nullable();

            $table->decimal('import_kwh', 18, 3)->nullable();
            $table->decimal('export_kwh', 18, 3)->nullable();

            $table->decimal('kwh_total', 18, 3)->nullable();
            $table->decimal('kvarh_total', 18, 3)->nullable();

            $table->decimal('power_kw', 18, 3)->nullable();
            $table->decimal('apparent_power', 18, 3)->nullable();

            $table->decimal('power_factor', 8, 5)->nullable();

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

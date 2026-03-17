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
        Schema::create('meter_analysis', function (Blueprint $table) {

            $table->id();

            $table->foreignId('meter_id')->constrained('meters');

            $table->date('analysis_date');

            $table->double('consumption_kwh');

            $table->enum('anomaly_status', ['NORMAL', 'LOW_CONSUMPTION', 'SUSPECT', 'ANOMALY'])->default('NORMAL');

            $table->double('anomaly_score')->nullable();

            $table->string('analysis_method')->nullable(); // misal “rule-based” atau “Isolation Forest”

            $table->index(['meter_id', 'analysis_date']);

            $table->unique(['meter_id', 'analysis_date']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meter_analysis');
    }
};

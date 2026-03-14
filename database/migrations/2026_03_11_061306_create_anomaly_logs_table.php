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
        Schema::create('anomaly_logs', function (Blueprint $table) {
            $table->id();

            $table->string('idpel');
            $table->foreign('idpel')->references('idpel')->on('pelanggans')->cascadeOnDelete();

            $table->text('jenis_anomali');

            $table->double('nilai')->nullable();
            $table->double('threshold')->nullable();

            $table->text('sumber_data')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anomaly_logs');
    }
};

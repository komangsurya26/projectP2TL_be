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
        Schema::create('meters', function (Blueprint $table) {

            $table->id();

            $table->string('idpel');
            $table->foreign('idpel')->references('idpel')->on('pelanggans')->cascadeOnDelete();

            $table->enum('meter_type', [
                'AMI',
                'AMR',
                'MANUAL',
                'PRABAYAR'
            ]);

            $table->string('tariff')->nullable();
            $table->bigInteger('power_capacity')->nullable();

            $table->index(['idpel', 'meter_type']);

            $table->unique(['idpel']);


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meters');
    }
};

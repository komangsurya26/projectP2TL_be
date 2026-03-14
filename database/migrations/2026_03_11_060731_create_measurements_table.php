<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('measurements', function (Blueprint $table) {

            $table->id();

            $table->text('idpel');
            $table->foreign('idpel')
                ->references('idpel')
                ->on('pelanggans')
                ->cascadeOnDelete();

            $table->enum('jenis_meter', ['AMI', 'AMR']);

            $table->timestamp('waktu_data');

            /*
            Voltage 3 phase
            */

            $table->decimal('voltage_r', 8, 2)->nullable();
            $table->decimal('voltage_s', 8, 2)->nullable();
            $table->decimal('voltage_t', 8, 2)->nullable();

            /*
            Current 3 phase
            */

            $table->decimal('current_r', 10, 2)->nullable();
            $table->decimal('current_s', 10, 2)->nullable();
            $table->decimal('current_t', 10, 2)->nullable();

            /*
            Power factor
            */

            $table->decimal('pf', 5, 2)->nullable();

            /*
            Energy
            */

            $table->decimal('energy_import', 14, 2)->nullable();
            $table->decimal('energy_export', 14, 2)->nullable();

            $table->decimal('reactive_import', 14, 2)->nullable();
            $table->decimal('reactive_export', 14, 2)->nullable();

            /*
            Electrical parameters
            */

            $table->decimal('current_netral', 10, 2)->nullable();
            $table->decimal('apparent_power', 14, 2)->nullable();

            /*
            Index untuk time-series query
            */

            $table->index(['idpel', 'waktu_data']);
            $table->index('waktu_data');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('measurements');
    }
};

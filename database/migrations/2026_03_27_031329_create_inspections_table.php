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
        Schema::create('inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meter_id')->constrained('meters');

            $table->dateTime('inspection_time');

            $table->decimal('stand_lwbp', 14, 2)->nullable();
            $table->decimal('stand_wbp', 14, 2)->nullable();
            $table->decimal('stand_kvarh', 14, 2)->nullable();

            $table->string('status_kwh')->nullable();
            $table->string('kode_pesan')->nullable();
            $table->string('pemutusan')->nullable();
            $table->decimal('rupiah_ts', 18, 2)->nullable();

            $table->text('notes')->nullable();
            $table->string('source')->default('EPM'); //EPM

            $table->timestamps();

            $table->index(['meter_id', 'inspection_time']);
            $table->index('source');

            $table->unique(['meter_id', 'inspection_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inspections');
    }
};

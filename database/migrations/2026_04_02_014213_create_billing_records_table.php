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
        Schema::create('billing_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meter_id')->constrained('meters');
            $table->string('periode');
            $table->tinyInteger('month')->nullable();
            $table->integer('year')->nullable();
            $table->decimal('kwh_lwbp', 12, 2)->nullable();
            $table->decimal('kwh_wbp', 12, 2)->nullable();
            $table->decimal('kwh_total', 12, 2)->nullable();
            $table->decimal('kvarh', 12, 2)->nullable();
            $table->decimal('rpptl', 18, 2)->nullable();
            $table->decimal('rpppn', 18, 2)->nullable();
            $table->decimal('rpbpju', 18, 2)->nullable();
            $table->decimal('tagihan', 18, 2)->nullable();
            $table->string('status')->nullable();
            $table->decimal('cost_per_kwh', 12, 4)->nullable();
            $table->string('source')->default('SOREK');

            $table->timestamps();

            $table->unique(['meter_id', 'periode']);

            $table->index(['meter_id', 'month']);
            $table->index(['meter_id', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_records');
    }
};

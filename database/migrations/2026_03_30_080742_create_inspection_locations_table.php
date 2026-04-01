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
        Schema::create('inspection_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained('inspections');

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('gardu')->nullable();
            $table->string('tiang')->nullable();

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
        Schema::dropIfExists('inspection_locations');
    }
};

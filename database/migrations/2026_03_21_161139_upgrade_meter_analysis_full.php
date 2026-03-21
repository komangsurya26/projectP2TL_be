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
        Schema::table('meter_analysis', function (Blueprint $table) {
            $table->json('flags')->nullable();
            $table->float('avg_7_days')->nullable();
            $table->float('avg_30_days')->nullable();
            $table->integer('zero_days_count')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

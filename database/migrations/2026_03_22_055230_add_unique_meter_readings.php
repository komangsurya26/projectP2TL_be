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
        Schema::table('meter_readings', function (Blueprint $table) {
            $table->unique(['meter_id', 'reading_time'], 'meter_readings_meterid_readingtime_unique');
        });
    }

    public function down(): void {}
};

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
        Schema::create('analisa_results', function (Blueprint $table) {
            $table->id();

            $table->string('idpel');
            $table->foreign('idpel')->references('idpel')->on('pelanggans')->cascadeOnDelete();

            $table->integer('risk_score')->default(0);

            $table->enum('status', [
                'NORMAL',
                'SUSPECT',
                'ANOMALY'
            ]);

            $table->timestamp('analisa_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analisa_results');
    }
};

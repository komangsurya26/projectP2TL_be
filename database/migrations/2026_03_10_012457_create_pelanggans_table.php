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
        Schema::create('pelanggans', function (Blueprint $table) {

            $table->id();

            $table->string('idpel')->unique();

            $table->string('nama')->nullable();
            $table->string('notelp')->nullable();

            $table->string('kelurahan')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('kabupaten')->nullable();

            $table->string('tarif')->nullable();
            $table->integer('daya')->nullable();
            $table->string('nometer')->nullable();

            $table->string('unitup')->nullable();

            $table->double('koordinat_x')->nullable();
            $table->double('koordinat_y')->nullable();

            $table->enum('jenis_meter', ['AMI', 'AMR', 'PASKABAYAR', 'PRABAYAR'])->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pelanggans');
    }
};

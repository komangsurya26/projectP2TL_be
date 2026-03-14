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

            $table->text('nama')->nullable();
            $table->text('notelp')->nullable();

            $table->text('alamat')->nullable();

            $table->text('tarif')->nullable();
            $table->integer('daya')->nullable();
            $table->text('nometer')->nullable();

            $table->text('unitup')->nullable();

            $table->double('koordinat_x')->nullable();
            $table->double('koordinat_y')->nullable();

            $table->enum('jenis_meter', ['AMI', 'AMR', 'MANUAL', 'PRABAYAR'])->nullable();

            $table->index('idpel');

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

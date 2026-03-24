<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('unit_kode')->nullable()->after('id'); // misal 55100
            $table->string('unit_nama')->nullable()->after('unit_kode'); // misal Denpasar
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['unit_kode', 'unit_nama']);
        });
    }
};

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
        Schema::create('prepaid_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meter_id')->constrained('meters')->cascadeOnDelete();
            $table->string('token_number', 32)->unique();
            $table->date('purchase_date');
            $table->decimal('kwh_purchased', 10, 3)->nullable();
            $table->decimal('amount_paid', 18, 2)->nullable();
            $table->string('source')->default('PREPAID');

            $table->index(['meter_id', 'purchase_date']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prepaid_tokens');
    }
};

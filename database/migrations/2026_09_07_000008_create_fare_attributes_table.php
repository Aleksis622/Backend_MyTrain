<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('fare_attributes', function (Blueprint $table) {
            $table->string('fare_id')->primary();
            $table->decimal('price', 6, 2);
            $table->string('currency_type');
            $table->integer('payment_method');
            $table->integer('transfers')->nullable();
            $table->string('agency_id')->nullable();
            $table->integer('transfer_duration')->nullable();

            $table->foreign('agency_id')->references('agency_id')->on('agencies')->cascadeOnDelete();
        });
    }

    public function down(): void {
        Schema::dropIfExists('fare_attributes');
    }
};

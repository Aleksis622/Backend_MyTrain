<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('fare_rules', function (Blueprint $table) {
            $table->id();
            $table->string('fare_id');
            $table->string('route_id')->nullable();
            $table->string('origin_id')->nullable();
            $table->string('destination_id')->nullable();
            $table->string('contains_id')->nullable();

            $table->foreign('fare_id')->references('fare_id')->on('fare_attributes')->cascadeOnDelete();
            $table->foreign('route_id')->references('route_id')->on('routes')->cascadeOnDelete();
        });
    }

    public function down(): void {
        Schema::dropIfExists('fare_rules');
    }
};

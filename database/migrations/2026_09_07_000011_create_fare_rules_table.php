<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('fare_rules', function (Blueprint $table) {
            $table->id();

            $table->string('fare_id');
            $table->foreign('fare_id')
                ->references('fare_id')
                ->on('fare_attributes')
                ->cascadeOnDelete();

            $table->string('route_id')->nullable();
            $table->foreign('route_id')
                ->references('route_id')
                ->on('routes')
                ->cascadeOnDelete();

            
            $table->string('origin_id')->nullable();
            $table->foreign('origin_id')
                ->references('stop_id')
                ->on('stops')
                ->nullOnDelete();

            $table->string('destination_id')->nullable();
            $table->foreign('destination_id')
                ->references('stop_id')
                ->on('stops')
                ->nullOnDelete();

            $table->string('contains_id')->nullable();
            $table->foreign('contains_id')
                ->references('stop_id')
                ->on('stops')
                ->nullOnDelete();
        });
    }

    public function down(): void {
        Schema::dropIfExists('fare_rules');
    }
};

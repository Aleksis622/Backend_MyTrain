<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('stop_times', function (Blueprint $table) {
            $table->id();
            $table->string('trip_id');
            $table->time('arrival_time');
            $table->time('departure_time');
            $table->string('stop_id');
            $table->integer('stop_sequence');
            $table->integer('pickup_type')->nullable();
            $table->integer('drop_off_type')->nullable();

            $table->foreign('trip_id')->references('trip_id')->on('trips')->cascadeOnDelete();
            $table->foreign('stop_id')->references('stop_id')->on('stops')->cascadeOnDelete();
        });
    }

    public function down(): void {
        Schema::dropIfExists('stop_times');
    }
};

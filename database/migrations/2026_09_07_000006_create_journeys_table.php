<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journeys', function (Blueprint $table) {
            $table->id();

            
            $table->string('trip_id');
            $table->foreign('trip_id')
                ->references('trip_id')
                ->on('trips')
                ->cascadeOnDelete();

            
            $table->foreignId('train_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            
            $table->string('from_stop_id')->nullable();
            $table->foreign('from_stop_id')
                ->references('stop_id')
                ->on('stops')
                ->nullOnDelete();

            $table->string('to_stop_id')->nullable();
            $table->foreign('to_stop_id')
                ->references('stop_id')
                ->on('stops')
                ->nullOnDelete();

            
            $table->timestamp('departure_time')->nullable();
            $table->timestamp('arrival_time')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journeys');
    }
};

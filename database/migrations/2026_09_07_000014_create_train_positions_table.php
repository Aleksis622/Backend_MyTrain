<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('train_positions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('train_id')->constrained()->cascadeOnDelete();

            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            $table->float('speed')->nullable();   // km/h
            $table->float('heading')->nullable(); // degrees 0–360

            $table->timestamp('reported_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('train_positions');
    }
};

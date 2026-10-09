<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The map uses positions estimated from the timetable; the unused GPS part
     * (physical trains, their reported coordinates and journeys.train_id) is removed.
     */
    public function up(): void
    {
        Schema::table('journeys', function (Blueprint $table) {
            $table->dropForeign(['train_id']);
            $table->dropColumn('train_id');
        });

        Schema::dropIfExists('train_positions');
        Schema::dropIfExists('trains');
    }

    public function down(): void
    {
        Schema::create('trains', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('number')->nullable();
            $table->string('type')->nullable();
            $table->string('operator')->nullable();
            $table->timestamps();
        });

        Schema::create('train_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('train_id')->constrained()->cascadeOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->float('speed')->nullable();
            $table->float('heading')->nullable();
            $table->timestamp('reported_at')->nullable();
            $table->timestamps();
            $table->index(['train_id', 'reported_at']);
        });

        Schema::table('journeys', function (Blueprint $table) {
            $table->foreignId('train_id')->nullable()->after('trip_id')->constrained()->nullOnDelete();
        });
    }
};

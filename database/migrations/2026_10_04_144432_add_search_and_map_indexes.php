<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * stop_times: train search self-joins on trip_id and compares stop_sequence.
     * train_positions: the map looks up the latest reported_at per train.
     */
    public function up(): void
    {
        Schema::table('stop_times', function (Blueprint $table) {
            $table->index(['trip_id', 'stop_sequence']);
        });

        Schema::table('train_positions', function (Blueprint $table) {
            $table->index(['train_id', 'reported_at']);
        });
    }

    public function down(): void
    {
        Schema::table('stop_times', function (Blueprint $table) {
            $table->dropIndex(['trip_id', 'stop_sequence']);
        });

        Schema::table('train_positions', function (Blueprint $table) {
            $table->dropIndex(['train_id', 'reported_at']);
        });
    }
};

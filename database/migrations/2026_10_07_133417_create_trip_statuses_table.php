<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Live status of a trip on one day. No row = running on time.
     */
    public function up(): void
    {
        Schema::create('trip_statuses', function (Blueprint $table) {
            $table->id();

            $table->string('trip_id');
            $table->foreign('trip_id')->references('trip_id')->on('trips')->cascadeOnDelete();

            $table->date('service_date');
            $table->enum('status', ['delayed', 'cancelled']);
            $table->unsignedSmallInteger('delay_minutes')->default(0);
            $table->string('reason')->nullable();

            $table->timestamps();

            $table->unique(['trip_id', 'service_date']);
            $table->index('service_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_statuses');
    }
};

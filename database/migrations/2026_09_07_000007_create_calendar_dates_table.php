<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('calendar_dates', function (Blueprint $table) {
            $table->id();
            $table->string('service_id');
            $table->date('date');
            $table->integer('exception_type');

            $table->foreign('service_id')->references('service_id')->on('calendar')->cascadeOnDelete();
        });
    }

    public function down(): void {
        Schema::dropIfExists('calendar_dates');
    }
};

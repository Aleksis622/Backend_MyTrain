<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('agencies', function (Blueprint $table) {
            $table->string('agency_id')->primary();
            $table->string('agency_name');
            $table->string('agency_url')->nullable();
            $table->string('agency_timezone')->nullable();
            $table->string('agency_lang')->nullable();
            $table->string('agency_phone')->nullable();
            $table->string('agency_fare_url')->nullable();
            $table->string('agency_email')->nullable();
        });
    }

    public function down(): void {
        Schema::dropIfExists('agencies');
    }
};

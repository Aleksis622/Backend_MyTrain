<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('stops', function (Blueprint $table) {
            $table->string('stop_id')->primary();
            $table->string('stop_code')->nullable();
            $table->string('stop_name');
            $table->string('stop_desc')->nullable();
            $table->decimal('stop_lat', 12, 8);
            $table->decimal('stop_lon', 12, 8);
            $table->string('zone_id')->nullable();
            $table->string('stop_url')->nullable();
            $table->integer('location_type')->nullable();
            $table->string('parent_station')->nullable();
        });
    }

    public function down(): void {
        Schema::dropIfExists('stops');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('routes', function (Blueprint $table) {
            $table->string('route_id')->primary();
            $table->string('route_short_name')->nullable();
            $table->string('route_long_name')->nullable();
            $table->string('route_desc')->nullable();
            $table->integer('route_type')->nullable();
            $table->string('route_url')->nullable();
            $table->string('route_color')->nullable();
            $table->string('route_text_color')->nullable();
        });
    }

    public function down(): void {
        Schema::dropIfExists('routes');
    }
};

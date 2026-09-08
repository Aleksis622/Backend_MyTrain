<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('journey_id')->constrained()->cascadeOnDelete();
            $table->string('ticket_code')->unique();
            $table->decimal('price', 8, 2);
            $table->string('currency')->default('EUR');
            $table->enum('status', ['valid', 'used', 'expired', 'cancelled'])->default('valid');
            $table->timestamp('purchased_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};

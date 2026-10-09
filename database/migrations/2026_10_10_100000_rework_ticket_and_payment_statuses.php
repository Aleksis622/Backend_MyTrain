<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Clear status models (see Ticket::TRANSITIONS and Payment::TRANSITIONS):
     *   tickets:  pending -> paid | cancelled,  paid -> refunded   ("confirmed" was never used)
     *   payments: pending -> paid | failed | cancelled,  paid -> refunded
     */
    public function up(): void
    {
        // 1) allow old and new values while the data is moved
        Schema::table('tickets', function (Blueprint $table) {
            $table->enum('status', ['pending', 'confirmed', 'paid', 'cancelled', 'refunded'])->default('pending')->change();
        });

        DB::table('tickets')->where('status', 'confirmed')->update(['status' => 'paid']);

        // refunded tickets used to be stored as "cancelled"
        DB::table('tickets')
            ->where('status', 'cancelled')
            ->whereIn('id', DB::table('payments')->where('status', 'refunded')->select('ticket_id'))
            ->update(['status' => 'refunded']);

        // 2) final list
        Schema::table('tickets', function (Blueprint $table) {
            $table->enum('status', ['pending', 'paid', 'cancelled', 'refunded'])->default('pending')->change();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->enum('status', ['pending', 'paid', 'failed', 'cancelled', 'refunded'])->default('pending')->change();
        });

        // Payments started on the old simulated provider can never be finished: close them.
        DB::table('payments')->where('status', 'pending')->update(['status' => 'cancelled']);
    }

    public function down(): void
    {
        DB::table('payments')->where('status', 'cancelled')->update(['status' => 'failed']);
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('status', ['pending', 'paid', 'failed', 'refunded'])->default('pending')->change();
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->enum('status', ['pending', 'confirmed', 'paid', 'cancelled', 'refunded'])->default('pending')->change();
        });
        DB::table('tickets')->where('status', 'refunded')->update(['status' => 'cancelled']);
        Schema::table('tickets', function (Blueprint $table) {
            $table->enum('status', ['pending', 'confirmed', 'paid', 'cancelled'])->default('pending')->change();
        });
    }
};

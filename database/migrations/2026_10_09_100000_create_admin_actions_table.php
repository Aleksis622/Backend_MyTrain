<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Activity log of the admin panel: who changed what and when.
     */
    public function up(): void
    {
        Schema::create('admin_actions', function (Blueprint $table) {
            $table->id();

            // The admin who did it (kept as null if that account is ever removed).
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('action', 40); // e.g. ticket_refunded, train_status_changed
            $table->string('subject_type', 20); // ticket, user, train, account
            $table->string('subject_id')->nullable(); // string, because trip ids are strings

            // Names and values at the time of the action, so the log still reads well after deletes.
            $table->json('details')->nullable();

            $table->timestamps();

            $table->index(['subject_type', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_actions');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stripe Checkout data, and a database guarantee that a ticket never has
     * more than one active (pending or paid) payment at the same time.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // provider_payment_id keeps the Stripe PaymentIntent id (pi_...)
            $table->string('provider_session_id')->nullable()->after('provider_payment_id'); // cs_...
            $table->text('checkout_url')->nullable()->after('provider_session_id');
            $table->timestamp('checkout_expires_at')->nullable()->after('checkout_url');
            $table->string('provider_refund_id')->nullable()->after('paid_at'); // re_...
            $table->timestamp('refunded_at')->nullable()->after('provider_refund_id');

            $table->index('provider_session_id');
        });

        // ticket_id while the payment is pending or paid, otherwise NULL. A unique index ignores NULLs,
        // so two active payments for one ticket are impossible even when two requests race.
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('active_ticket_id')
                ->nullable()
                ->virtualAs("CASE WHEN status IN ('pending', 'paid') THEN ticket_id END");
            $table->unique('active_ticket_id');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['active_ticket_id']);
            $table->dropColumn('active_ticket_id');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['provider_session_id']);
            $table->dropColumn(['provider_session_id', 'checkout_url', 'checkout_expires_at', 'provider_refund_id', 'refunded_at']);
        });
    }
};

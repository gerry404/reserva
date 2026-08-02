<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Record which gateway transaction settled a payment, and make it unique.
 *
 * The webhook used to re-verify whatever transaction id the caller sent and
 * activate the plan on success, so one genuine payment could be replayed to
 * extend a subscription indefinitely. Binding each Flutterwave transaction to
 * at most one payment row closes that at the database level, regardless of what
 * the application logic does.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('flw_transaction_id')->nullable()->unique()->after('flw_ref');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['flw_transaction_id']);
            $table->dropColumn('flw_transaction_id');
        });
    }
};

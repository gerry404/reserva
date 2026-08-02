<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nuvo onboards merchants from Dakar to Nairobi, but every opening hour,
 * booking notice and "is this slot in the past?" check used to run against the
 * server's single hardcoded Africa/Douala clock, and every price was printed in
 * F CFA. Both now belong to the business.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('timezone', 64)->default('Africa/Douala')->after('country');
            $table->string('currency', 3)->default('XAF')->after('timezone');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['timezone', 'currency']);
        });
    }
};

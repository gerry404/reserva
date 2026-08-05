<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Drop the native ENUM columns in favour of plain strings validated in PHP.
 *
 * ENUMs are spelled differently by every driver (MySQL has a real type,
 * PostgreSQL emits a CHECK constraint) and adding a value means an ALTER that
 * behaves differently on each, which is exactly what we hit when adding the
 * `no_show` booking status. The allowed values now live in one place:
 * Booking::STATUSES and User::PLANS.
 *
 * Copy-then-rename keeps this driver-agnostic; ->change() would need the
 * constraint dropped by hand on PostgreSQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        // The index has to go first: SQLite refuses to drop a column an index
        // still references, and MySQL/PostgreSQL would silently discard it here
        // anyway. It is rebuilt once the new column is in place.
        Schema::table('bookings', fn (Blueprint $t) => $t->dropIndex(['business_id', 'status']));

        $this->swapColumn('bookings', 'status', 'pending');
        $this->swapColumn('users', 'plan', 'free');

        Schema::table('bookings', fn (Blueprint $t) => $t->index(['business_id', 'status']));
    }

    public function down(): void
    {
        // Intentionally irreversible: recreating the ENUMs would fail on any
        // row holding a value the old ENUM never knew about (e.g. no_show).
    }

    private function swapColumn(string $table, string $column, string $default): void
    {
        $temp = $column . '_tmp';

        Schema::table($table, function (Blueprint $t) use ($temp, $column, $default) {
            $t->string($temp, 20)->default($default)->after($column);
        });

        DB::table($table)->update([$temp => DB::raw($column)]);

        Schema::table($table, fn (Blueprint $t) => $t->dropColumn($column));
        Schema::table($table, fn (Blueprint $t) => $t->renameColumn($temp, $column));
    }
};

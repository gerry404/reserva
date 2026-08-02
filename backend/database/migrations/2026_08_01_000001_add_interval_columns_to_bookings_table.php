<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A booking is an interval, not a point in time.
 *
 * Until now a booking only stored the slot it started on, so a 3-hour service
 * booked at 10:00 left 10:30, 11:00, ... bookable. This migration gives every
 * booking an explicit [starts_at, ends_at) interval plus a snapshot of the
 * service duration and price at the time of booking, so later edits to a
 * service never rewrite history (and revenue survives a deleted service).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedSmallInteger('duration')->default(30)->after('time_slot')
                ->comment('minutes — snapshot of the service duration');
            $table->decimal('price', 10, 0)->default(0)->after('duration')
                ->comment('F CFA — snapshot of the service price');
            $table->dateTime('starts_at')->nullable()->after('price');
            $table->dateTime('ends_at')->nullable()->after('starts_at');

            // Null for cancelled bookings. Both MySQL and PostgreSQL exclude
            // NULLs from unique indexes, which gives us a portable "unique
            // among active bookings only" constraint without partial indexes.
            $table->string('slot_key', 64)->nullable()->after('ends_at');
        });

        $this->backfill();

        Schema::table('bookings', function (Blueprint $table) {
            $table->index(['business_id', 'starts_at']);
            $table->unique('slot_key');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropUnique(['slot_key']);
            $table->dropIndex(['business_id', 'starts_at']);
            $table->dropColumn(['duration', 'price', 'starts_at', 'ends_at', 'slot_key']);
        });
    }

    /**
     * Rebuild the interval columns for rows that pre-date them.
     *
     * Legacy data may already contain overlapping bookings — that was the bug.
     * We keep those rows untouched but leave their slot_key null so the new
     * unique index can still be created; only bookings made from now on are
     * protected by it.
     */
    private function backfill(): void
    {
        $claimed = [];

        DB::table('bookings')
            ->leftJoin('services', 'bookings.service_id', '=', 'services.id')
            ->orderBy('bookings.id')
            ->select(
                'bookings.id',
                'bookings.date',
                'bookings.time_slot',
                'bookings.status',
                'bookings.business_id',
                'services.duration as service_duration',
                'services.price as service_price',
            )
            ->chunk(500, function ($rows) use (&$claimed) {
                foreach ($rows as $row) {
                    $duration = (int) ($row->service_duration ?: 30);

                    $start = Carbon::parse(
                        Carbon::parse($row->date)->toDateString() . ' ' . substr((string) $row->time_slot, 0, 5)
                    );
                    $end = $start->copy()->addMinutes($duration);

                    $key = $row->business_id . '|' . $start->format('Y-m-d H:i');
                    $isActive = $row->status !== 'cancelled';

                    if (! $isActive || isset($claimed[$key])) {
                        $key = null;
                    } else {
                        $claimed[$key] = true;
                    }

                    DB::table('bookings')->where('id', $row->id)->update([
                        'duration'  => $duration,
                        'price'     => $row->service_price ?: 0,
                        'starts_at' => $start,
                        'ends_at'   => $end,
                        'slot_key'  => $key,
                    ]);
                }
            });
    }
};

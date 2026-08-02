<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduled work
|--------------------------------------------------------------------------
|
| Nothing here runs unless a process is ticking the scheduler every minute. The
| `scheduler` service in docker-compose.yml exists for exactly that, running
| `schedule:work`; without it these commands are inert and no reminder ever
| reaches a customer.
|
| Times are read on the *server* clock. Per-business timezones are handled
| inside the commands, which is why the reminder job scans a wide window and
| decides "is it tomorrow there?" per booking.
|
*/

// Reminders go out the evening before, when a merchant can still act on a
// customer who replies "actually, can we move it?".
Schedule::command('bookings:send-reminders')
    ->dailyAt('18:00')
    ->withoutOverlapping()
    ->onOneServer();

// Entitlement is computed from User::effectivePlan(), so this is housekeeping
// rather than enforcement: it keeps the stored column honest for reporting.
Schedule::command('plans:expire')
    ->dailyAt('00:15')
    ->withoutOverlapping()
    ->onOneServer();

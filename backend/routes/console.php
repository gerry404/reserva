<?php

use Illuminate\Support\Facades\Schedule;

// Send booking reminders every day at 18:00
Schedule::command('bookings:send-reminders')->dailyAt('18:00');

// Expire plans every day at midnight
Schedule::command('plans:expire')->dailyAt('00:05');

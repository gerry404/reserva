<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

/**
 * A merchant may only ever touch bookings made against their own business.
 *
 * Kept as a policy rather than an inline check so route-model binding can never
 * hand a controller somebody else's row unnoticed: forgetting authorize() now
 * shows up as a missing call, not as a silent leak.
 */
class BookingPolicy
{
    public function view(User $user, Booking $booking): bool
    {
        return $this->owns($user, $booking);
    }

    public function update(User $user, Booking $booking): bool
    {
        return $this->owns($user, $booking);
    }

    public function delete(User $user, Booking $booking): bool
    {
        return $this->owns($user, $booking);
    }

    private function owns(User $user, Booking $booking): bool
    {
        return $user->business !== null
            && $booking->business_id === $user->business->id;
    }
}

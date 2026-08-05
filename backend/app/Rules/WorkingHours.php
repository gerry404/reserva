<?php

namespace App\Rules;

use App\Models\Business;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates the opening-hours JSON.
 *
 * This blob drives every slot the booking page offers, so a malformed entry
 * used to mean a business silently showed no availability at all, or worse,
 * an inverted range that generated slots until the loop gave up. Rejecting bad
 * shapes at the door keeps AvailabilityService free of defensive noise.
 *
 * Expected shape, one entry per day:
 *
 *   "lundi": { "is_open": true, "open": "08:00", "close": "18:00" }
 */
class WorkingHours implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            $fail('Les horaires d\'ouverture sont invalides.');

            return;
        }

        foreach (Business::DAYS as $day) {
            if (! array_key_exists($day, $value)) {
                $fail("Les horaires du {$day} sont manquants.");

                return;
            }

            $entry = $value[$day];

            if (! is_array($entry) || ! array_key_exists('is_open', $entry)) {
                $fail("Les horaires du {$day} sont invalides.");

                return;
            }

            // A closed day carries no meaningful bounds; nothing else to check.
            if (! filter_var($entry['is_open'], FILTER_VALIDATE_BOOL)) {
                continue;
            }

            $open  = $this->clock($entry['open'] ?? null);
            $close = $this->clock($entry['close'] ?? null);

            if ($open === null || $close === null) {
                $fail("Les heures d'ouverture du {$day} doivent être au format HH:MM.");

                return;
            }

            if ($close <= $open) {
                $fail("Le {$day}, l'heure de fermeture doit être après l'heure d'ouverture.");

                return;
            }
        }
    }

    /** Minutes since midnight, or null when the value is not a clock time. */
    private function clock(mixed $value): ?int
    {
        if (! is_string($value) || ! preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $value, $m)) {
            return null;
        }

        return ((int) $m[1]) * 60 + (int) $m[2];
    }
}

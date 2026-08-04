<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A phone number we can actually reach.
 *
 * Everything downstream (the wa.me deep link the merchant taps, the Twilio
 * call, the SMS) needs digits in international form. Accepting "appelez-moi"
 * as a 20-character string produced dead links and unreachable customers, so
 * the shape is enforced at the edge.
 *
 * Deliberately permissive about separators: merchants and customers type
 * "+237 6 91 23 45 67" and that is fine. Only the digits are counted.
 */
class PhoneNumber implements ValidationRule
{
    /** ITU-T E.164 allows 15 digits at most; 7 is the shortest real number. */
    private const MIN_DIGITS = 7;
    private const MAX_DIGITS = 15;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('Le numéro de téléphone est invalide.');

            return;
        }

        if (! preg_match('/^\+?[0-9 ().\-]+$/', trim($value))) {
            $fail('Le numéro de téléphone ne doit contenir que des chiffres.');

            return;
        }

        $digits = strlen(self::digits($value));

        if ($digits < self::MIN_DIGITS || $digits > self::MAX_DIGITS) {
            $fail('Le numéro de téléphone est invalide. Indiquez-le au format international, ex : +237 6 91 23 45 67.');
        }
    }

    /** Strip everything a human might type around the digits. */
    public static function digits(?string $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?? '';
    }

    /**
     * The form wa.me and the SMS gateways expect: digits only, no leading plus,
     * no local trunk prefix.
     */
    public static function toE164(?string $value): string
    {
        return ltrim(self::digits($value), '0');
    }

    /**
     * Whether two numbers denote the same line.
     *
     * The same person writes "+237 691 23 45 67" today and "0691234567"
     * tomorrow, so exact string equality would lock customers out of their own
     * booking. Comparing the last {@see SIGNIFICANT_DIGITS} digits absorbs the
     * country code and trunk prefix while still being far too specific to
     * guess, and it is never the only credential: a reference is required too.
     */
    public static function matches(?string $a, ?string $b): bool
    {
        $left  = self::digits($a);
        $right = self::digits($b);

        if ($left === '' || $right === '') {
            return false;
        }

        return substr($left, -self::SIGNIFICANT_DIGITS) === substr($right, -self::SIGNIFICANT_DIGITS);
    }

    /** Enough to identify a subscriber line once the country code is dropped. */
    private const SIGNIFICANT_DIGITS = 8;
}

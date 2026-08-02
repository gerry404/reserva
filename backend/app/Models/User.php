<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'plan',
        'plan_expires_at',
        'google_id',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'google_id',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'plan_expires_at'   => 'datetime',
        'password'          => 'hashed',
    ];

    public const PLAN_FREE     = 'free';
    public const PLAN_PRO      = 'pro';
    public const PLAN_BUSINESS = 'business';

    public const PLANS = [self::PLAN_FREE, self::PLAN_PRO, self::PLAN_BUSINESS];

    /** Plans ranked, so "at least Pro" is a comparison and not a list. */
    private const PLAN_RANK = [
        self::PLAN_FREE     => 0,
        self::PLAN_PRO      => 1,
        self::PLAN_BUSINESS => 2,
    ];

    /** Bookings per calendar month, per plan. null means unmetered. */
    private const MONTHLY_BOOKING_LIMITS = [
        self::PLAN_FREE     => 30,
        self::PLAN_PRO      => null,
        self::PLAN_BUSINESS => null,
    ];

    public const TRIAL_DAYS = 14;

    // ─── Relations ───────────────────────────────────────────────────────

    public function business()
    {
        return $this->hasOne(Business::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // ─── Plan ────────────────────────────────────────────────────────────

    /**
     * The plan actually in force right now.
     *
     * A stored plan whose expiry has passed is worth nothing, and leaning on a
     * nightly job to write that back leaves a window where an expired
     * subscriber still gets paid features. Entitlement is therefore computed,
     * never read from the column alone.
     */
    public function effectivePlan(): string
    {
        if ($this->plan === self::PLAN_FREE) {
            return self::PLAN_FREE;
        }

        if ($this->plan_expires_at && $this->plan_expires_at->isPast()) {
            return self::PLAN_FREE;
        }

        return $this->plan ?? self::PLAN_FREE;
    }

    public function hasPlan(string $minimum): bool
    {
        return (self::PLAN_RANK[$this->effectivePlan()] ?? 0) >= (self::PLAN_RANK[$minimum] ?? 0);
    }

    public function isPro(): bool
    {
        return $this->hasPlan(self::PLAN_PRO);
    }

    /**
     * On the free Pro trial: entitled to Pro, never paid, and still inside the
     * trial window.
     *
     * The window check matters. Without it, any unpaid Pro account counted as a
     * trial — including one granted a year by hand for a demo or a partnership,
     * which then displayed "Essai Pro — 365 j" in the header. A trial cannot
     * outlast TRIAL_DAYS by definition.
     */
    public function onTrial(): bool
    {
        if (! $this->isPro() || ! $this->plan_expires_at) {
            return false;
        }

        // now() en premier : diffInDays est signé en Carbon 3, et l'ordre
        // inverse donnait une valeur négative, donc toujours sous le seuil.
        if (now()->diffInDays($this->plan_expires_at) > self::TRIAL_DAYS) {
            return false;
        }

        return $this->payments()->where('status', Payment::STATUS_SUCCESSFUL)->doesntExist();
    }

    /**
     * Bookings allowed this month, or null when unmetered.
     *
     * array_key_exists, not ??: null is a meaningful value here (unmetered),
     * so a null-coalesce would quietly meter every paid plan at the free cap.
     */
    public function monthlyBookingLimit(): ?int
    {
        $plan = $this->effectivePlan();

        return array_key_exists($plan, self::MONTHLY_BOOKING_LIMITS)
            ? self::MONTHLY_BOOKING_LIMITS[$plan]
            : self::MONTHLY_BOOKING_LIMITS[self::PLAN_FREE];
    }

    // ─── Notifications ───────────────────────────────────────────────────

    /**
     * Password resets are completed in the SPA, not in a Blade page, so the
     * link has to point at the frontend rather than at a Laravel route.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}

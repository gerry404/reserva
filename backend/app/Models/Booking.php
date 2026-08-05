<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A booking occupies the half-open interval [starts_at, ends_at).
 *
 * `date` and `time_slot` are what the customer picked and what we display;
 * `starts_at`/`ends_at` are derived from them plus `duration` on save, and are
 * the only thing availability logic ever looks at. Keep writing to date /
 * time_slot / duration, never to the derived columns directly.
 */
class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'service_id',
        'reference',
        'customer_name',
        'customer_phone',
        'customer_email',
        'date',
        'time_slot',
        'duration',
        'price',
        'status',
        'notes',
        'reminder_sent',
    ];

    protected $casts = [
        'date'          => 'date',
        'starts_at'     => 'datetime',
        'ends_at'       => 'datetime',
        'duration'      => 'integer',
        'price'         => 'decimal:0',
        'reminder_sent' => 'boolean',
    ];

    public const STATUS_PENDING   = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_NO_SHOW   = 'no_show';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_CANCELLED,
        self::STATUS_COMPLETED,
        self::STATUS_NO_SHOW,
    ];

    /**
     * Statuses that still hold their slot. A cancelled booking frees the time;
     * a no-show does not, because the merchant really did wait for it.
     */
    public const ACTIVE_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_COMPLETED,
        self::STATUS_NO_SHOW,
    ];

    public const STATUS_LABELS = [
        self::STATUS_PENDING   => 'En attente',
        self::STATUS_CONFIRMED => 'Confirmé',
        self::STATUS_CANCELLED => 'Annulé',
        self::STATUS_COMPLETED => 'Terminé',
        self::STATUS_NO_SHOW   => 'Non présenté',
    ];

    public const STATUS_COLORS = [
        self::STATUS_PENDING   => 'yellow',
        self::STATUS_CONFIRMED => 'green',
        self::STATUS_CANCELLED => 'red',
        self::STATUS_COMPLETED => 'blue',
        self::STATUS_NO_SHOW   => 'gray',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $booking) {
            $booking->reference ??= static::generateReference();
        });

        static::saving(function (self $booking) {
            $booking->syncInterval();
        });
    }

    // ─── Derived interval ────────────────────────────────────────────────

    /**
     * Recompute starts_at / ends_at / slot_key from date + time_slot + duration.
     *
     * slot_key is null for cancelled bookings, so the unique index on it stops
     * two live bookings from claiming the same start while ignoring cancelled
     * ones: NULLs are excluded from unique indexes on MySQL and PostgreSQL
     * alike.
     */
    public function syncInterval(): void
    {
        if (! $this->date || ! $this->time_slot) {
            return;
        }

        $start = Carbon::parse(
            Carbon::parse($this->date)->toDateString() . ' ' . $this->time_slot
        );

        $this->starts_at = $start;
        $this->ends_at   = $start->copy()->addMinutes(max(1, (int) $this->duration));
        $this->slot_key  = $this->isActive()
            ? $this->business_id . '|' . $start->format('Y-m-d H:i')
            : null;
    }

    /**
     * Ambiguous glyphs (0/O, 1/I/L) are excluded from the alphabet: customers
     * read these references out over the phone.
     */
    public static function generateReference(): string
    {
        $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $max      = strlen($alphabet) - 1;

        do {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= $alphabet[random_int(0, $max)];
            }
            $reference = 'RES-' . $code;
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }

    // ─── Attributes ──────────────────────────────────────────────────────

    /**
     * The column is a TIME, so drivers hand back "14:00:00" while everything
     * else (slot generation, comparisons, display) speaks "14:00".
     */
    protected function timeSlot(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value === null ? null : substr($value, 0, 5),
            set: fn (?string $value) => $value === null ? null : substr(trim($value), 0, 5),
        );
    }

    protected function statusLabel(): Attribute
    {
        return Attribute::get(fn () => self::STATUS_LABELS[$this->status] ?? $this->status);
    }

    protected function statusColor(): Attribute
    {
        return Attribute::get(fn () => self::STATUS_COLORS[$this->status] ?? 'gray');
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true);
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED], true)
            && $this->starts_at?->isFuture();
    }

    // ─── Relations ───────────────────────────────────────────────────────

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────────────

    public function scopeForBusiness(Builder $query, int $businessId): Builder
    {
        return $query->where('bookings.business_id', $businessId);
    }

    /** Bookings that still hold their slot. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('bookings.status', self::ACTIVE_STATUSES);
    }

    /**
     * Bookings whose interval overlaps [$start, $end).
     *
     * Touching intervals do not overlap: a 10:00–11:00 booking leaves 11:00
     * free, which is why both comparisons are strict.
     */
    public function scopeOverlapping(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->where('bookings.starts_at', '<', $end)
                     ->where('bookings.ends_at', '>', $start);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->whereIn('bookings.status', [self::STATUS_PENDING, self::STATUS_CONFIRMED])
                     ->where('bookings.starts_at', '>=', now())
                     ->orderBy('bookings.starts_at');
    }
}

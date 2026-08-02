<?php

namespace App\Models;

use App\Support\Uploads;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Business extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'category',
        'address',
        'city',
        'country',
        'timezone',
        'currency',
        'phone',
        'whatsapp',
        'logo',
        'cover_image',
        'working_hours',
        'slot_duration',
        'booking_notice',
        'notifications_whatsapp',
        'notifications_sms',
        'notifications_email',
        'is_active',
        'accent_color',
    ];

    protected $casts = [
        'working_hours'          => 'array',
        'notifications_whatsapp' => 'boolean',
        'notifications_sms'      => 'boolean',
        'notifications_email'    => 'boolean',
        'is_active'              => 'boolean',
        'slot_duration'          => 'integer',
        'booking_notice'         => 'integer',
    ];

    public const DAYS = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];

    /** Slot lengths a merchant may pick, in minutes. */
    public const SLOT_DURATIONS = [15, 30, 45, 60, 90, 120];

    /**
     * Slugs we refuse to hand out: they become /b/{slug} and read like system
     * pages, or clash with a real route if the SPA ever flattens its URLs.
     */
    public const RESERVED_SLUGS = [
        'admin', 'api', 'app', 'auth', 'b', 'billing', 'dashboard', 'help',
        'login', 'logout', 'new', 'register', 'nuvo', 'settings', 'signup',
        'support', 'track', 'www',
    ];

    /**
     * Sensible defaults per country, so a merchant in Nairobi is not silently
     * put on a Douala clock quoting F CFA.
     *
     * @var array<string, array{timezone: string, currency: string}>
     */
    private const COUNTRY_DEFAULTS = [
        'CM' => ['timezone' => 'Africa/Douala',        'currency' => 'XAF'],
        'CI' => ['timezone' => 'Africa/Abidjan',       'currency' => 'XOF'],
        'SN' => ['timezone' => 'Africa/Dakar',         'currency' => 'XOF'],
        'BF' => ['timezone' => 'Africa/Ouagadougou',   'currency' => 'XOF'],
        'ML' => ['timezone' => 'Africa/Bamako',        'currency' => 'XOF'],
        'BJ' => ['timezone' => 'Africa/Porto-Novo',    'currency' => 'XOF'],
        'TG' => ['timezone' => 'Africa/Lome',          'currency' => 'XOF'],
        'NE' => ['timezone' => 'Africa/Niamey',        'currency' => 'XOF'],
        'GA' => ['timezone' => 'Africa/Libreville',    'currency' => 'XAF'],
        'CG' => ['timezone' => 'Africa/Brazzaville',   'currency' => 'XAF'],
        'TD' => ['timezone' => 'Africa/Ndjamena',      'currency' => 'XAF'],
        'CF' => ['timezone' => 'Africa/Bangui',        'currency' => 'XAF'],
        'GQ' => ['timezone' => 'Africa/Malabo',        'currency' => 'XAF'],
        'CD' => ['timezone' => 'Africa/Kinshasa',      'currency' => 'CDF'],
        'NG' => ['timezone' => 'Africa/Lagos',         'currency' => 'NGN'],
        'GH' => ['timezone' => 'Africa/Accra',         'currency' => 'GHS'],
        'KE' => ['timezone' => 'Africa/Nairobi',       'currency' => 'KES'],
        'TZ' => ['timezone' => 'Africa/Dar_es_Salaam', 'currency' => 'TZS'],
        'UG' => ['timezone' => 'Africa/Kampala',       'currency' => 'UGX'],
        'RW' => ['timezone' => 'Africa/Kigali',        'currency' => 'RWF'],
        'MA' => ['timezone' => 'Africa/Casablanca',    'currency' => 'MAD'],
        'TN' => ['timezone' => 'Africa/Tunis',         'currency' => 'TND'],
        'DZ' => ['timezone' => 'Africa/Algiers',       'currency' => 'DZD'],
        'FR' => ['timezone' => 'Europe/Paris',         'currency' => 'EUR'],
        'BE' => ['timezone' => 'Europe/Brussels',      'currency' => 'EUR'],
        'CA' => ['timezone' => 'America/Montreal',     'currency' => 'CAD'],
    ];

    protected static function booted(): void
    {
        static::creating(function (self $business) {
            $business->slug ??= static::generateUniqueSlug($business->name);

            $defaults = static::defaultsForCountry($business->country);
            $business->timezone      ??= $defaults['timezone'];
            $business->currency      ??= $defaults['currency'];
            $business->working_hours ??= static::defaultWorkingHours();
        });
    }

    // ─── Slug ────────────────────────────────────────────────────────────

    public static function generateUniqueSlug(?string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug((string) $name) ?: 'commerce';
        $slug = $base;

        for ($suffix = 2; static::slugTaken($slug, $ignoreId); $suffix++) {
            $slug = $base . '-' . $suffix;
        }

        return $slug;
    }

    public static function slugTaken(string $slug, ?int $ignoreId = null): bool
    {
        if (in_array($slug, self::RESERVED_SLUGS, true)) {
            return true;
        }

        return static::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists();
    }

    // ─── Defaults ────────────────────────────────────────────────────────

    /** @return array{timezone: string, currency: string} */
    public static function defaultsForCountry(?string $country): array
    {
        return self::COUNTRY_DEFAULTS[strtoupper((string) $country)]
            ?? ['timezone' => config('app.timezone'), 'currency' => 'XAF'];
    }

    /** @return array<string, array{is_open: bool, open: string, close: string}> */
    public static function defaultWorkingHours(): array
    {
        $hours = [];

        foreach (self::DAYS as $day) {
            $hours[$day] = match ($day) {
                'dimanche' => ['is_open' => false, 'open' => '09:00', 'close' => '13:00'],
                'samedi'   => ['is_open' => true,  'open' => '08:00', 'close' => '14:00'],
                default    => ['is_open' => true,  'open' => '08:00', 'close' => '18:00'],
            };
        }

        return $hours;
    }

    // ─── Relations ───────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Only the services a customer may book. */
    public function services()
    {
        return $this->hasMany(Service::class)->where('is_active', true)->orderBy('name');
    }

    /** Every service, including those hidden from the booking page. */
    public function allServices()
    {
        return $this->hasMany(Service::class)->orderBy('name');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    // ─── Presentation ────────────────────────────────────────────────────

    /**
     * Image paths are storage-relative, but every client needs a URL — and that
     * URL differs between a local disk and an S3 bucket. Appending them keeps
     * the frontend from having to know which backend is in use, or from
     * concatenating a base URL by hand.
     */
    protected $appends = ['logo_url', 'cover_image_url', 'public_url'];

    public function getLogoUrlAttribute(): ?string
    {
        return Uploads::url($this->logo);
    }

    public function getCoverImageUrlAttribute(): ?string
    {
        return Uploads::url($this->cover_image);
    }

    public function getPublicUrlAttribute(): string
    {
        return config('app.frontend_url') . '/b/' . $this->slug;
    }
}

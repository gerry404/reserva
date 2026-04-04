<?php

namespace App\Models;

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

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($business) {
            if (empty($business->slug)) {
                $business->slug = static::generateUniqueSlug($business->name);
            }
        });
    }

    public static function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $original = $slug;
        $count = 1;
        while (static::where('slug', $slug)->exists()) {
            $slug = $original . '-' . $count++;
        }
        return $slug;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function services()
    {
        return $this->hasMany(Service::class)->where('is_active', true)->orderBy('name');
    }

    public function allServices()
    {
        return $this->hasMany(Service::class)->orderBy('name');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function getPublicUrlAttribute(): string
    {
        return config('app.frontend_url', 'http://localhost:5173') . '/b/' . $this->slug;
    }

    public function getDefaultWorkingHours(): array
    {
        $days = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
        $hours = [];
        foreach ($days as $day) {
            $hours[$day] = [
                'is_open' => !in_array($day, ['dimanche']),
                'open'    => '08:00',
                'close'   => '18:00',
            ];
        }
        return $hours;
    }
}

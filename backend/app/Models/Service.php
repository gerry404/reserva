<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'name',
        'description',
        'duration',
        'price',
        'category',
        'color',
        'images',
        'is_active',
    ];

    protected $casts = [
        'duration'  => 'integer',
        'price'     => 'decimal:0',
        'is_active' => 'boolean',
        'images'    => 'array',
    ];

    /** Bounds mirrored by the request rules; a service must fit inside a day. */
    public const MIN_DURATION = 5;
    public const MAX_DURATION = 480;
    public const MAX_IMAGES   = 5;

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    protected function formattedPrice(): Attribute
    {
        return Attribute::get(function () {
            if ((float) $this->price === 0.0) {
                return 'Gratuit';
            }

            return Money::format($this->price, $this->business?->currency);
        });
    }

    protected function formattedDuration(): Attribute
    {
        return Attribute::get(function () {
            $minutes = (int) $this->duration;

            if ($minutes < 60) {
                return $minutes . ' min';
            }

            $hours     = intdiv($minutes, 60);
            $remainder = $minutes % 60;

            return $remainder > 0 ? "{$hours}h{$remainder}" : "{$hours}h";
        });
    }
}

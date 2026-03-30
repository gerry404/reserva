<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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
        'status',
        'notes',
        'reminder_sent',
    ];

    protected $casts = [
        'date'          => 'date',
        'reminder_sent' => 'boolean',
    ];

    const STATUSES = ['pending', 'confirmed', 'cancelled', 'completed'];

    const STATUS_LABELS = [
        'pending'   => 'En attente',
        'confirmed' => 'Confirmé',
        'cancelled' => 'Annulé',
        'completed' => 'Terminé',
    ];

    const STATUS_COLORS = [
        'pending'   => 'yellow',
        'confirmed' => 'green',
        'cancelled' => 'red',
        'completed' => 'blue',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($booking) {
            $booking->reference = 'RES-' . strtoupper(Str::random(8));
        });
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'gray';
    }

    public function scopeUpcoming($query)
    {
        return $query->whereIn('status', ['pending', 'confirmed'])
                     ->where('date', '>=', now()->toDateString())
                     ->orderBy('date')
                     ->orderBy('time_slot');
    }

    public function scopeToday($query)
    {
        return $query->where('date', now()->toDateString());
    }

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('bookings.business_id', $businessId);
    }
}

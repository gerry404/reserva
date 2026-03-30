<?php

namespace App\Models;

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
        'is_active',
    ];

    protected $casts = [
        'duration'  => 'integer',
        'price'     => 'decimal:0',
        'is_active' => 'boolean',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function getFormattedPriceAttribute(): string
    {
        if ($this->price == 0) return 'Gratuit';
        return number_format($this->price, 0, ',', ' ') . ' F CFA';
    }

    public function getFormattedDurationAttribute(): string
    {
        if ($this->duration < 60) return $this->duration . ' min';
        $hours   = intdiv($this->duration, 60);
        $minutes = $this->duration % 60;
        return $minutes > 0 ? "{$hours}h{$minutes}" : "{$hours}h";
    }
}

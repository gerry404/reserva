<?php

namespace App\Models;

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
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'plan_expires_at'   => 'datetime',
        'password'          => 'hashed',
    ];

    const PLANS = ['free', 'pro', 'business'];

    public function business()
    {
        return $this->hasOne(Business::class);
    }

    public function payments()
    {
        return $this->hasMany(\App\Models\Payment::class);
    }

    public function isProOrHigher(): bool
    {
        return in_array($this->plan, ['pro', 'business']);
    }

    public function getMonthlyBookingLimit(): int
    {
        return match($this->plan) {
            'free'     => 30,
            'pro'      => PHP_INT_MAX,
            'business' => PHP_INT_MAX,
            default    => 30,
        };
    }
}

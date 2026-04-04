<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'user_id', 'tx_ref', 'flw_ref', 'plan', 'billing_cycle',
        'amount', 'currency', 'payment_method', 'status', 'meta', 'paid_at',
    ];

    protected $casts = [
        'meta'    => 'array',
        'paid_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Pricing config
    const PLANS = [
        'pro' => [
            'monthly' => 2900,
            'yearly'  => 24900,
            'label'   => 'Pro',
        ],
        'business' => [
            'monthly' => 7900,
            'yearly'  => 69900,
            'label'   => 'Business',
        ],
    ];

    public static function generateTxRef(): string
    {
        return 'RSV-' . strtoupper(bin2hex(random_bytes(8)));
    }
}

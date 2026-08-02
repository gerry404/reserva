<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'user_id', 'tx_ref', 'flw_ref', 'flw_transaction_id', 'plan',
        'billing_cycle', 'amount', 'currency', 'payment_method', 'status',
        'meta', 'paid_at',
    ];

    protected $casts = [
        'meta'    => 'array',
        'amount'  => 'integer',
        'paid_at' => 'datetime',
    ];

    protected $hidden = [
        // Raw gateway payloads can echo back customer card metadata; they are
        // kept for support and reconciliation, never serialised to a client.
        'meta',
    ];

    public const STATUS_PENDING    = 'pending';
    public const STATUS_SUCCESSFUL = 'successful';
    public const STATUS_FAILED     = 'failed';
    public const STATUS_CANCELLED  = 'cancelled';

    public const CYCLE_MONTHLY = 'monthly';
    public const CYCLE_YEARLY  = 'yearly';

    public const CYCLES = [self::CYCLE_MONTHLY, self::CYCLE_YEARLY];

    /**
     * Prices are held in the smallest unit of XAF, which has no minor unit —
     * 2900 means 2 900 F CFA.
     */
    public const PLANS = [
        User::PLAN_PRO => [
            'label'   => 'Pro',
            'monthly' => 2900,
            'yearly'  => 24900,
        ],
        User::PLAN_BUSINESS => [
            'label'   => 'Business',
            'monthly' => 7900,
            'yearly'  => 69900,
        ],
    ];

    public const CURRENCY = 'XAF';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function amountFor(string $plan, string $cycle): int
    {
        return self::PLANS[$plan][$cycle];
    }

    public static function labelFor(string $plan): string
    {
        return self::PLANS[$plan]['label'] ?? ucfirst($plan);
    }

    public static function generateTxRef(): string
    {
        return 'RSV-' . strtoupper(bin2hex(random_bytes(8)));
    }

    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_SUCCESSFUL;
    }

    public function cycleLabel(): string
    {
        return $this->billing_cycle === self::CYCLE_YEARLY ? 'Annuel' : 'Mensuel';
    }
}

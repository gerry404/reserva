<?php

namespace App\Support;

/**
 * Formatting prices for the currencies Nuvo actually serves.
 *
 * Zero-decimal currencies are the norm here (nobody quotes 5 000,00 F CFA)
 * so the decimal count is per currency rather than a blanket two.
 */
final class Money
{
    /** @var array<string, array{symbol: string, decimals: int}> */
    private const CURRENCIES = [
        'XAF' => ['symbol' => 'F CFA', 'decimals' => 0],
        'XOF' => ['symbol' => 'F CFA', 'decimals' => 0],
        'CDF' => ['symbol' => 'FC',    'decimals' => 0],
        'NGN' => ['symbol' => '₦',     'decimals' => 0],
        'GHS' => ['symbol' => 'GH₵',   'decimals' => 2],
        'KES' => ['symbol' => 'KSh',   'decimals' => 0],
        'TZS' => ['symbol' => 'TSh',   'decimals' => 0],
        'UGX' => ['symbol' => 'USh',   'decimals' => 0],
        'RWF' => ['symbol' => 'FRw',   'decimals' => 0],
        'MAD' => ['symbol' => 'DH',    'decimals' => 2],
        'TND' => ['symbol' => 'DT',    'decimals' => 3],
        'DZD' => ['symbol' => 'DA',    'decimals' => 2],
        'EUR' => ['symbol' => '€',     'decimals' => 2],
        'CAD' => ['symbol' => '$',     'decimals' => 2],
        'USD' => ['symbol' => '$',     'decimals' => 2],
    ];

    private const FALLBACK = ['symbol' => 'F CFA', 'decimals' => 0];

    public static function format(int|float|string|null $amount, ?string $currency = null): string
    {
        $amount = (float) $amount;
        $config = self::CURRENCIES[strtoupper((string) $currency)] ?? self::FALLBACK;

        // French convention: comma for decimals, narrow space for thousands.
        $formatted = number_format($amount, $config['decimals'], ',', "\u{202F}");

        return $formatted . "\u{202F}" . $config['symbol'];
    }

    public static function symbol(?string $currency): string
    {
        return (self::CURRENCIES[strtoupper((string) $currency)] ?? self::FALLBACK)['symbol'];
    }

    public static function isSupported(?string $currency): bool
    {
        return isset(self::CURRENCIES[strtoupper((string) $currency)]);
    }
}

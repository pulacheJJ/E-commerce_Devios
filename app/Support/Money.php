<?php

namespace App\Support;

use InvalidArgumentException;

final class Money
{
    public static function cents(string|int|float $amount): int
    {
        $normalized = trim((string) $amount);
        if (! preg_match('/^(?<sign>-?)(?<whole>\d+)(?:\.(?<fraction>\d+))?$/', $normalized, $matches)) {
            throw new InvalidArgumentException('Importe monetario inválido.');
        }

        $fraction = str_pad($matches['fraction'] ?? '', 3, '0');
        $cents = ((int) $matches['whole'] * 100) + (int) substr($fraction, 0, 2);
        if ((int) $fraction[2] >= 5) {
            $cents++;
        }

        return ($matches['sign'] ?? '') === '-' ? -$cents : $cents;
    }

    public static function percentageDiscount(int $baseCents, string|int|float $percentage): int
    {
        $basisPoints = self::scaledInteger($percentage, 2);

        return intdiv(($baseCents * $basisPoints) + 5000, 10000);
    }

    public static function decimal(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $absolute = abs($cents);

        return sprintf('%s%d.%02d', $sign, intdiv($absolute, 100), $absolute % 100);
    }

    private static function scaledInteger(string|int|float $value, int $scale): int
    {
        $normalized = trim((string) $value);
        if (! preg_match('/^(?<whole>\d+)(?:\.(?<fraction>\d+))?$/', $normalized, $matches)) {
            throw new InvalidArgumentException('Valor decimal inválido.');
        }

        $fraction = str_pad($matches['fraction'] ?? '', $scale, '0');

        return ((int) $matches['whole'] * (10 ** $scale)) + (int) substr($fraction, 0, $scale);
    }
}

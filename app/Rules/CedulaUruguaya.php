<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CedulaUruguaya implements ValidationRule
{
    private const MULTIPLIERS = [2, 9, 8, 7, 6, 3, 4];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $ci = preg_replace('/[.\-\s]/', '', (string) $value);

        if (! preg_match('/^\d{7,8}$/', $ci)) {
            $fail('La cédula debe tener 7 u 8 dígitos.');

            return;
        }

        $digits = str_pad(substr($ci, 0, -1), 7, '0', STR_PAD_LEFT);
        $checkDigit = (int) substr($ci, -1);

        if (self::checkDigit($digits) !== $checkDigit) {
            $fail('La cédula no es válida.');
        }
    }

    /** Check digit for a 7-digit CI body (standard Uruguayan algorithm). */
    public static function checkDigit(string $sevenDigits): int
    {
        $sum = 0;
        foreach (str_split($sevenDigits) as $i => $digit) {
            $sum += (int) $digit * self::MULTIPLIERS[$i];
        }

        return (10 - $sum % 10) % 10;
    }
}

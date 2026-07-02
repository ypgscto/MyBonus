<?php

namespace App\Support;

class WhatsappNumberHelper
{
    public static function normalize(string $number): string
    {
        return IndonesianPhoneHelper::normalize($number);
    }

    public static function isValidIndonesianNumber(string $number): bool
    {
        $normalized = preg_replace('/[\s\-\.]/', '', $number) ?? '';

        return (bool) preg_match('/^(\+62|62|0)8[1-9][0-9]{6,10}$/', $normalized);
    }
}

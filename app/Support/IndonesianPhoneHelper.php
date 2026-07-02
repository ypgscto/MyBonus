<?php

namespace App\Support;

class IndonesianPhoneHelper
{
    public static function normalize(string $phone): string
    {
        $normalized = preg_replace('/[\s\-\.]/', '', $phone) ?? '';

        if (str_starts_with($normalized, '+62')) {
            return '62'.substr($normalized, 3);
        }

        if (str_starts_with($normalized, '0')) {
            return '62'.substr($normalized, 1);
        }

        if (str_starts_with($normalized, '62')) {
            return $normalized;
        }

        return ltrim($normalized, '+');
    }
}

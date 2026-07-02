<?php

namespace App\Support;

class AccountNumberMasker
{
    public static function mask(?string $accountNumber): string
    {
        if ($accountNumber === null || $accountNumber === '') {
            return '-';
        }

        $length = strlen($accountNumber);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        if ($length <= 6) {
            return substr($accountNumber, 0, 2).str_repeat('*', $length - 4).substr($accountNumber, -2);
        }

        return substr($accountNumber, 0, 4).str_repeat('*', max(2, $length - 6)).substr($accountNumber, -2);
    }
}

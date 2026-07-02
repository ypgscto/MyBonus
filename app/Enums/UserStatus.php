<?php

namespace App\Enums;

enum UserStatus: string
{
    case Aktif = 'aktif';
    case Nonaktif = 'nonaktif';

    public function label(): string
    {
        return match ($this) {
            self::Aktif => 'Aktif',
            self::Nonaktif => 'Nonaktif',
        };
    }

    public function isActive(): bool
    {
        return $this === self::Aktif;
    }
}

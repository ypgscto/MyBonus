<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case AdminPmb = 'admin_pmb';
    case Verifikator = 'verifikator';
    case Keuangan = 'keuangan';
    case Presenter = 'presenter';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::AdminPmb => 'Admin PMB',
            self::Verifikator => 'Verifikator',
            self::Keuangan => 'Keuangan',
            self::Presenter => 'Presenter',
        };
    }

    public function dashboardRoute(): string
    {
        return match ($this) {
            self::SuperAdmin => 'dashboard.super-admin',
            self::AdminPmb => 'dashboard.admin-pmb',
            self::Verifikator => 'dashboard.verifikator',
            self::Keuangan => 'dashboard.keuangan',
            self::Presenter => 'presenter.dashboard',
        };
    }

    public function changePasswordRoute(): ?string
    {
        return match ($this) {
            self::Presenter => 'presenter.change-password',
            default => null,
        };
    }
}

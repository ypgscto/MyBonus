<?php

namespace App\Enums;

enum ReportType: string
{
    case PresenterRequests = 'presenter_requests';
    case StudentDetails = 'student_details';
    case DuplicateNim = 'duplicate_nim';
    case VerifikatorTransfers = 'verifikator_transfers';
    case PresenterTransfers = 'presenter_transfers';
    case TransferVariance = 'transfer_variance';
    case Rejected = 'rejected';
    case Closed = 'closed';
    case ActivePresenters = 'active_presenters';
    case AuditActivity = 'audit_activity';

    public function label(): string
    {
        return match ($this) {
            self::PresenterRequests => 'Laporan Permintaan Presenter',
            self::StudentDetails => 'Laporan Detail Mahasiswa per Presenter',
            self::DuplicateNim => 'Laporan Duplicate/Rekap NIM yang pernah diajukan',
            self::VerifikatorTransfers => 'Laporan Transfer Verifikator ke Keuangan',
            self::PresenterTransfers => 'Laporan Transfer Keuangan ke Presenter',
            self::TransferVariance => 'Laporan Selisih Transfer Internal dan Transfer ke Presenter',
            self::Rejected => 'Laporan Permintaan Ditolak',
            self::Closed => 'Laporan Permintaan Closed',
            self::ActivePresenters => 'Laporan Presenter Teraktif',
            self::AuditActivity => 'Laporan Audit Aktivitas',
        };
    }
}

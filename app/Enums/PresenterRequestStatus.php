<?php

namespace App\Enums;

enum PresenterRequestStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case RejectedByVerifikator = 'rejected_by_verifikator';
    case ApprovedByVerifikator = 'approved_by_verifikator';
    case TransferredToFinance = 'transferred_to_finance';
    case ReceivedByFinance = 'received_by_finance';
    case TransferredToPresenter = 'transferred_to_presenter';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Menunggu Verifikasi',
            self::RejectedByVerifikator => 'Ditolak Verifikator',
            self::ApprovedByVerifikator => 'Disetujui Verifikator',
            self::TransferredToFinance => 'Transfer ke Keuangan',
            self::ReceivedByFinance => 'Dana Diterima Keuangan',
            self::TransferredToPresenter => 'Transfer ke Presenter',
            self::Closed => 'Selesai / Closed',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'bg-gray-100 text-gray-700 ring-gray-200',
            self::Submitted => 'bg-blue-100 text-blue-800 ring-blue-200',
            self::RejectedByVerifikator => 'bg-red-100 text-red-800 ring-red-200',
            self::ApprovedByVerifikator => 'bg-green-100 text-green-800 ring-green-200',
            self::TransferredToFinance => 'bg-purple-100 text-purple-800 ring-purple-200',
            self::ReceivedByFinance => 'bg-cyan-100 text-cyan-800 ring-cyan-200',
            self::TransferredToPresenter => 'bg-amber-100 text-amber-900 ring-amber-200',
            self::Closed => 'bg-emerald-100 text-emerald-900 ring-emerald-200',
            self::Cancelled => 'bg-slate-200 text-slate-700 ring-slate-300',
        };
    }

    public function presenterLabel(): string
    {
        return match ($this) {
            self::Draft => 'Belum Dikirim',
            self::Submitted => 'Menunggu Verifikasi',
            self::RejectedByVerifikator => 'Ditolak',
            self::ApprovedByVerifikator => 'Disetujui',
            self::TransferredToFinance => 'Dana Ditransfer ke Keuangan',
            self::ReceivedByFinance => 'Dana Diterima Keuangan',
            self::TransferredToPresenter => 'Sudah Ditransfer ke Presenter',
            self::Closed => 'Selesai / Closed',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function payoutLabel(): string
    {
        return match ($this) {
            self::Draft => 'Belum Diproses',
            self::Submitted => 'Dalam Verifikasi',
            self::RejectedByVerifikator => 'Ditolak',
            self::ApprovedByVerifikator => 'Disetujui',
            self::TransferredToFinance, self::ReceivedByFinance => 'Dana di Keuangan',
            self::TransferredToPresenter => 'Sudah Ditransfer',
            self::Closed => 'Closed',
            self::Cancelled => 'Ditolak',
        };
    }
}

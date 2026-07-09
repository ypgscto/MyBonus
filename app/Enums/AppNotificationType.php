<?php

namespace App\Enums;

enum AppNotificationType: string
{
    case RequestSubmitted = 'request_submitted';
    case RequestRejected = 'request_rejected';
    case RequestApproved = 'request_approved';
    case TransferredToFinance = 'transferred_to_finance';
    case ReceivedByFinance = 'received_by_finance';
    case TransferredToPresenter = 'transferred_to_presenter';
    case RequestClosed = 'request_closed';

    public function label(): string
    {
        return match ($this) {
            self::RequestSubmitted => 'Permintaan Baru',
            self::RequestRejected => 'Permintaan Ditolak',
            self::RequestApproved => 'Permintaan Disetujui',
            self::TransferredToFinance => 'Transfer ke Keuangan',
            self::ReceivedByFinance => 'Dana Diterima Keuangan',
            self::TransferredToPresenter => 'Transfer ke Presenter',
            self::RequestClosed => 'Permintaan Selesai',
        };
    }
}

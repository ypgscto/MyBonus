<?php

namespace App\Enums;

enum AuditAction: string
{
    case Login = 'login';
    case Logout = 'logout';
    case UserCreated = 'user_created';
    case UserUpdated = 'user_updated';
    case UserDeactivated = 'user_deactivated';
    case UserPasswordReset = 'user_password_reset';
    case PresenterCategoryCreated = 'presenter_category_created';
    case PresenterCategoryUpdated = 'presenter_category_updated';
    case PresenterCategoryDeactivated = 'presenter_category_deactivated';
    case PresenterCreated = 'presenter_created';
    case PresenterUpdated = 'presenter_updated';
    case PresenterDeactivated = 'presenter_deactivated';
    case PmbPeriodCreated = 'pmb_period_created';
    case CommissionSchemeCreated = 'commission_scheme_created';
    case DraftCreated = 'draft_created';
    case DraftUpdated = 'draft_updated';
    case RequestSubmitted = 'request_submitted';
    case DuplicateNimFailed = 'duplicate_nim_validation_failed';
    case RequestRejectedByVerifikator = 'request_rejected_by_verifikator';
    case RequestApprovedByVerifikator = 'request_approved_by_verifikator';
    case TransferredToFinance = 'transferred_to_finance';
    case ReceivedByFinance = 'received_by_finance';
    case TransferredToPresenter = 'transferred_to_presenter';
    case RequestClosed = 'request_closed';
    case WhatsappNotificationSent = 'whatsapp_notification_sent';
    case WhatsappNotificationFailed = 'whatsapp_notification_failed';
    case PresenterAccountCreated = 'presenter_account_created';
    case PresenterAccountEmailSent = 'presenter_account_email_sent';
    case PresenterAccountEmailFailed = 'presenter_account_email_failed';
    case PresenterAccountEmailResent = 'presenter_account_email_resent';
    case PresenterPasswordChanged = 'presenter_password_changed';

    public function label(): string
    {
        return match ($this) {
            self::Login => 'Login',
            self::Logout => 'Logout',
            self::UserCreated => 'Tambah User',
            self::UserUpdated => 'Edit User',
            self::UserDeactivated => 'Nonaktif User',
            self::UserPasswordReset => 'Reset Password User',
            self::PresenterCategoryCreated => 'Tambah Kategori Presenter',
            self::PresenterCategoryUpdated => 'Edit Kategori Presenter',
            self::PresenterCategoryDeactivated => 'Nonaktif Kategori Presenter',
            self::PresenterCreated => 'Tambah Presenter',
            self::PresenterUpdated => 'Edit Presenter',
            self::PresenterDeactivated => 'Nonaktif Presenter',
            self::PmbPeriodCreated => 'Tambah Periode PMB',
            self::CommissionSchemeCreated => 'Tambah Skema Komisi',
            self::DraftCreated => 'Buat Draft Permintaan',
            self::DraftUpdated => 'Edit Draft Permintaan',
            self::RequestSubmitted => 'Submit Permintaan',
            self::DuplicateNimFailed => 'Validasi Duplicate NIM Gagal',
            self::RequestRejectedByVerifikator => 'Verifikator Menolak Permintaan',
            self::RequestApprovedByVerifikator => 'Verifikator Menyetujui Permintaan',
            self::TransferredToFinance => 'Verifikator Transfer ke Keuangan',
            self::ReceivedByFinance => 'Keuangan Konfirmasi Dana Diterima',
            self::TransferredToPresenter => 'Keuangan Transfer ke Presenter',
            self::RequestClosed => 'Close Permintaan',
            self::WhatsappNotificationSent => 'Notifikasi WA Berhasil',
            self::WhatsappNotificationFailed => 'Notifikasi WA Gagal',
            self::PresenterAccountCreated => 'Akun Presenter Dibuat Otomatis',
            self::PresenterAccountEmailSent => 'Email Akun Presenter Terkirim',
            self::PresenterAccountEmailFailed => 'Email Akun Presenter Gagal',
            self::PresenterAccountEmailResent => 'Kirim Ulang Email Akun Presenter',
            self::PresenterPasswordChanged => 'Presenter Ubah Password',
        };
    }

    public function moduleLabel(): string
    {
        return match ($this) {
            self::Login, self::Logout => 'auth',
            self::UserCreated, self::UserUpdated, self::UserDeactivated, self::UserPasswordReset => 'user',
            self::PresenterCategoryCreated, self::PresenterCategoryUpdated, self::PresenterCategoryDeactivated => 'presenter_category',
            self::PresenterCreated, self::PresenterUpdated, self::PresenterDeactivated,
            self::PresenterAccountCreated, self::PresenterAccountEmailSent, self::PresenterAccountEmailFailed,
            self::PresenterAccountEmailResent => 'presenter',
            self::PmbPeriodCreated => 'pmb_period',
            self::CommissionSchemeCreated => 'commission_scheme',
            self::DraftCreated, self::DraftUpdated, self::RequestSubmitted, self::DuplicateNimFailed,
            self::RequestRejectedByVerifikator, self::RequestApprovedByVerifikator, self::TransferredToFinance,
            self::ReceivedByFinance, self::TransferredToPresenter, self::RequestClosed,
            self::WhatsappNotificationSent, self::WhatsappNotificationFailed => 'presenter_request',
            self::PresenterPasswordChanged => 'auth',
        };
    }

    /**
     * @return list<self>
     */
    public static function forModule(string $module): array
    {
        return array_values(array_filter(self::cases(), fn (self $action) => $action->moduleLabel() === $module));
    }
}

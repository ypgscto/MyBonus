<?php

return [

    'super_admin' => [
        ['label' => 'Dashboard', 'route' => 'dashboard.super-admin', 'route_prefix' => 'dashboard.super-admin', 'icon' => 'dashboard'],
        ['label' => 'Kategori Presenter', 'route' => 'master.presenter-categories.index', 'route_prefix' => 'master.presenter-categories.', 'icon' => 'tags'],
        ['label' => 'Master Presenter', 'route' => 'master.presenters.index', 'route_prefix' => 'master.presenters.', 'icon' => 'presenter'],
        ['label' => 'Periode PMB', 'route' => 'master.pmb-periods.index', 'route_prefix' => 'master.pmb-periods.', 'icon' => 'calendar'],
        ['label' => 'Skema Komisi', 'route' => 'master.commission-schemes.index', 'route_prefix' => 'master.commission-schemes.', 'icon' => 'currency'],
        ['label' => 'Kelola User', 'route' => 'users.index', 'route_prefix' => 'users.', 'icon' => 'users'],
        [
            'type' => 'group',
            'label' => 'Permintaan Presenter',
            'icon' => 'document',
            'route_prefix' => 'presenter-requests.',
            'children' => [
                [
                    'label' => 'Buat Permintaan',
                    'route' => 'presenter-requests.create',
                    'routes' => ['presenter-requests.create'],
                    'icon' => 'clipboard-plus',
                ],
                [
                    'label' => 'Draft Permintaan',
                    'route' => 'presenter-requests.drafts',
                    'routes' => ['presenter-requests.drafts', 'presenter-requests.edit'],
                    'icon' => 'document-text',
                ],
                [
                    'label' => 'Semua Permintaan',
                    'route' => 'presenter-requests.index',
                    'routes' => ['presenter-requests.index', 'presenter-requests.show'],
                    'icon' => 'history',
                ],
            ],
        ],
        ['label' => 'Laporan', 'route' => 'reports.index', 'route_prefix' => 'reports.', 'icon' => 'chart'],
        ['label' => 'Audit Log', 'route' => 'admin.audit-logs.index', 'route_prefix' => 'admin.audit-logs.', 'icon' => 'journal'],
        ['label' => 'Notification Log', 'route' => 'admin.notification-logs.index', 'route_prefix' => 'admin.notification-logs.', 'icon' => 'bell'],
    ],

    'admin_pmb' => [
        ['label' => 'Dashboard', 'route' => 'dashboard.admin-pmb', 'route_prefix' => 'dashboard.admin-pmb', 'icon' => 'dashboard'],
        ['label' => 'Kategori Presenter', 'route' => 'master.presenter-categories.index', 'route_prefix' => 'master.presenter-categories.', 'icon' => 'tags'],
        ['label' => 'Master Presenter', 'route' => 'master.presenters.index', 'route_prefix' => 'master.presenters.', 'icon' => 'presenter'],
        ['label' => 'Periode PMB', 'route' => 'master.pmb-periods.index', 'route_prefix' => 'master.pmb-periods.', 'icon' => 'calendar'],
        ['label' => 'Skema Komisi', 'route' => 'master.commission-schemes.index', 'route_prefix' => 'master.commission-schemes.', 'icon' => 'currency'],
        [
            'type' => 'group',
            'label' => 'Permintaan Presenter',
            'icon' => 'document',
            'route_prefix' => 'presenter-requests.',
            'children' => [
                [
                    'label' => 'Buat Permintaan',
                    'route' => 'presenter-requests.create',
                    'routes' => ['presenter-requests.create'],
                    'icon' => 'clipboard-plus',
                ],
                [
                    'label' => 'Draft Permintaan',
                    'route' => 'presenter-requests.drafts',
                    'routes' => ['presenter-requests.drafts', 'presenter-requests.edit'],
                    'icon' => 'document-text',
                ],
                [
                    'label' => 'Riwayat Permintaan',
                    'route' => 'presenter-requests.history',
                    'routes' => ['presenter-requests.history', 'presenter-requests.show'],
                    'icon' => 'history',
                ],
            ],
        ],
        ['label' => 'Laporan', 'route' => 'reports.index', 'route_prefix' => 'reports.', 'icon' => 'chart'],
    ],

    'verifikator' => [
        ['label' => 'Dashboard', 'route' => 'dashboard.verifikator', 'route_prefix' => 'dashboard.verifikator', 'icon' => 'dashboard'],
        ['label' => 'Menunggu Verifikasi', 'route' => 'verifikator.requests.pending', 'route_prefix' => 'verifikator.requests.pending', 'icon' => 'inbox'],
        ['label' => 'Disetujui', 'route' => 'verifikator.requests.approved', 'route_prefix' => 'verifikator.requests.approved', 'icon' => 'check'],
        ['label' => 'Ditolak', 'route' => 'verifikator.requests.rejected', 'route_prefix' => 'verifikator.requests.rejected', 'icon' => 'x-circle'],
        ['label' => 'Transfer ke Keuangan', 'route' => 'verifikator.requests.to-transfer', 'route_prefix' => 'verifikator.requests.to-transfer', 'icon' => 'send'],
        ['label' => 'Riwayat Transfer', 'route' => 'verifikator.requests.transfer-history', 'route_prefix' => 'verifikator.requests.transfer-history', 'icon' => 'history'],
    ],

    'keuangan' => [
        ['label' => 'Dashboard', 'route' => 'dashboard.keuangan', 'route_prefix' => 'dashboard.keuangan', 'icon' => 'dashboard'],
        ['label' => 'Dana Masuk', 'route' => 'keuangan.requests.incoming', 'route_prefix' => 'keuangan.requests.incoming', 'icon' => 'inbox'],
        ['label' => 'Konfirmasi Dana', 'route' => 'keuangan.requests.received', 'route_prefix' => 'keuangan.requests.received', 'icon' => 'check'],
        ['label' => 'Transfer ke Presenter', 'route' => 'keuangan.requests.to-transfer', 'route_prefix' => 'keuangan.requests.to-transfer', 'icon' => 'send'],
        ['label' => 'Riwayat Pencairan', 'route' => 'keuangan.requests.disbursement-history', 'route_prefix' => 'keuangan.requests.disbursement-history', 'icon' => 'history'],
        ['label' => 'Permintaan Closed', 'route' => 'keuangan.requests.closed', 'route_prefix' => 'keuangan.requests.closed', 'icon' => 'lock'],
        ['label' => 'Laporan Keuangan', 'route' => 'presenter-requests.index', 'route_prefix' => 'presenter-requests.index', 'icon' => 'chart'],
    ],

    'presenter' => [
        ['label' => 'Dashboard', 'route' => 'presenter.dashboard', 'route_prefix' => 'presenter.dashboard', 'icon' => 'dashboard'],
        ['label' => 'Mahasiswa Saya', 'route' => 'presenter.students', 'route_prefix' => 'presenter.students', 'icon' => 'users'],
        ['label' => 'Permintaan Saya', 'route' => 'presenter.requests', 'route_prefix' => 'presenter.requests', 'icon' => 'document'],
        ['label' => 'Status Pencairan', 'route' => 'presenter.payouts', 'route_prefix' => 'presenter.payouts', 'icon' => 'currency'],
        ['label' => 'Profil Saya', 'route' => 'presenter.profile', 'route_prefix' => 'presenter.profile', 'icon' => 'presenter'],
        ['label' => 'Ubah Password', 'route' => 'presenter.change-password', 'route_prefix' => 'presenter.change-password', 'icon' => 'lock'],
    ],

];

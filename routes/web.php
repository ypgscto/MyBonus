<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\NotificationLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Keuangan\PresenterRequestController as KeuanganPresenterRequestController;
use App\Http\Controllers\Master\CommissionSchemeController;
use App\Http\Controllers\Master\PmbPeriodController;
use App\Http\Controllers\Master\PresenterCategoryController;
use App\Http\Controllers\Master\PresenterController;
use App\Http\Controllers\PresenterRequest\PresenterRequestController;
use App\Http\Controllers\PresenterRequest\PresenterRequestDetailController;
use App\Http\Controllers\Presenter\ChangePasswordController;
use App\Http\Controllers\Presenter\DashboardController as PresenterDashboardController;
use App\Http\Controllers\Presenter\PayoutController as PresenterPayoutController;
use App\Http\Controllers\Presenter\ProfileController as PresenterProfileController;
use App\Http\Controllers\Presenter\RequestController as PresenterPortalRequestController;
use App\Http\Controllers\Presenter\StudentController as PresenterStudentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\Verifikator\PresenterRequestController as VerifikatorPresenterRequestController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route(auth()->user()->role->dashboardRoute())
        : redirect()->route('login');
});

Route::middleware(['auth', 'active', 'must_change_password'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::middleware('role:super_admin')->prefix('super-admin')->name('dashboard.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'superAdmin'])->name('super-admin');
    });

    Route::middleware('role:admin_pmb')->prefix('admin-pmb')->name('dashboard.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'adminPmb'])->name('admin-pmb');
    });

    Route::middleware('role:verifikator')->group(function () {
        Route::get('/verifikator/dashboard', [VerifikatorPresenterRequestController::class, 'dashboard'])->name('dashboard.verifikator');

        Route::prefix('verifikator/requests')->name('verifikator.requests.')->group(function () {
            Route::get('/pending', [VerifikatorPresenterRequestController::class, 'pending'])->name('pending');
            Route::get('/approved', [VerifikatorPresenterRequestController::class, 'approved'])->name('approved');
            Route::get('/rejected', [VerifikatorPresenterRequestController::class, 'rejected'])->name('rejected');
            Route::get('/to-transfer', [VerifikatorPresenterRequestController::class, 'toTransfer'])->name('to-transfer');
            Route::get('/transfer-history', [VerifikatorPresenterRequestController::class, 'transferHistory'])->name('transfer-history');
            Route::get('/{presenter_request}', [VerifikatorPresenterRequestController::class, 'show'])->name('show');
            Route::post('/{presenter_request}/reject', [VerifikatorPresenterRequestController::class, 'reject'])->name('reject');
            Route::post('/{presenter_request}/approve', [VerifikatorPresenterRequestController::class, 'approve'])->name('approve');
            Route::post('/{presenter_request}/transfer', [VerifikatorPresenterRequestController::class, 'transfer'])->name('transfer');
            Route::get('/{presenter_request}/transfer-proof', [VerifikatorPresenterRequestController::class, 'downloadTransferProof'])->name('transfer-proof');
        });
    });

    Route::middleware('role:keuangan')->group(function () {
        Route::get('/keuangan/dashboard', [KeuanganPresenterRequestController::class, 'dashboard'])->name('dashboard.keuangan');

        Route::prefix('keuangan/requests')->name('keuangan.requests.')->group(function () {
            Route::get('/incoming', [KeuanganPresenterRequestController::class, 'incoming'])->name('incoming');
            Route::get('/received', [KeuanganPresenterRequestController::class, 'received'])->name('received');
            Route::get('/to-transfer', [KeuanganPresenterRequestController::class, 'toTransfer'])->name('to-transfer');
            Route::get('/closed', [KeuanganPresenterRequestController::class, 'closed'])->name('closed');
            Route::get('/disbursement-history', [KeuanganPresenterRequestController::class, 'disbursementHistory'])->name('disbursement-history');
            Route::get('/{presenter_request}/verifikator-proof', [KeuanganPresenterRequestController::class, 'downloadVerifikatorProof'])->name('verifikator-proof');
            Route::get('/{presenter_request}/presenter-proof', [KeuanganPresenterRequestController::class, 'downloadPresenterProof'])->name('presenter-proof');
            Route::get('/{presenter_request}', [KeuanganPresenterRequestController::class, 'show'])->name('show');
            Route::post('/{presenter_request}/confirm-received', [KeuanganPresenterRequestController::class, 'confirmReceived'])->name('confirm-received');
            Route::post('/{presenter_request}/transfer', [KeuanganPresenterRequestController::class, 'transfer'])->name('transfer');
            Route::post('/{presenter_request}/close', [KeuanganPresenterRequestController::class, 'close'])->name('close');
        });
    });

    Route::middleware('role:super_admin,admin_pmb')->prefix('master')->name('master.')->group(function () {
        Route::resource('presenter-categories', PresenterCategoryController::class)->except(['show', 'destroy']);
        Route::patch('presenter-categories/{presenter_category}/toggle-status', [PresenterCategoryController::class, 'toggleStatus'])
            ->name('presenter-categories.toggle-status');

        Route::resource('presenters', PresenterController::class)->except(['show', 'destroy']);
        Route::patch('presenters/{presenter}/toggle-status', [PresenterController::class, 'toggleStatus'])
            ->name('presenters.toggle-status');
        Route::post('presenters/{presenter}/resend-account-email', [PresenterController::class, 'resendAccountEmail'])
            ->name('presenters.resend-account-email');

        Route::resource('pmb-periods', PmbPeriodController::class)->except(['show', 'destroy']);
        Route::patch('pmb-periods/{pmb_period}/toggle-status', [PmbPeriodController::class, 'toggleStatus'])
            ->name('pmb-periods.toggle-status');

        Route::resource('commission-schemes', CommissionSchemeController::class)->except(['show', 'destroy']);
        Route::patch('commission-schemes/{commission_scheme}/toggle-status', [CommissionSchemeController::class, 'toggleStatus'])
            ->name('commission-schemes.toggle-status');
    });

    Route::middleware('role:super_admin,admin_pmb')->group(function () {
        Route::get('presenter-requests/create', [PresenterRequestController::class, 'create'])->name('presenter-requests.create');
        Route::post('presenter-requests', [PresenterRequestController::class, 'storeDraft'])->name('presenter-requests.store');
        Route::get('presenter-requests/drafts', [PresenterRequestController::class, 'drafts'])->name('presenter-requests.drafts');
        Route::get('presenter-requests/history', [PresenterRequestController::class, 'history'])->name('presenter-requests.history');
        Route::get('presenter-requests/presenters/{presenter}/info', [PresenterRequestController::class, 'presenterInfo'])->name('presenter-requests.presenter-info');
        Route::get('presenter-requests/{presenter_request}/check-nim', [PresenterRequestController::class, 'checkNim'])->name('presenter-requests.check-nim');
        Route::get('presenter-requests/{presenter_request}/commission-preview', [PresenterRequestController::class, 'commissionPreview'])->name('presenter-requests.commission-preview');
        Route::get('presenter-requests/{presenter_request}/edit', [PresenterRequestController::class, 'edit'])->name('presenter-requests.edit');
        Route::put('presenter-requests/{presenter_request}', [PresenterRequestController::class, 'update'])->name('presenter-requests.update');
        Route::post('presenter-requests/{presenter_request}/submit', [PresenterRequestController::class, 'submit'])->name('presenter-requests.submit');
        Route::post('presenter-requests/{presenter_request}/details', [PresenterRequestDetailController::class, 'store'])->name('presenter-requests.details.store');
        Route::put('presenter-requests/{presenter_request}/details/{detail}', [PresenterRequestDetailController::class, 'update'])->name('presenter-requests.details.update');
        Route::delete('presenter-requests/{presenter_request}/details/{detail}', [PresenterRequestDetailController::class, 'destroy'])->name('presenter-requests.details.destroy');
    });

    Route::middleware('role:super_admin,admin_pmb,verifikator,keuangan')->group(function () {
        Route::get('presenter-requests', [PresenterRequestController::class, 'index'])->name('presenter-requests.index');
        Route::get('presenter-requests/{presenter_request}', [PresenterRequestController::class, 'show'])->name('presenter-requests.show');
    });

    Route::middleware('role:super_admin')->group(function () {
        Route::get('/admin/audit-logs', [AuditLogController::class, 'index'])->name('admin.audit-logs.index');
        Route::get('/admin/notification-logs', [NotificationLogController::class, 'index'])->name('admin.notification-logs.index');
        Route::post('/admin/notification-logs/{notification_log}/resend', [NotificationLogController::class, 'resend'])
            ->name('admin.notification-logs.resend');

        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserManagementController::class, 'create'])->name('users.create');
        Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
        Route::get('/users/{user}', [UserManagementController::class, 'show'])->name('users.show');
        Route::get('/users/{user}/edit', [UserManagementController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
        Route::patch('/users/{user}/toggle-status', [UserManagementController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::post('/users/{user}/reset-password', [UserManagementController::class, 'resetPassword'])->name('users.reset-password');
    });

    Route::middleware('role:super_admin,admin_pmb')->prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::post('/export/excel', [ReportController::class, 'exportExcel'])->name('export.excel');
        Route::post('/export/pdf', [ReportController::class, 'exportPdf'])->name('export.pdf');
    });

    Route::middleware('role:admin_pmb,verifikator,keuangan,super_admin')->group(function () {
        Route::get('payment-proofs/{detail}/download', [PresenterRequestDetailController::class, 'download'])->name('payment-proofs.download');
        Route::get('presenter-transfer-proofs/{presenter_request}/download', [PresenterRequestController::class, 'downloadPresenterTransferProof'])
            ->name('presenter-transfer-proofs.download');
    });

    Route::middleware('role:presenter')->prefix('presenter')->name('presenter.')->group(function () {
        Route::get('/dashboard', PresenterDashboardController::class)->name('dashboard');
        Route::get('/students', [PresenterStudentController::class, 'index'])->name('students');
        Route::get('/requests', [PresenterPortalRequestController::class, 'index'])->name('requests');
        Route::get('/requests/{presenter_request}', [PresenterPortalRequestController::class, 'show'])->name('requests.show');
        Route::get('/payouts', [PresenterPayoutController::class, 'index'])->name('payouts');
        Route::get('/payouts/{presenter_request}/proof', [PresenterPayoutController::class, 'downloadProof'])->name('payouts.proof');
        Route::get('/profile', PresenterProfileController::class)->name('profile');
        Route::get('/change-password', [ChangePasswordController::class, 'edit'])->name('change-password');
        Route::post('/change-password', [ChangePasswordController::class, 'update'])->name('change-password.update');
    });
});

require __DIR__.'/auth.php';

<?php

use App\Http\Controllers\Api\V1\Admin\AuditLogController;
use App\Http\Controllers\Api\V1\Admin\PresenterRequestController as AdminPresenterRequestController;
use App\Http\Controllers\Api\V1\Admin\PresenterRequestDetailController as AdminPresenterRequestDetailController;
use App\Http\Controllers\Api\V1\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Api\V1\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\V1\Admin\WhatsappLogController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\Keuangan\RequestController as KeuanganRequestController;
use App\Http\Controllers\Api\V1\LookupController;
use App\Http\Controllers\Api\V1\Master\CommissionSchemeController;
use App\Http\Controllers\Api\V1\Master\PmbPeriodController;
use App\Http\Controllers\Api\V1\Master\PresenterCategoryController;
use App\Http\Controllers\Api\V1\Master\PresenterController as MasterPresenterController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\Presenter\DashboardController as PresenterDashboardController;
use App\Http\Controllers\Api\V1\Presenter\PayoutController as PresenterPayoutController;
use App\Http\Controllers\Api\V1\Presenter\ProfileController as PresenterProfileController;
use App\Http\Controllers\Api\V1\Presenter\RequestController as PresenterRequestController;
use App\Http\Controllers\Api\V1\Presenter\StudentController as PresenterStudentController;
use App\Http\Controllers\Api\V1\Verifikator\RequestController as VerifikatorRequestController;
use Illuminate\Support\Facades\Route;

$apiVersion = config('api.version', 'v1');

Route::prefix($apiVersion)
    ->name("api.{$apiVersion}.")
    ->group(function () {
        Route::get('/health', HealthController::class)->name('health');

        Route::middleware(['throttle:'.config('api.rate_limit.login', 10).',1'])
            ->post('/auth/login', [AuthController::class, 'login'])
            ->name('auth.login');

        Route::middleware([
            'auth:sanctum',
            'active',
            'must_change_password',
            'throttle:'.config('api.rate_limit.authenticated', 120).',1',
        ])->group(function () {
            Route::prefix('auth')->name('auth.')->group(function () {
                Route::get('/me', [AuthController::class, 'me'])->name('me');
                Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
                Route::post('/change-password', [AuthController::class, 'changePassword'])->name('change-password');
            });

            Route::get('/dashboard', DashboardController::class)->name('dashboard');

            Route::prefix('notifications')->name('notifications.')->group(function () {
                Route::get('/', [NotificationController::class, 'index'])->name('index');
                Route::get('/unread-count', [NotificationController::class, 'unreadCount'])->name('unread-count');
                Route::post('/device-token', [NotificationController::class, 'registerDevice'])->name('device.register');
                Route::delete('/device-token', [NotificationController::class, 'unregisterDevice'])->name('device.unregister');
                Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');
                Route::post('/{notification}/read', [NotificationController::class, 'markAsRead'])->name('read');
            });

            Route::prefix('lookups')->name('lookups.')->group(function () {
                Route::get('/request-statuses', [LookupController::class, 'requestStatuses'])->name('request-statuses');
                Route::get('/pmb-periods', [LookupController::class, 'pmbPeriods'])->name('pmb-periods');
                Route::get('/presenters', [LookupController::class, 'presenters'])->name('presenters');
            });

            Route::get('/presenter-request-details/{detail}/payment-proof', [AdminPresenterRequestDetailController::class, 'downloadPaymentProof'])
                ->name('presenter-request-details.payment-proof');

            Route::middleware('role:presenter')->prefix('presenter')->name('presenter.')->group(function () {
                Route::get('/dashboard', PresenterDashboardController::class)->name('dashboard');
                Route::get('/profile', PresenterProfileController::class)->name('profile');
                Route::get('/students', [PresenterStudentController::class, 'index'])->name('students.index');
                Route::get('/requests', [PresenterRequestController::class, 'index'])->name('requests.index');
                Route::get('/requests/{presenterRequest}', [PresenterRequestController::class, 'show'])->name('requests.show');
                Route::get('/payouts', [PresenterPayoutController::class, 'index'])->name('payouts.index');
                Route::get('/payouts/{presenterRequest}/proof', [PresenterPayoutController::class, 'downloadProof'])->name('payouts.proof');
            });

            Route::middleware('role:verifikator')->prefix('verifikator')->name('verifikator.')->group(function () {
                Route::get('/requests', [VerifikatorRequestController::class, 'index'])->name('requests.index');
                Route::get('/requests/transfer-history', [VerifikatorRequestController::class, 'transferHistory'])->name('requests.transfer-history');
                Route::get('/requests/{presenterRequest}', [VerifikatorRequestController::class, 'show'])->name('requests.show');
                Route::get('/requests/{presenterRequest}/bank-transfer-note', [VerifikatorRequestController::class, 'bankTransferNote'])->name('requests.bank-transfer-note');
                Route::post('/requests/{presenterRequest}/approve', [VerifikatorRequestController::class, 'approve'])->name('requests.approve');
                Route::post('/requests/{presenterRequest}/reject', [VerifikatorRequestController::class, 'reject'])->name('requests.reject');
                Route::post('/requests/{presenterRequest}/transfer', [VerifikatorRequestController::class, 'transfer'])->name('requests.transfer');
                Route::get('/requests/{presenterRequest}/transfer-proof', [VerifikatorRequestController::class, 'downloadTransferProof'])->name('requests.transfer-proof');
                Route::get('/finance-users', [VerifikatorRequestController::class, 'financeUsers'])->name('finance-users');
            });

            Route::middleware('role:keuangan')->prefix('keuangan')->name('keuangan.')->group(function () {
                Route::get('/requests', [KeuanganRequestController::class, 'index'])->name('requests.index');
                Route::get('/requests/disbursement-history', [KeuanganRequestController::class, 'disbursementHistory'])->name('requests.disbursement-history');
                Route::get('/requests/{presenterRequest}', [KeuanganRequestController::class, 'show'])->name('requests.show');
                Route::get('/requests/{presenterRequest}/bank-transfer-note', [KeuanganRequestController::class, 'bankTransferNote'])->name('requests.bank-transfer-note');
                Route::post('/requests/{presenterRequest}/confirm-received', [KeuanganRequestController::class, 'confirmReceived'])->name('requests.confirm-received');
                Route::post('/requests/{presenterRequest}/transfer', [KeuanganRequestController::class, 'transfer'])->name('requests.transfer');
                Route::post('/requests/{presenterRequest}/close', [KeuanganRequestController::class, 'close'])->name('requests.close');
                Route::get('/requests/{presenterRequest}/verifikator-proof', [KeuanganRequestController::class, 'downloadVerifikatorProof'])->name('requests.verifikator-proof');
                Route::get('/requests/{presenterRequest}/presenter-proof', [KeuanganRequestController::class, 'downloadPresenterProof'])->name('requests.presenter-proof');
            });

            Route::middleware('role:super_admin,admin_pmb')->prefix('admin/presenter-requests')->name('admin.presenter-requests.')->group(function () {
                Route::get('/', [AdminPresenterRequestController::class, 'index'])->name('index');
                Route::get('/drafts', [AdminPresenterRequestController::class, 'drafts'])->name('drafts');
                Route::get('/history', [AdminPresenterRequestController::class, 'history'])->name('history');
                Route::post('/', [AdminPresenterRequestController::class, 'store'])->name('store');
                Route::get('/presenters/{presenter}/info', [AdminPresenterRequestController::class, 'presenterInfo'])->name('presenter-info');
                Route::get('/details/{detail}/payment-proof', [AdminPresenterRequestDetailController::class, 'downloadPaymentProof'])->name('details.payment-proof');
                Route::get('/{presenterRequest}', [AdminPresenterRequestController::class, 'show'])->name('show');
                Route::put('/{presenterRequest}', [AdminPresenterRequestController::class, 'update'])->name('update');
                Route::post('/{presenterRequest}/submit', [AdminPresenterRequestController::class, 'submit'])->name('submit');
                Route::get('/{presenterRequest}/check-nim', [AdminPresenterRequestController::class, 'checkNim'])->name('check-nim');
                Route::get('/{presenterRequest}/commission-preview', [AdminPresenterRequestController::class, 'commissionPreview'])->name('commission-preview');
                Route::post('/{presenterRequest}/details', [AdminPresenterRequestDetailController::class, 'store'])->name('details.store');
                Route::put('/{presenterRequest}/details/{detail}', [AdminPresenterRequestDetailController::class, 'update'])->name('details.update');
                Route::delete('/{presenterRequest}/details/{detail}', [AdminPresenterRequestDetailController::class, 'destroy'])->name('details.destroy');
            });

            Route::middleware('role:super_admin,admin_pmb')->prefix('master')->name('master.')->group(function () {
                Route::apiResource('presenter-categories', PresenterCategoryController::class)->except(['destroy']);
                Route::post('presenter-categories/{presenterCategory}/toggle-status', [PresenterCategoryController::class, 'toggleStatus'])->name('presenter-categories.toggle-status');

                Route::apiResource('presenters', MasterPresenterController::class)->except(['destroy']);
                Route::post('presenters/{presenter}/toggle-status', [MasterPresenterController::class, 'toggleStatus'])->name('presenters.toggle-status');
                Route::post('presenters/{presenter}/resend-account-email', [MasterPresenterController::class, 'resendAccountEmail'])->name('presenters.resend-account-email');

                Route::apiResource('pmb-periods', PmbPeriodController::class)->except(['destroy']);
                Route::post('pmb-periods/{pmbPeriod}/toggle-status', [PmbPeriodController::class, 'toggleStatus'])->name('pmb-periods.toggle-status');

                Route::apiResource('commission-schemes', CommissionSchemeController::class)->except(['destroy']);
                Route::post('commission-schemes/{commissionScheme}/toggle-status', [CommissionSchemeController::class, 'toggleStatus'])->name('commission-schemes.toggle-status');
            });

            Route::middleware('role:super_admin,admin_pmb')->prefix('reports')->name('reports.')->group(function () {
                Route::get('/', [AdminReportController::class, 'index'])->name('index');
            });

            Route::middleware('role:super_admin')->prefix('admin')->name('admin.')->group(function () {
                Route::apiResource('users', AdminUserController::class)->except(['destroy']);
                Route::post('users/{user}/toggle-status', [AdminUserController::class, 'toggleStatus'])->name('users.toggle-status');
                Route::post('users/{user}/reset-password', [AdminUserController::class, 'resetPassword'])->name('users.reset-password');
                Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
                Route::get('whatsapp-logs', [WhatsappLogController::class, 'index'])->name('whatsapp-logs.index');
            });
        });
    });

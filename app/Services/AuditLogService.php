<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\PresenterRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogService
{
    public function log(
        AuditAction $action,
        string $module,
        ?int $referenceId = null,
        ?array $oldValue = null,
        ?array $newValue = null,
        ?int $userId = null,
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $userId ?? Auth::id(),
            'action' => $action->value,
            'module' => $module,
            'reference_id' => $referenceId,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }

    public function logLogin(User $user): AuditLog
    {
        return $this->log(AuditAction::Login, 'auth', $user->id, null, [
            'email' => $user->email,
            'role' => $user->role->value,
        ], $user->id);
    }

    public function logLogout(User $user): AuditLog
    {
        return $this->log(AuditAction::Logout, 'auth', $user->id, null, [
            'email' => $user->email,
        ], $user->id);
    }

    public function logUserCreated(User $user): AuditLog
    {
        return $this->log(AuditAction::UserCreated, 'user', $user->id, null, $user->toArray());
    }

    public function logUserUpdated(User $user, array $oldAttributes): AuditLog
    {
        return $this->log(AuditAction::UserUpdated, 'user', $user->id, $oldAttributes, $user->toArray());
    }

    public function logUserDeactivated(User $user, array $oldAttributes): AuditLog
    {
        return $this->log(AuditAction::UserDeactivated, 'user', $user->id, $oldAttributes, $user->only(['status']));
    }

    public function logUserPasswordReset(User $user): AuditLog
    {
        return $this->log(AuditAction::UserPasswordReset, 'user', $user->id, null, [
            'email' => $user->email,
            'name' => $user->name,
        ]);
    }

    public function logWhatsappNotificationSent(PresenterRequest $request, \App\Models\NotificationLog $log): AuditLog
    {
        return $this->log(AuditAction::WhatsappNotificationSent, 'presenter_request', $request->id, null, [
            'notification_log_id' => $log->id,
            'recipient_name' => $log->recipient_name,
            'recipient_phone' => $log->recipient_phone,
            'recipient_role' => $log->recipient_role,
        ]);
    }

    public function logWhatsappNotificationFailed(PresenterRequest $request, \App\Models\NotificationLog $log): AuditLog
    {
        return $this->log(AuditAction::WhatsappNotificationFailed, 'presenter_request', $request->id, null, [
            'notification_log_id' => $log->id,
            'recipient_name' => $log->recipient_name,
            'recipient_phone' => $log->recipient_phone,
            'recipient_role' => $log->recipient_role,
            'error' => $log->provider_response,
        ]);
    }

    public function logPresenterCategoryCreated(Model $category): AuditLog
    {
        return $this->log(AuditAction::PresenterCategoryCreated, 'presenter_category', $category->getKey(), null, $category->toArray());
    }

    public function logPresenterCategoryUpdated(Model $category, array $oldAttributes): AuditLog
    {
        return $this->log(AuditAction::PresenterCategoryUpdated, 'presenter_category', $category->getKey(), $oldAttributes, $category->toArray());
    }

    public function logPresenterCategoryDeactivated(Model $category, array $oldAttributes): AuditLog
    {
        return $this->log(AuditAction::PresenterCategoryDeactivated, 'presenter_category', $category->getKey(), $oldAttributes, $category->only(['status']));
    }

    public function logPresenterCreated(Model $presenter): AuditLog
    {
        return $this->log(AuditAction::PresenterCreated, 'presenter', $presenter->getKey(), null, $presenter->toArray());
    }

    public function logPresenterUpdated(Model $presenter, array $oldAttributes): AuditLog
    {
        return $this->log(AuditAction::PresenterUpdated, 'presenter', $presenter->getKey(), $oldAttributes, $presenter->toArray());
    }

    public function logPresenterDeactivated(Model $presenter, array $oldAttributes): AuditLog
    {
        return $this->log(AuditAction::PresenterDeactivated, 'presenter', $presenter->getKey(), $oldAttributes, $presenter->only(['status']));
    }

    public function logPresenterAccountCreated(Model $presenter, User $user): AuditLog
    {
        return $this->log(AuditAction::PresenterAccountCreated, 'presenter', $presenter->getKey(), null, [
            'presenter_id' => $presenter->getKey(),
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
    }

    public function logPresenterAccountEmailSent(Model $presenter): AuditLog
    {
        return $this->log(AuditAction::PresenterAccountEmailSent, 'presenter', $presenter->getKey(), null, [
            'email' => $presenter->email,
        ]);
    }

    public function logPresenterAccountEmailFailed(Model $presenter, string $error): AuditLog
    {
        return $this->log(AuditAction::PresenterAccountEmailFailed, 'presenter', $presenter->getKey(), null, [
            'email' => $presenter->email,
            'error' => $error,
        ]);
    }

    public function logPresenterAccountEmailResent(Model $presenter): AuditLog
    {
        return $this->log(AuditAction::PresenterAccountEmailResent, 'presenter', $presenter->getKey(), null, [
            'email' => $presenter->email,
        ]);
    }

    public function logPresenterPasswordChanged(User $user): AuditLog
    {
        return $this->log(AuditAction::PresenterPasswordChanged, 'auth', $user->id, null, [
            'email' => $user->email,
        ], $user->id);
    }

    public function logPmbPeriodCreated(Model $period): AuditLog
    {
        return $this->log(AuditAction::PmbPeriodCreated, 'pmb_period', $period->getKey(), null, $period->toArray());
    }

    public function logCommissionSchemeCreated(Model $scheme): AuditLog
    {
        return $this->log(AuditAction::CommissionSchemeCreated, 'commission_scheme', $scheme->getKey(), null, $scheme->toArray());
    }

    public function logDraftCreated(PresenterRequest $request): AuditLog
    {
        return $this->log(AuditAction::DraftCreated, 'presenter_request', $request->id, null, $request->toArray());
    }

    public function logDraftUpdated(PresenterRequest $request, array $oldAttributes, ?array $newValue = null): AuditLog
    {
        return $this->log(
            AuditAction::DraftUpdated,
            'presenter_request',
            $request->id,
            $oldAttributes,
            $newValue ?? $request->toArray()
        );
    }

    public function logRequestSubmitted(PresenterRequest $request, array $oldAttributes): AuditLog
    {
        return $this->log(AuditAction::RequestSubmitted, 'presenter_request', $request->id, $oldAttributes, $request->fresh()->toArray());
    }

    /**
     * @param  array<int, array<string, mixed>>  $report
     */
    public function logDuplicateNimFailed(PresenterRequest $request, array $report): AuditLog
    {
        return $this->log(AuditAction::DuplicateNimFailed, 'presenter_request', $request->id, null, [
            'request_code' => $request->request_code,
            'duplicate_report' => $report,
        ]);
    }

    public function logRequestRejectedByVerifikator(PresenterRequest $request, array $oldAttributes): AuditLog
    {
        return $this->log(AuditAction::RequestRejectedByVerifikator, 'presenter_request', $request->id, $oldAttributes, $request->fresh()->toArray());
    }

    public function logRequestApprovedByVerifikator(PresenterRequest $request, array $oldAttributes): AuditLog
    {
        return $this->log(AuditAction::RequestApprovedByVerifikator, 'presenter_request', $request->id, $oldAttributes, $request->fresh()->toArray());
    }

    public function logTransferredToFinance(PresenterRequest $request, array $oldAttributes): AuditLog
    {
        return $this->log(AuditAction::TransferredToFinance, 'presenter_request', $request->id, $oldAttributes, $request->fresh()->toArray());
    }

    public function logReceivedByFinance(PresenterRequest $request, array $oldAttributes): AuditLog
    {
        return $this->log(AuditAction::ReceivedByFinance, 'presenter_request', $request->id, $oldAttributes, $request->fresh()->toArray());
    }

    public function logTransferredToPresenter(PresenterRequest $request, array $oldAttributes): AuditLog
    {
        return $this->log(AuditAction::TransferredToPresenter, 'presenter_request', $request->id, $oldAttributes, $request->fresh()->toArray());
    }

    public function logRequestClosed(PresenterRequest $request, array $oldAttributes): AuditLog
    {
        return $this->log(AuditAction::RequestClosed, 'presenter_request', $request->id, $oldAttributes, $request->fresh()->toArray());
    }
}

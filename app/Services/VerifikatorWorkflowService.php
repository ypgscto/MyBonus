<?php

namespace App\Services;

use App\Enums\PresenterRequestStatus;
use App\Models\PresenterRequest;
use App\Models\User;
use App\Models\VerifikatorTransfer;
use App\Support\LocksPresenterRequestForWorkflow;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VerifikatorWorkflowService
{
    use LocksPresenterRequestForWorkflow;

    public function __construct(
        private readonly CommissionCalculationService $commissionCalculator,
        private readonly PresenterRequestNotificationService $notifications,
        private readonly AuditLogService $auditLog,
    ) {}

    public function reject(PresenterRequest $request, User $user, string $rejectionReason): WorkflowResult
    {
        if ($request->status !== PresenterRequestStatus::Submitted) {
            throw ValidationException::withMessages([
                'reject' => 'Hanya permintaan berstatus submitted yang dapat ditolak.',
            ]);
        }

        return DB::transaction(function () use ($request, $user, $rejectionReason) {
            $locked = $this->lockPresenterRequest($request->id);

            $this->assertRequestStatus(
                $locked,
                PresenterRequestStatus::Submitted,
                'reject',
                'Status permintaan telah berubah. Penolakan dibatalkan.',
            );

            $oldAttributes = $locked->toArray();

            $locked->update([
                'status' => PresenterRequestStatus::RejectedByVerifikator,
                'rejected_by' => $user->id,
                'rejected_at' => now(),
                'rejection_reason' => $rejectionReason,
            ]);

            $notifications = $this->notifications->notifyRejectedToAdmin($locked->fresh());
            $this->auditLog->logRequestRejectedByVerifikator($locked->fresh(), $oldAttributes);

            return new WorkflowResult($locked->fresh(), $notifications);
        });
    }

    public function approve(PresenterRequest $request, User $user, ?string $verifikatorNote = null): WorkflowResult
    {
        if ($request->status !== PresenterRequestStatus::Submitted) {
            throw ValidationException::withMessages([
                'approve' => 'Hanya permintaan berstatus submitted yang dapat disetujui.',
            ]);
        }

        $request->load('details');
        $commission = $this->commissionCalculator->calculate($request);

        return DB::transaction(function () use ($request, $user, $verifikatorNote, $commission) {
            $locked = $this->lockPresenterRequest($request->id);

            $this->assertRequestStatus(
                $locked,
                PresenterRequestStatus::Submitted,
                'approve',
                'Status permintaan telah berubah. Persetujuan dibatalkan.',
            );

            $oldAttributes = $locked->toArray();

            $locked->update([
                'status' => PresenterRequestStatus::ApprovedByVerifikator,
                'approved_by' => $user->id,
                'approved_at' => now(),
                'verifikator_note' => $verifikatorNote,
                'total_students' => $commission['total_students'],
                'commission_per_student' => $commission['commission_per_student'],
                'total_commission' => $commission['total_commission'],
            ]);

            $notifications = $this->notifications->notifyApprovedToAdmin($locked->fresh());
            $this->auditLog->logRequestApprovedByVerifikator($locked->fresh(), $oldAttributes);

            return new WorkflowResult($locked->fresh(), $notifications);
        });
    }

    /**
     * @param  array<string, mixed>  $transferData
     */
    public function transferToFinance(PresenterRequest $request, User $user, array $transferData): WorkflowResult
    {
        if ($request->status !== PresenterRequestStatus::ApprovedByVerifikator) {
            throw ValidationException::withMessages([
                'transfer' => 'Hanya permintaan berstatus approved_by_verifikator yang dapat ditransfer ke keuangan.',
            ]);
        }

        return DB::transaction(function () use ($request, $user, $transferData) {
            $locked = $this->lockPresenterRequest($request->id);

            $this->assertRequestStatus(
                $locked,
                PresenterRequestStatus::ApprovedByVerifikator,
                'transfer',
                'Status permintaan telah berubah. Transfer dibatalkan.',
            );

            $oldAttributes = $locked->toArray();

            VerifikatorTransfer::create([
                'presenter_request_id' => $locked->id,
                'transferred_by' => $user->id,
                'transfer_date' => $transferData['transfer_date'],
                'transfer_amount' => $transferData['transfer_amount'],
                'finance_user_id' => $transferData['finance_user_id'],
                'destination_bank' => $transferData['destination_bank'],
                'destination_account_number' => $transferData['destination_account_number'],
                'destination_account_name' => $transferData['destination_account_name'],
                'transfer_proof_file' => $transferData['transfer_proof_file'],
                'note' => $transferData['note'] ?? null,
            ]);

            $locked->update([
                'status' => PresenterRequestStatus::TransferredToFinance,
                'transferred_to_finance_by' => $user->id,
                'transferred_to_finance_at' => now(),
            ]);

            $notifications = $this->notifications->notifyTransferredToFinance($locked->fresh());
            $this->auditLog->logTransferredToFinance($locked->fresh(), $oldAttributes);

            return new WorkflowResult($locked->fresh(), $notifications);
        });
    }
}

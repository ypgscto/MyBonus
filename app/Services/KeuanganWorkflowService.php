<?php

namespace App\Services;

use App\Enums\PresenterRequestStatus;
use App\Models\PresenterRequest;
use App\Models\PresenterTransfer;
use App\Models\User;
use App\Support\LocksPresenterRequestForWorkflow;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KeuanganWorkflowService
{
    use LocksPresenterRequestForWorkflow;

    public function __construct(
        private readonly PresenterRequestNotificationService $notifications,
        private readonly AuditLogService $auditLog,
    ) {}

    public function confirmReceived(PresenterRequest $request, User $user): WorkflowResult
    {
        if ($request->status !== PresenterRequestStatus::TransferredToFinance) {
            throw ValidationException::withMessages([
                'confirm' => 'Hanya permintaan berstatus transferred_to_finance yang dapat dikonfirmasi.',
            ]);
        }

        return DB::transaction(function () use ($request, $user) {
            $locked = $this->lockPresenterRequest($request->id);

            $this->assertRequestStatus(
                $locked,
                PresenterRequestStatus::TransferredToFinance,
                'confirm',
                'Status permintaan telah berubah. Konfirmasi dibatalkan.',
            );

            $oldAttributes = $locked->toArray();

            $locked->update([
                'status' => PresenterRequestStatus::ReceivedByFinance,
                'received_by_finance_by' => $user->id,
                'received_by_finance_at' => now(),
            ]);

            $notifications = $this->notifications->notifyFinanceReceivedToVerifikator($locked->fresh());
            $this->auditLog->logReceivedByFinance($locked->fresh(), $oldAttributes);

            return new WorkflowResult($locked->fresh(), $notifications);
        });
    }

    /**
     * @param  array<string, mixed>  $transferData
     */
    public function transferToPresenter(PresenterRequest $request, User $user, array $transferData): WorkflowResult
    {
        if ($request->status !== PresenterRequestStatus::ReceivedByFinance) {
            throw ValidationException::withMessages([
                'transfer' => 'Hanya permintaan berstatus received_by_finance yang dapat ditransfer ke presenter.',
            ]);
        }

        return DB::transaction(function () use ($request, $user, $transferData) {
            $locked = $this->lockPresenterRequest($request->id);

            $this->assertRequestStatus(
                $locked,
                PresenterRequestStatus::ReceivedByFinance,
                'transfer',
                'Status permintaan telah berubah. Transfer dibatalkan.',
            );

            $oldAttributes = $locked->toArray();

            PresenterTransfer::create([
                'presenter_request_id' => $locked->id,
                'transferred_by' => $user->id,
                'transfer_date' => $transferData['transfer_date'],
                'transfer_amount' => $transferData['transfer_amount'],
                'presenter_id' => $locked->presenter_id,
                'destination_bank' => $transferData['destination_bank'],
                'destination_account_number' => $transferData['destination_account_number'],
                'destination_account_name' => $transferData['destination_account_name'],
                'transfer_proof_file' => $transferData['transfer_proof_file'],
                'note' => $transferData['note'] ?? null,
            ]);

            $locked->update([
                'status' => PresenterRequestStatus::TransferredToPresenter,
                'transferred_to_presenter_by' => $user->id,
                'transferred_to_presenter_at' => now(),
                'finance_note' => $transferData['finance_note'] ?? null,
            ]);

            $fresh = $locked->fresh();
            $this->auditLog->logTransferredToPresenter($fresh, $oldAttributes);

            $notifications = $this->notifications->notifyTransferredToPresenter($fresh);

            return new WorkflowResult($fresh, $notifications);
        });
    }

    public function close(PresenterRequest $request, User $user): WorkflowResult
    {
        if ($request->status !== PresenterRequestStatus::TransferredToPresenter) {
            throw ValidationException::withMessages([
                'close' => 'Hanya permintaan berstatus transferred_to_presenter yang dapat ditutup.',
            ]);
        }

        return DB::transaction(function () use ($request, $user) {
            $locked = $this->lockPresenterRequest($request->id);

            $this->assertRequestStatus(
                $locked,
                PresenterRequestStatus::TransferredToPresenter,
                'close',
                'Status permintaan telah berubah. Penutupan dibatalkan.',
            );

            $oldAttributes = $locked->toArray();

            $locked->update([
                'status' => PresenterRequestStatus::Closed,
                'closed_by' => $user->id,
                'closed_at' => now(),
            ]);

            $this->auditLog->logRequestClosed($locked->fresh(), $oldAttributes);
            $notifications = $this->notifications->notifyClosed($locked->fresh());

            return new WorkflowResult($locked->fresh(), $notifications);
        });
    }
}

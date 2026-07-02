<?php

namespace App\Services;

use App\Enums\PresenterRequestStatus;
use App\Enums\RecordStatus;
use App\Exceptions\DuplicateNimValidationException;
use App\Models\CommissionScheme;
use App\Models\PresenterRequest;
use App\Support\LocksPresenterRequestForWorkflow;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PresenterRequestSubmitService
{
    use LocksPresenterRequestForWorkflow;

    public function __construct(
        private readonly DuplicateNimValidatorService $duplicateNimValidator,
        private readonly PresenterRequestNotificationService $notifications,
        private readonly AuditLogService $auditLog,
    ) {}

    public function submit(PresenterRequest $request, int $userId): WorkflowResult
    {
        $request->refresh()->load(['details.presenterRequest', 'presenter', 'pmbPeriod']);

        if ($request->status !== PresenterRequestStatus::Draft) {
            throw ValidationException::withMessages([
                'submit' => 'Hanya permintaan draft yang dapat dikirim.',
            ]);
        }

        $this->validateForSubmit($request);

        return DB::transaction(function () use ($request, $userId) {
            $locked = $this->lockPresenterRequest($request->id);
            $locked->load(['details', 'presenter', 'pmbPeriod']);

            $this->assertRequestStatus(
                $locked,
                PresenterRequestStatus::Draft,
                'submit',
                'Status permintaan telah berubah. Submit dibatalkan.',
            );

            $scheme = $this->resolveCommissionScheme($locked);

            $totalStudents = $locked->details->count();
            $commissionPerStudent = $scheme->commission_amount_per_student;
            $oldAttributes = $locked->toArray();

            $locked->update([
                'status' => PresenterRequestStatus::Submitted,
                'submitted_by' => $userId,
                'submitted_at' => now(),
                'total_students' => $totalStudents,
                'commission_per_student' => $commissionPerStudent,
                'total_commission' => $totalStudents * $commissionPerStudent,
            ]);

            $this->auditLog->logRequestSubmitted($locked->fresh(), $oldAttributes);

            $notifications = $this->notifications->notifySubmittedToVerifikator($locked->fresh());

            return new WorkflowResult($locked->fresh(), $notifications);
        });
    }

    private function validateForSubmit(PresenterRequest $request): void
    {
        $errors = [];

        if ($request->presenter?->status !== RecordStatus::Aktif) {
            $errors['presenter_id'] = 'Presenter harus berstatus aktif.';
        }

        if ($request->pmbPeriod?->status !== RecordStatus::Aktif) {
            $errors['pmb_period_id'] = 'Periode PMB harus berstatus aktif.';
        }

        if ($request->details->isEmpty()) {
            $errors['details'] = 'Minimal harus ada 1 calon mahasiswa.';
        }

        foreach ($request->details as $index => $detail) {
            $row = $index + 1;

            if (empty($detail->nim)) {
                $errors["details.{$detail->id}.nim"] = "Baris {$row}: NIM wajib diisi.";
            }

            if (empty($detail->student_name)) {
                $errors["details.{$detail->id}.student_name"] = "Baris {$row}: Nama mahasiswa wajib diisi.";
            }

            if (! $detail->birth_date) {
                $errors["details.{$detail->id}.birth_date"] = "Baris {$row}: Tanggal lahir wajib diisi.";
            }

            if (! $detail->payment_date) {
                $errors["details.{$detail->id}.payment_date"] = "Baris {$row}: Tanggal bayar wajib diisi.";
            }

            if (! $detail->payment_proof_file) {
                $errors["details.{$detail->id}.payment_proof_file"] = "Baris {$row}: Bukti pembayaran wajib diupload.";
            }
        }

        try {
            $this->resolveCommissionScheme($request);
        } catch (ValidationException $e) {
            $errors = array_merge($errors, $e->errors());
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        $detailsForValidation = $request->details->each(function ($detail) use ($request) {
            $detail->setRelation('presenterRequest', $request);
        });

        $nimList = $request->details->pluck('nim')->filter()->values()->all();

        $withinIssues = $this->duplicateNimValidator->validateWithinCurrentRequest($detailsForValidation);
        $otherIssues = $this->duplicateNimValidator->validateAgainstOtherRequests($request->id, $nimList);
        $duplicateReport = array_merge($withinIssues, $otherIssues);

        if ($this->duplicateNimValidator->hasSubmitBlockingIssues($duplicateReport)) {
            throw new DuplicateNimValidationException($duplicateReport);
        }
    }

    private function resolveCommissionScheme(PresenterRequest $request): CommissionScheme
    {
        $scheme = CommissionScheme::query()
            ->where('presenter_category_id', $request->presenter?->presenter_category_id)
            ->where('pmb_period_id', $request->pmb_period_id)
            ->where('status', RecordStatus::Aktif)
            ->first();

        if (! $scheme) {
            throw ValidationException::withMessages([
                'commission' => 'Skema komisi aktif tidak ditemukan untuk kategori presenter dan periode PMB ini.',
            ]);
        }

        return $scheme;
    }
}

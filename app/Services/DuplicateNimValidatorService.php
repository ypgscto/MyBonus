<?php

namespace App\Services;

use App\Enums\PresenterRequestStatus;
use App\Models\PresenterRequestDetail;
use Illuminate\Support\Collection;

class DuplicateNimValidatorService
{
    /**
     * @param  Collection<int, \App\Models\PresenterRequestDetail>|array<int, \App\Models\PresenterRequestDetail|array<string, mixed>>  $requestDetails
     * @return array<int, array<string, mixed>>
     */
    public function validateWithinCurrentRequest(Collection|array $requestDetails): array
    {
        $details = collect($requestDetails)->map(function ($detail) {
            if ($detail instanceof PresenterRequestDetail) {
                return [
                    'id' => $detail->id,
                    'nim' => $detail->nim,
                    'student_name' => $detail->student_name,
                    'request_code' => $detail->presenterRequest?->request_code,
                ];
            }

            return [
                'id' => $detail['id'] ?? null,
                'nim' => $detail['nim'] ?? '',
                'student_name' => $detail['student_name'] ?? '',
                'request_code' => $detail['request_code'] ?? null,
            ];
        })->filter(fn (array $row) => filled($row['nim']));

        $report = [];

        $details->groupBy('nim')->each(function (Collection $group, string $nim) use (&$report) {
            if ($group->count() <= 1) {
                return;
            }

            foreach ($group as $item) {
                $report[] = [
                    'nim' => $nim,
                    'student_name' => $item['student_name'],
                    'previous_request_code' => $item['request_code'] ?? '-',
                    'previous_presenter_name' => '-',
                    'previous_status' => 'duplicate_within_request',
                    'previous_status_label' => 'Duplikat dalam permintaan ini',
                    'previous_request_date' => '-',
                    'issue_type' => 'within_request',
                    'detail_message' => "NIM {$nim} atas nama {$item['student_name']} muncul lebih dari sekali dalam permintaan ini.",
                ];
            }
        });

        return $report;
    }

    /**
     * NIM dianggap duplikat jika sudah dipakai di permintaan lain (status apa pun).
     * Pengecualian: detail pada presenter_request_id yang sedang diedit.
     *
     * @param  list<string>  $nimList
     * @return array<int, array<string, mixed>>
     */
    public function validateAgainstOtherRequests(int $requestId, array $nimList): array
    {
        $nimList = array_values(array_unique(array_filter($nimList)));

        if ($nimList === []) {
            return [];
        }

        return $this->findExternalConflicts($requestId, $nimList);
    }

    /**
     * @param  Collection<int, \App\Models\PresenterRequestDetail>|array<int, \App\Models\PresenterRequestDetail|array<string, mixed>>  $requestDetails
     * @param  list<string>  $nimList
     * @return array<int, array<string, mixed>>
     */
    public function getDuplicateReport(int $requestId, array $nimList, Collection|array $requestDetails): array
    {
        $within = $this->validateWithinCurrentRequest($requestDetails);
        $against = $this->validateAgainstOtherRequests($requestId, $nimList);

        return array_values(array_merge($within, $against));
    }

    /**
     * @param  list<string>  $nimList
     * @return array<int, array<string, mixed>>
     */
    public function getExternalConflictsOnly(int $requestId, array $nimList): array
    {
        return $this->validateAgainstOtherRequests($requestId, $nimList);
    }

    /** @deprecated Use getExternalConflictsOnly */
    public function getBlockingConflictsOnly(int $requestId, array $nimList): array
    {
        return $this->getExternalConflictsOnly($requestId, $nimList);
    }

    /** @deprecated All external conflicts are blocking under strict rules */
    public function getDraftWarningsOnly(int $requestId, array $nimList): array
    {
        return [];
    }

    public function hasSubmitBlockingIssues(array $report): bool
    {
        return collect($report)->isNotEmpty();
    }

    /**
     * @param  list<string>  $nimList
     * @return array<int, array<string, mixed>>
     */
    private function findExternalConflicts(int $requestId, array $nimList): array
    {
        return PresenterRequestDetail::query()
            ->whereIn('nim', $nimList)
            ->whereHas('presenterRequest', fn ($query) => $query->where('id', '!=', $requestId))
            ->with(['presenterRequest.presenter'])
            ->get()
            ->map(fn (PresenterRequestDetail $detail) => $this->mapDetailToReportRow($detail))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function mapDetailToReportRow(PresenterRequestDetail $detail): array
    {
        $request = $detail->presenterRequest;
        $presenterName = $request?->presenter?->name ?? '-';
        $status = $request?->status;
        $statusValue = $status instanceof PresenterRequestStatus ? $status->value : (string) $status;
        $statusLabel = $status instanceof PresenterRequestStatus ? $status->label() : $statusValue;

        return [
            'nim' => $detail->nim,
            'student_name' => $detail->student_name,
            'previous_request_code' => $request?->request_code ?? '-',
            'previous_presenter_name' => $presenterName,
            'previous_status' => $statusValue,
            'previous_status_label' => $statusLabel,
            'previous_request_date' => $request?->request_date?->format('d M Y') ?? '-',
            'issue_type' => 'external_duplicate',
            'detail_message' => "NIM {$detail->nim} atas nama {$detail->student_name} sudah digunakan pada permintaan {$request?->request_code} milik presenter {$presenterName} (status: {$statusLabel}).",
        ];
    }
}

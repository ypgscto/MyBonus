<?php

namespace App\Http\Requests\PresenterRequest;

use App\Enums\RecordStatus;
use App\Services\DuplicateNimValidatorService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePresenterRequestWithDetailsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isSubmit = $this->input('action') === 'submit';

        return [
            'action' => ['required', Rule::in(['draft', 'submit'])],
            'pmb_period_id' => [
                'required',
                Rule::exists('pmb_periods', 'id')->where('status', RecordStatus::Aktif->value),
            ],
            'presenter_id' => [
                'required',
                Rule::exists('presenters', 'id')->where('status', RecordStatus::Aktif->value),
            ],
            'admin_note' => ['nullable', 'string'],
            'details' => [$isSubmit ? 'required' : 'nullable', 'array', $isSubmit ? 'min:1' : ''],
            'details.*.nim' => ['required_with:details', 'string', 'max:30'],
            'details.*.student_name' => ['required_with:details', 'string', 'max:255'],
            'details.*.birth_date' => [$isSubmit ? 'required' : 'nullable', 'date'],
            'details.*.payment_date' => [$isSubmit ? 'required' : 'nullable', 'date'],
            'details.*.payment_proof' => [$isSubmit ? 'required' : 'nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'details.*.note' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty() || $this->input('action') !== 'submit') {
                return;
            }

            $details = collect($this->input('details', []))->filter(fn ($row) => ! empty($row['nim']));
            $nims = $details->pluck('nim')->map(fn ($nim) => trim((string) $nim))->filter()->values()->all();

            if (count($nims) !== count(array_unique($nims))) {
                $validator->errors()->add('details', 'Terdapat NIM duplikat dalam daftar calon mahasiswa.');
            }

            $service = app(DuplicateNimValidatorService::class);
            $simulated = $details->map(fn ($row) => new \App\Models\PresenterRequestDetail([
                'nim' => trim((string) $row['nim']),
                'student_name' => $row['student_name'] ?? '-',
            ]));

            $within = $service->validateWithinCurrentRequest($simulated);
            if (! empty($within)) {
                $validator->errors()->add('details', 'Terdapat NIM duplikat dalam daftar calon mahasiswa.');
            }

            $blocking = $service->validateAgainstOtherRequests(0, $nims);
            if ($service->hasSubmitBlockingIssues($blocking)) {
                foreach ($blocking as $issue) {
                    $validator->errors()->add('details', $issue['detail_message']);
                }
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'action.required' => 'Aksi permintaan wajib dipilih.',
            'action.in' => 'Aksi permintaan tidak valid.',
            'pmb_period_id.required' => 'Periode PMB wajib dipilih.',
            'pmb_period_id.exists' => 'Periode PMB harus aktif.',
            'presenter_id.required' => 'Presenter wajib dipilih.',
            'presenter_id.exists' => 'Presenter harus aktif.',
            'details.required' => 'Minimal harus ada 1 calon mahasiswa untuk dikirim ke Verifikator.',
            'details.min' => 'Minimal harus ada 1 calon mahasiswa untuk dikirim ke Verifikator.',
            'details.*.nim.required_with' => 'NIM wajib diisi.',
            'details.*.student_name.required_with' => 'Nama mahasiswa wajib diisi.',
            'details.*.birth_date.required' => 'Tanggal lahir wajib diisi untuk setiap mahasiswa.',
            'details.*.payment_date.required' => 'Tanggal bayar wajib diisi untuk setiap mahasiswa.',
            'details.*.payment_proof.required' => 'Bukti pembayaran wajib diupload untuk setiap mahasiswa.',
            'details.*.payment_proof.mimes' => 'Bukti pembayaran harus berformat JPG, JPEG, PNG, atau PDF.',
            'details.*.payment_proof.max' => 'Ukuran bukti pembayaran maksimal 5 MB.',
        ];
    }
}

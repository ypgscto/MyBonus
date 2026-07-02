<?php

namespace App\Http\Requests\PresenterRequest;

use App\Services\DuplicateNimValidatorService;
use Illuminate\Foundation\Http\FormRequest;

class StorePresenterRequestDetailRequest extends FormRequest
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
        return [
            'nim' => ['required', 'string', 'max:30'],
            'student_name' => ['required', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'payment_date' => ['nullable', 'date'],
            'payment_proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'note' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $presenterRequest = $this->route('presenter_request');
            $nim = $this->input('nim');
            $service = app(DuplicateNimValidatorService::class);

            $existingDetails = $presenterRequest->details()
                ->get()
                ->each(fn ($detail) => $detail->setRelation('presenterRequest', $presenterRequest))
                ->push(new \App\Models\PresenterRequestDetail([
                    'nim' => $nim,
                    'student_name' => $this->input('student_name'),
                ]));

            $within = $service->validateWithinCurrentRequest($existingDetails);
            if (! empty($within)) {
                $validator->errors()->add('nim', 'NIM sudah ada dalam permintaan ini.');
            }

            $blocking = $service->getBlockingConflictsOnly($presenterRequest->id, [$nim]);
            if (! empty($blocking)) {
                $validator->errors()->add('nim', $blocking[0]['detail_message']);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nim.required' => 'NIM wajib diisi.',
            'student_name.required' => 'Nama mahasiswa wajib diisi.',
            'payment_proof.mimes' => 'Bukti pembayaran harus berformat JPG, JPEG, PNG, atau PDF.',
            'payment_proof.max' => 'Ukuran bukti pembayaran maksimal 5 MB.',
        ];
    }
}

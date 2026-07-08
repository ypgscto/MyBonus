<x-admin-layout title="Detail Permintaan">
@php
    $canConfirm = $request->status === \App\Enums\PresenterRequestStatus::TransferredToFinance;
    $canTransfer = $request->status === \App\Enums\PresenterRequestStatus::ReceivedByFinance;
    $canClose = $request->status === \App\Enums\PresenterRequestStatus::TransferredToPresenter;
    $presenter = $request->presenter;
    $inputClass = 'block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
@endphp

    <div class="mb-6">
        <a href="{{ route($backRoute) }}" class="text-sm text-indigo-600 hover:text-indigo-800">← Kembali</a>
        <div class="mt-2 flex flex-wrap items-start justify-between gap-2">
            <h2 class="text-xl font-semibold text-slate-900">{{ $request->request_code }}</h2>
            <x-request-status-badge :status="$request->status" />
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-6">
            <x-card header="Data Permintaan">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><span class="text-xs text-slate-500 block">Kode Permintaan</span><span class="text-sm font-semibold text-slate-900">{{ $request->request_code }}</span></div>
                    <div><span class="text-xs text-slate-500 block">Presenter</span><span class="text-sm text-slate-900">{{ $presenter?->name }}</span></div>
                    <div><span class="text-xs text-slate-500 block">Rekening Presenter</span>
                        <span class="text-sm text-slate-900">{{ $presenter?->bank_name }}<br>{{ $presenter?->account_number }}<br><span class="text-slate-600">a.n. {{ $presenter?->account_holder_name }}</span></span>
                    </div>
                    <div><span class="text-xs text-slate-500 block">Periode PMB</span><span class="text-sm text-slate-900">{{ $request->pmbPeriod?->academic_year }} – {{ $request->pmbPeriod?->wave }}</span></div>
                </div>
            </x-card>

            <x-card header="Komisi">
                <div class="grid gap-4 sm:grid-cols-3">
                    <div><span class="text-xs text-slate-500 block">Total Mahasiswa</span><span class="text-sm font-semibold text-slate-900">{{ $request->total_students }}</span></div>
                    <div><span class="text-xs text-slate-500 block">Komisi per Mahasiswa</span><span class="text-sm text-slate-900">Rp {{ number_format($request->commission_per_student, 0, ',', '.') }}</span></div>
                    <div><span class="text-xs text-slate-500 block">Total Komisi</span><span class="text-sm font-semibold text-indigo-600">Rp {{ number_format($request->total_commission, 0, ',', '.') }}</span></div>
                </div>
            </x-card>

            @if ($request->verifikatorTransfer)
                <x-card header="Transfer dari Verifikator">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><span class="text-xs text-slate-500 block">Nominal Transfer</span><span class="text-sm font-semibold text-slate-900">Rp {{ number_format($request->verifikatorTransfer->transfer_amount, 0, ',', '.') }}</span></div>
                        <div><span class="text-xs text-slate-500 block">Tanggal Transfer</span><span class="text-sm text-slate-900">{{ $request->verifikatorTransfer->transfer_date->format('d M Y') }}</span></div>
                        @if ($request->verifikator_note)
                            <div class="sm:col-span-2"><span class="text-xs text-slate-500 block">Catatan Verifikator</span><span class="text-sm text-slate-900">{{ $request->verifikator_note }}</span></div>
                        @endif
                        @if ($request->verifikatorTransfer->note)
                            <div class="sm:col-span-2"><span class="text-xs text-slate-500 block">Catatan Transfer Verifikator</span><span class="text-sm text-slate-900">{{ $request->verifikatorTransfer->note }}</span></div>
                        @endif
                        <div class="sm:col-span-2">
                            <span class="text-xs text-slate-500 block mb-1">Bukti Transfer Verifikator</span>
                            <a href="{{ route('keuangan.requests.verifikator-proof', $request) }}" target="_blank" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm text-slate-700 hover:bg-slate-50">
                                <x-icon name="document" class="h-4 w-4" /> Unduh Bukti
                            </a>
                        </div>
                    </div>
                </x-card>
            @endif

            @if ($request->presenterTransfer)
                <x-card header="Transfer ke Presenter">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><span class="text-xs text-slate-500 block">Nominal Transfer</span><span class="text-sm text-slate-900">Rp {{ number_format($request->presenterTransfer->transfer_amount, 0, ',', '.') }}</span></div>
                        <div><span class="text-xs text-slate-500 block">Tanggal Transfer</span><span class="text-sm text-slate-900">{{ $request->presenterTransfer->transfer_date->format('d M Y') }}</span></div>
                        <div><span class="text-xs text-slate-500 block">Bank Tujuan</span><span class="text-sm text-slate-900">{{ $request->presenterTransfer->destination_bank }}</span></div>
                        <div><span class="text-xs text-slate-500 block">Rekening Tujuan</span><span class="text-sm text-slate-900">{{ $request->presenterTransfer->destination_account_number }}<br><span class="text-slate-600">a.n. {{ $request->presenterTransfer->destination_account_name }}</span></span></div>
                        @if ($request->finance_note)
                            <div class="sm:col-span-2"><span class="text-xs text-slate-500 block">Catatan Keuangan</span><span class="text-sm text-slate-900">{{ $request->finance_note }}</span></div>
                        @endif
                        @if ($request->presenterTransfer->note)
                            <div class="sm:col-span-2"><span class="text-xs text-slate-500 block">Catatan Selisih</span><span class="text-sm text-slate-900">{{ $request->presenterTransfer->note }}</span></div>
                        @endif
                        <div class="sm:col-span-2">
                            <a href="{{ route('keuangan.requests.presenter-proof', $request) }}" target="_blank" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm text-slate-700 hover:bg-slate-50">
                                <x-icon name="document" class="h-4 w-4" /> Unduh Bukti Transfer ke Presenter
                            </a>
                        </div>
                    </div>
                </x-card>
            @endif

            <x-card header="Calon Mahasiswa ({{ $request->details->count() }})">
                <div class="overflow-x-auto -mx-5 -mb-5">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">#</th>
                                <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">NIM</th>
                                <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Nama</th>
                                <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Tgl Lahir</th>
                                <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Tgl Bayar</th>
                                <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Bukti</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            @foreach ($request->details as $detail)
                                <tr>
                                    <td class="px-5 py-3 text-sm text-slate-900">{{ $loop->iteration }}</td>
                                    <td class="px-5 py-3 text-sm text-slate-900">{{ $detail->nim }}</td>
                                    <td class="px-5 py-3 text-sm text-slate-900">{{ $detail->student_name }}</td>
                                    <td class="px-5 py-3 text-sm text-slate-600">{{ $detail->birth_date?->format('d/m/Y') ?? '-' }}</td>
                                    <td class="px-5 py-3 text-sm text-slate-600">{{ $detail->payment_date?->format('d/m/Y') ?? '-' }}</td>
                                    <td class="px-5 py-3 text-sm">
                                        @if ($detail->payment_proof_file)
                                            <a href="{{ route('payment-proofs.download', $detail) }}" target="_blank" class="inline-flex items-center rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm text-slate-700 hover:bg-slate-50" title="Unduh">
                                                <x-icon name="document" class="h-4 w-4" />
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>

            <x-request-bank-transfer-note :request="$request" />
        </div>

        <div class="space-y-6">
            @if ($canConfirm)
                <x-card header="Konfirmasi Dana Diterima">
                    <p class="text-sm text-slate-500 mb-4">Konfirmasi bahwa dana dari Verifikator telah diterima di bagian Keuangan.</p>
                    <form method="POST" action="{{ route('keuangan.requests.confirm-received', $request) }}">
                        @csrf
                        <button type="submit"
                                data-confirm="Konfirmasi dana telah diterima?"
                                data-confirm-title="Konfirmasi Dana"
                                class="w-full inline-flex items-center justify-center gap-1 rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                            <x-icon name="check" class="h-4 w-4" /> Konfirmasi Dana Diterima
                        </button>
                    </form>
                </x-card>
            @endif

            @if ($canTransfer)
                <x-card header="Transfer ke Presenter">
                    <form method="POST" action="{{ route('keuangan.requests.transfer', $request) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Tanggal Transfer <span class="text-red-600">*</span></label>
                                <input type="date" name="transfer_date" class="{{ $inputClass }} @error('transfer_date') border-red-500 @enderror"
                                       value="{{ old('transfer_date', now()->toDateString()) }}" required>
                                @error('transfer_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Nominal Transfer <span class="text-red-600">*</span></label>
                                <input type="number" name="transfer_amount" id="transferAmount" step="0.01" min="0"
                                       class="{{ $inputClass }} @error('transfer_amount') border-red-500 @enderror"
                                       value="{{ old('transfer_amount', $request->total_commission) }}" required>
                                @error('transfer_amount')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            @if ($presenter?->hasCompleteBankAccount())
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500 mb-3">Rekening Tujuan Presenter (dari Master Presenter)</p>
                                    <dl class="grid gap-2 text-sm">
                                        <div class="flex justify-between gap-4">
                                            <dt class="text-slate-500">Bank</dt>
                                            <dd class="font-medium text-slate-900 text-right">{{ $presenter->bank_name }}</dd>
                                        </div>
                                        <div class="flex justify-between items-center gap-4">
                                            <dt class="text-slate-500">Nomor Rekening</dt>
                                            <dd class="flex items-center gap-2 font-medium text-slate-900 text-right">
                                                <span>{{ $presenter->account_number }}</span>
                                                <x-copy-text-button :text="$presenter->account_number" class="shrink-0" />
                                            </dd>
                                        </div>
                                        <div class="flex justify-between gap-4">
                                            <dt class="text-slate-500">Atas Nama</dt>
                                            <dd class="font-medium text-slate-900 text-right">{{ $presenter->account_holder_name }}</dd>
                                        </div>
                                    </dl>
                                </div>
                            @else
                                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                                    Data rekening presenter belum lengkap di Master Presenter. Hubungi Admin PMB sebelum melakukan transfer.
                                </div>
                            @endif
                            @error('transfer')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Bukti Transfer <span class="text-red-600">*</span></label>
                                <input type="file" name="transfer_proof" class="{{ $inputClass }} @error('transfer_proof') border-red-500 @enderror"
                                       accept=".jpg,.jpeg,.png,.pdf" required>
                                <p class="mt-1 text-xs text-slate-500">JPG, JPEG, PNG, PDF. Maks. 5 MB.</p>
                                @error('transfer_proof')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Catatan Keuangan</label>
                                <textarea name="finance_note" class="{{ $inputClass }} @error('finance_note') border-red-500 @enderror" rows="2">{{ old('finance_note') }}</textarea>
                                @error('finance_note')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div id="noteField" class="hidden">
                                <label class="block text-sm font-medium text-slate-700 mb-1">Catatan Alasan Selisih <span class="text-red-600">*</span></label>
                                <textarea name="note" class="{{ $inputClass }} @error('note') border-red-500 @enderror" rows="2">{{ old('note') }}</textarea>
                                @error('note')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <button type="submit" @disabled(! $presenter?->hasCompleteBankAccount()) class="w-full inline-flex items-center justify-center gap-1 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">
                                <x-icon name="send" class="h-4 w-4" /> Transfer ke Presenter
                            </button>
                        </div>
                    </form>
                </x-card>
            @endif

            @if ($canClose)
                <x-card header="Close Permintaan">
                    <p class="text-sm text-slate-500 mb-4">Tutup permintaan setelah komisi berhasil ditransfer ke presenter. Permintaan tidak dapat diedit lagi.</p>
                    <form method="POST" action="{{ route('keuangan.requests.close', $request) }}">
                        @csrf
                        <button type="submit"
                                data-confirm="Tutup permintaan ini?"
                                data-confirm-title="Close Permintaan"
                                class="w-full inline-flex items-center justify-center gap-1 rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900">
                            <x-icon name="lock" class="h-4 w-4" /> Close Permintaan
                        </button>
                    </form>
                </x-card>
            @endif
        </div>
    </div>

    @if ($canTransfer)
    @push('scripts')
    <script>
    (function () {
        const totalCommission = {{ (float) $request->total_commission }};
        const amountInput = document.getElementById('transferAmount');
        const noteField = document.getElementById('noteField');
        const noteTextarea = noteField?.querySelector('textarea[name="note"]');

        function toggleNote() {
            const amount = parseFloat(amountInput.value) || 0;
            const differs = Math.abs(amount - totalCommission) > 0.009;
            if (differs) {
                noteField.classList.remove('hidden');
            } else {
                noteField.classList.add('hidden');
            }
            if (noteTextarea) {
                noteTextarea.required = differs;
            }
        }

        amountInput?.addEventListener('input', toggleNote);
        toggleNote();
    })();
    </script>
    @endpush
    @endif
</x-admin-layout>

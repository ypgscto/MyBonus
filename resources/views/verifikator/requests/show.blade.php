<x-admin-layout title="Detail Permintaan">
@php
    $commission = $request->status === \App\Enums\PresenterRequestStatus::Submitted
        ? $previewCommission
        : [
            'total_students' => $request->total_students,
            'commission_per_student' => $request->commission_per_student,
            'total_commission' => $request->total_commission,
        ];
    $canVerify = $request->status === \App\Enums\PresenterRequestStatus::Submitted;
    $canTransfer = $request->status === \App\Enums\PresenterRequestStatus::ApprovedByVerifikator;
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
                    <div><span class="text-xs text-slate-500 block">Tanggal Pengajuan</span><span class="text-sm text-slate-900">{{ $request->request_date->format('d M Y') }}</span></div>
                    <div><span class="text-xs text-slate-500 block">Periode PMB</span><span class="text-sm text-slate-900">{{ $request->pmbPeriod?->academic_year }} – {{ $request->pmbPeriod?->wave }}</span></div>
                    <div><span class="text-xs text-slate-500 block">Dikirim</span><span class="text-sm text-slate-900">{{ $request->submitted_at?->format('d M Y H:i') ?? '-' }}</span></div>
                    @if ($request->admin_note)
                        <div class="sm:col-span-2"><span class="text-xs text-slate-500 block">Catatan Admin</span><span class="text-sm text-slate-900">{{ $request->admin_note }}</span></div>
                    @endif
                    @if ($request->verifikator_note)
                        <div class="sm:col-span-2"><span class="text-xs text-slate-500 block">Catatan Verifikator</span><span class="text-sm text-slate-900">{{ $request->verifikator_note }}</span></div>
                    @endif
                    @if ($request->rejection_reason)
                        <div class="sm:col-span-2"><span class="text-xs text-slate-500 block">Alasan Penolakan</span><span class="text-sm text-red-600">{{ $request->rejection_reason }}</span></div>
                    @endif
                </div>
            </x-card>

            <x-card header="Komisi">
                @if ($commission)
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div><span class="text-xs text-slate-500 block">Total Mahasiswa</span><span class="text-sm font-semibold text-slate-900">{{ $commission['total_students'] }}</span></div>
                        <div><span class="text-xs text-slate-500 block">Komisi per Mahasiswa</span><span class="text-sm text-slate-900">Rp {{ number_format($commission['commission_per_student'], 0, ',', '.') }}</span></div>
                        <div><span class="text-xs text-slate-500 block">Total Komisi</span><span class="text-sm font-semibold text-indigo-600">Rp {{ number_format($commission['total_commission'], 0, ',', '.') }}</span></div>
                    </div>
                @else
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">Skema komisi aktif tidak ditemukan untuk kategori presenter dan periode PMB ini.</div>
                @endif
            </x-card>

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
                                <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Bukti Pembayaran</th>
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
        </div>

        <div class="space-y-6">
            <x-card header="Data Presenter">
                <dl class="space-y-3 text-sm">
                    <div><dt class="text-xs text-slate-500">Presenter</dt><dd class="text-slate-900">{{ $request->presenter?->name }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Kategori Presenter</dt><dd class="text-slate-900">{{ $request->presenter?->category?->name }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Rekening Presenter</dt><dd class="text-slate-900">{{ $request->presenter?->bank_name }}<br>{{ $request->presenter?->account_number }}<br><span class="text-slate-600">a.n. {{ $request->presenter?->account_holder_name }}</span></dd></div>
                    <div><dt class="text-xs text-slate-500">Nomor HP</dt><dd class="text-slate-900">{{ $request->presenter?->phone }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Email</dt><dd class="text-slate-900">{{ $request->presenter?->email ?? '-' }}</dd></div>
                </dl>
            </x-card>

            @if ($canVerify && $commission)
                <x-card header="Aksi Verifikasi">
                    <div class="space-y-2">
                        <button type="button" @click="$dispatch('open-modal', 'approve-modal')" class="w-full inline-flex items-center justify-center gap-1 rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                            <x-icon name="check" class="h-4 w-4" /> Setujui
                        </button>
                        <button type="button" @click="$dispatch('open-modal', 'reject-modal')" class="w-full inline-flex items-center justify-center gap-1 rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50">
                            <x-icon name="x-circle" class="h-4 w-4" /> Tolak
                        </button>
                    </div>
                </x-card>
            @endif

            @if ($request->verifikatorTransfer)
                <x-card header="Data Transfer ke Keuangan">
                    <dl class="space-y-3 text-sm">
                        <div><dt class="text-xs text-slate-500">Tanggal Transfer</dt><dd class="text-slate-900">{{ $request->verifikatorTransfer->transfer_date->format('d M Y') }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Nominal Transfer</dt><dd class="text-slate-900">Rp {{ number_format($request->verifikatorTransfer->transfer_amount, 0, ',', '.') }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Penerima Keuangan</dt><dd class="text-slate-900">{{ $request->verifikatorTransfer->financeUser?->name }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Bank Tujuan</dt><dd class="text-slate-900">{{ $request->verifikatorTransfer->destination_bank }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Rekening Tujuan</dt><dd class="text-slate-900">{{ $request->verifikatorTransfer->destination_account_number }}<br><span class="text-slate-600">a.n. {{ $request->verifikatorTransfer->destination_account_name }}</span></dd></div>
                        @if ($request->verifikatorTransfer->note)
                            <div><dt class="text-xs text-slate-500">Catatan</dt><dd class="text-slate-900">{{ $request->verifikatorTransfer->note }}</dd></div>
                        @endif
                        <div>
                            <dt class="text-xs text-slate-500 mb-1">Bukti Transfer</dt>
                            <dd>
                                <a href="{{ route('verifikator.requests.transfer-proof', $request) }}" target="_blank" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm text-slate-700 hover:bg-slate-50">
                                    <x-icon name="document" class="h-4 w-4" /> Unduh
                                </a>
                            </dd>
                        </div>
                    </dl>
                </x-card>
            @endif

            @if ($canTransfer)
                <x-card header="Transfer ke Keuangan">
                    <form method="POST" action="{{ route('verifikator.requests.transfer', $request) }}" enctype="multipart/form-data" id="transferForm">
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
                                <p class="mt-1 text-xs text-slate-500">Default: total komisi Rp {{ number_format($request->total_commission, 0, ',', '.') }}</p>
                                @error('transfer_amount')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">User Keuangan Penerima <span class="text-red-600">*</span></label>
                                <select name="finance_user_id" id="financeUserId" class="{{ $inputClass }} @error('finance_user_id') border-red-500 @enderror" required>
                                    <option value="">-- Pilih --</option>
                                    @foreach ($financeUsers as $user)
                                        <option value="{{ $user->id }}"
                                                data-bank="{{ $user->bank_name }}"
                                                data-account="{{ $user->account_number }}"
                                                data-holder="{{ $user->account_holder_name }}"
                                                @selected(old('finance_user_id') == $user->id)>{{ $user->name }}</option>
                                    @endforeach
                                </select>
                                @error('finance_user_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                @if ($financeUsers->isEmpty())
                                    <p class="mt-1 text-sm text-amber-600">Belum ada user keuangan dengan data rekening lengkap. Hubungi Super Admin.</p>
                                @endif
                            </div>
                            <div id="financeBankInfo" class="hidden rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-xs font-medium uppercase tracking-wide text-slate-500 mb-3">Rekening Tujuan (dari Master User)</p>
                                <dl class="grid gap-2 text-sm">
                                    <div class="flex justify-between gap-4">
                                        <dt class="text-slate-500">Bank</dt>
                                        <dd class="font-medium text-slate-900 text-right" id="financeBankName">-</dd>
                                    </div>
                                    <div class="flex justify-between items-center gap-4">
                                        <dt class="text-slate-500">Nomor Rekening</dt>
                                        <dd class="flex items-center gap-2 font-medium text-slate-900 text-right">
                                            <span id="financeAccountNumber">-</span>
                                            <x-copy-text-button target-id="financeAccountNumber" class="shrink-0" />
                                        </dd>
                                    </div>
                                    <div class="flex justify-between gap-4">
                                        <dt class="text-slate-500">Atas Nama</dt>
                                        <dd class="font-medium text-slate-900 text-right" id="financeAccountHolder">-</dd>
                                    </div>
                                </dl>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Bukti Transfer <span class="text-red-600">*</span></label>
                                <input type="file" name="transfer_proof" class="{{ $inputClass }} @error('transfer_proof') border-red-500 @enderror"
                                       accept=".jpg,.jpeg,.png,.pdf" required>
                                <p class="mt-1 text-xs text-slate-500">JPG, JPEG, PNG, PDF. Maks. 5 MB.</p>
                                @error('transfer_proof')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div id="noteField" class="hidden">
                                <label class="block text-sm font-medium text-slate-700 mb-1">Catatan Alasan Selisih <span class="text-red-600">*</span></label>
                                <textarea name="note" class="{{ $inputClass }} @error('note') border-red-500 @enderror" rows="2">{{ old('note') }}</textarea>
                                @error('note')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <button type="submit" class="w-full inline-flex items-center justify-center gap-1 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                                <x-icon name="send" class="h-4 w-4" /> Transfer ke Keuangan
                            </button>
                        </div>
                    </form>
                </x-card>
            @endif
        </div>
    </div>

    @if ($canVerify)
        <x-modal name="reject-modal" :show="$errors->has('rejection_reason')">
            <form method="POST" action="{{ route('verifikator.requests.reject', $request) }}" class="p-6">
                @csrf
                <h3 class="text-lg font-semibold text-slate-900 mb-4">Tolak Permintaan</h3>
                <label class="block text-sm font-medium text-slate-700 mb-1">Alasan Penolakan <span class="text-red-600">*</span></label>
                <textarea name="rejection_reason" class="{{ $inputClass }} @error('rejection_reason') border-red-500 @enderror"
                          rows="4" required minlength="10">{{ old('rejection_reason') }}</textarea>
                @error('rejection_reason')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="$dispatch('close-modal', 'reject-modal')" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Batal</button>
                    <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">Tolak Permintaan</button>
                </div>
            </form>
        </x-modal>

        <x-modal name="approve-modal">
            <form method="POST" action="{{ route('verifikator.requests.approve', $request) }}" class="p-6">
                @csrf
                <h3 class="text-lg font-semibold text-slate-900 mb-4">Setujui Permintaan</h3>
                <p class="text-sm text-slate-500 mb-4">Komisi akan dikunci: <strong class="text-slate-900">Rp {{ number_format($commission['total_commission'] ?? 0, 0, ',', '.') }}</strong></p>
                <label class="block text-sm font-medium text-slate-700 mb-1">Catatan Verifikator (opsional)</label>
                <textarea name="verifikator_note" class="{{ $inputClass }}" rows="3">{{ old('verifikator_note') }}</textarea>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="$dispatch('close-modal', 'approve-modal')" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Batal</button>
                    <button type="submit" class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">Setujui</button>
                </div>
            </form>
        </x-modal>
    @endif

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

        const financeSelect = document.getElementById('financeUserId');
        const bankInfo = document.getElementById('financeBankInfo');

        function updateFinanceBankInfo() {
            const option = financeSelect?.selectedOptions[0];
            if (!option || !option.value) {
                bankInfo?.classList.add('hidden');
                return;
            }
            document.getElementById('financeBankName').textContent = option.dataset.bank || '-';
            document.getElementById('financeAccountNumber').textContent = option.dataset.account || '-';
            document.getElementById('financeAccountHolder').textContent = option.dataset.holder || '-';
            bankInfo?.classList.remove('hidden');
        }

        financeSelect?.addEventListener('change', updateFinanceBankInfo);
        updateFinanceBankInfo();
    })();
    </script>
    @endpush
    @endif
</x-admin-layout>

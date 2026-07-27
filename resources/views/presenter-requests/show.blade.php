<x-admin-layout title="Detail Permintaan" :breadcrumbs="[
    ['label' => 'Dashboard', 'url' => route(auth()->user()->role->dashboardRoute())],
    ['label' => 'Permintaan', 'url' => route('presenter-requests.history')],
    ['label' => $request->request_code],
]">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-bonusku-navy">{{ $request->request_code }}</h2>
            <p class="mt-1 text-sm text-bonusku-slate">{{ $request->presenter?->name }} · {{ $request->pmbPeriod?->academic_year }}</p>
        </div>
        <x-request-status-badge :status="$request->status" class="!px-3 !py-1 !text-sm" />
    </div>

    <x-workflow-progress :request="$request" class="mb-6" />

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-6">
            <x-card header="Data Permintaan">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><span class="text-xs font-medium text-bonusku-slate block">Periode PMB</span><span class="text-sm font-medium text-bonusku-navy">{{ $request->pmbPeriod?->academic_year }} – {{ $request->pmbPeriod?->wave }}</span></div>
                    <div><span class="text-xs font-medium text-bonusku-slate block">Tanggal Pengajuan</span><span class="text-sm text-bonusku-navy">{{ $request->request_date->format('d M Y') }}</span></div>
                    <div><span class="text-xs font-medium text-bonusku-slate block">Dikirim</span><span class="text-sm text-bonusku-navy">{{ $request->submitted_at?->format('d M Y H:i') ?? '-' }}</span></div>
                    @if ($request->isEditable() && ! empty($commissionPreview))
                        <div><span class="text-xs font-medium text-bonusku-slate block">Total Komisi</span>
                            <span class="text-lg font-bold text-amber-600">Rp {{ number_format($commissionPreview['total_commission'], 0, ',', '.') }}</span>
                            <span class="mt-1 block text-xs text-amber-700">Estimasi live (belum dikunci)</span>
                        </div>
                    @else
                        <div><span class="text-xs font-medium text-bonusku-slate block">Total Komisi</span><span class="text-lg font-bold text-amber-600">Rp {{ number_format($request->total_commission, 0, ',', '.') }}</span></div>
                    @endif
                    @if ($request->admin_note)
                        <div class="sm:col-span-2 rounded-xl bg-slate-50 p-3"><span class="text-xs font-medium text-bonusku-slate block">Catatan Admin</span><span class="text-sm text-bonusku-navy">{{ $request->admin_note }}</span></div>
                    @endif
                </div>
            </x-card>

            <x-table-card title="Calon Mahasiswa" :description="$request->details->count() . ' mahasiswa terdaftar'">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bonusku-table-head">
                        <tr>
                            <th class="px-5 py-3 text-left">#</th>
                            <th class="px-5 py-3 text-left">NIM</th>
                            <th class="px-5 py-3 text-left">Nama</th>
                            <th class="px-5 py-3 text-left">Tgl Bayar</th>
                            <th class="px-5 py-3 text-left">Bukti</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($request->details as $detail)
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-5 py-3 text-sm text-bonusku-slate">{{ $loop->iteration }}</td>
                                <td class="px-5 py-3 text-sm font-medium text-bonusku-navy">{{ $detail->nim }}</td>
                                <td class="px-5 py-3 text-sm">{{ $detail->student_name }}</td>
                                <td class="px-5 py-3 text-sm text-bonusku-slate">{{ $detail->payment_date?->format('d/m/Y') ?? '-' }}</td>
                                <td class="px-5 py-3 text-sm">
                                    @if ($detail->payment_proof_file)
                                        <a href="{{ route('payment-proofs.download', $detail) }}" target="_blank" class="inline-flex rounded-lg border border-slate-200 p-2 hover:bg-indigo-50 hover:text-indigo-600" title="Unduh">
                                            <x-icon name="document" class="h-4 w-4" />
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-table-card>

            @if ($request->presenterTransfer)
                <x-card header="Transfer Keuangan ke Presenter">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><span class="text-xs font-medium text-bonusku-slate block">Tanggal Transfer</span><span class="text-sm text-bonusku-navy">{{ $request->presenterTransfer->transfer_date->format('d M Y') }}</span></div>
                        <div><span class="text-xs font-medium text-bonusku-slate block">Nominal Transfer</span><span class="text-sm font-semibold text-bonusku-navy">Rp {{ number_format($request->presenterTransfer->transfer_amount, 0, ',', '.') }}</span></div>
                        <div><span class="text-xs font-medium text-bonusku-slate block">Bank Tujuan</span><span class="text-sm text-bonusku-navy">{{ $request->presenterTransfer->destination_bank }}</span></div>
                        <div><span class="text-xs font-medium text-bonusku-slate block">Rekening Tujuan</span><span class="text-sm text-bonusku-navy">{{ $request->presenterTransfer->destination_account_number }}<br><span class="text-bonusku-slate">a.n. {{ $request->presenterTransfer->destination_account_name }}</span></span></div>
                        @if ($request->finance_note)
                            <div class="sm:col-span-2"><span class="text-xs font-medium text-bonusku-slate block">Catatan Keuangan</span><span class="text-sm text-bonusku-navy">{{ $request->finance_note }}</span></div>
                        @endif
                        @if ($request->presenterTransfer->note)
                            <div class="sm:col-span-2"><span class="text-xs font-medium text-bonusku-slate block">Catatan Selisih</span><span class="text-sm text-bonusku-navy">{{ $request->presenterTransfer->note }}</span></div>
                        @endif
                        @if ($request->presenterTransfer->transfer_proof_file)
                            <div class="sm:col-span-2">
                                <a href="{{ route('presenter-transfer-proofs.download', $request) }}" target="_blank" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm text-bonusku-navy hover:bg-indigo-50 hover:text-indigo-600">
                                    <x-icon name="document" class="h-4 w-4" /> Unduh Bukti Transfer ke Presenter
                                </a>
                            </div>
                        @endif
                    </div>
                </x-card>
            @endif
        </div>

        <x-card header="Informasi Presenter">
            <dl class="space-y-4 text-sm">
                <div class="rounded-xl bg-gradient-to-br from-indigo-50 to-violet-50 p-4 ring-1 ring-indigo-100">
                    <dt class="text-xs font-medium text-bonusku-slate">Nama Presenter</dt>
                    <dd class="mt-1 text-base font-bold text-bonusku-navy">{{ $request->presenter?->name }}</dd>
                    <dd class="text-xs text-indigo-600">{{ $request->presenter?->category?->name }}</dd>
                </div>
                <div><dt class="text-xs font-medium text-bonusku-slate">Bank</dt><dd class="font-medium text-bonusku-navy">{{ $request->presenter?->bank_name }}</dd></div>
                <div><dt class="text-xs font-medium text-bonusku-slate">Rekening</dt><dd class="text-bonusku-navy">{{ $request->presenter?->account_number }}<br><span class="text-bonusku-slate">a.n. {{ $request->presenter?->account_holder_name }}</span></dd></div>
                <div><dt class="text-xs font-medium text-bonusku-slate">Kontak</dt><dd class="text-bonusku-navy">{{ $request->presenter?->phone }}<br>{{ $request->presenter?->email ?? '-' }}</dd></div>
            </dl>
        </x-card>
    </div>

    @if ($request->notificationLogs->isNotEmpty())
        <x-table-card title="Riwayat Notifikasi" :description="$request->notificationLogs->count() . ' notifikasi terkirim'" class="mt-6">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bonusku-table-head">
                    <tr>
                        <th class="px-5 py-3 text-left">Waktu</th>
                        <th class="px-5 py-3 text-left">Penerima</th>
                        <th class="px-5 py-3 text-left">Nomor WA</th>
                        <th class="px-5 py-3 text-left">Role</th>
                        <th class="px-5 py-3 text-left">Status</th>
                        <th class="px-5 py-3 text-left">Provider</th>
                        <th class="px-5 py-3 text-left">Pesan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($request->notificationLogs as $log)
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-5 py-3 text-sm text-bonusku-slate whitespace-nowrap">{{ $log->sent_at?->format('d M Y H:i') ?? $log->created_at?->format('d M Y H:i') }}</td>
                            <td class="px-5 py-3 text-sm text-bonusku-navy">{{ $log->recipient_name ?? '-' }}</td>
                            <td class="px-5 py-3 text-sm text-bonusku-slate">{{ $log->recipient_phone ?? '-' }}</td>
                            <td class="px-5 py-3 text-sm">
                                <span class="inline-flex rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700">{{ $log->recipient_role ?? '-' }}</span>
                            </td>
                            <td class="px-5 py-3 text-sm">
                                @if ($log->status === \App\Enums\NotificationStatus::Sent)
                                    <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">Berhasil</span>
                                @elseif ($log->status === \App\Enums\NotificationStatus::Failed)
                                    <span class="inline-flex rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700">Gagal</span>
                                @else
                                    <span class="inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">Menunggu</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-sm text-bonusku-slate">{{ $log->provider ?? '-' }}</td>
                            <td class="px-5 py-3 text-sm text-bonusku-slate max-w-xs truncate" title="{{ $log->message }}">{{ Str::limit($log->message, 80) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-table-card>
    @endif
</x-admin-layout>

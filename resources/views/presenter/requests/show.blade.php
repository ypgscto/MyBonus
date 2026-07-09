<x-admin-layout title="Detail Permintaan">
    <div class="mb-6">
        <a href="{{ route('presenter.requests') }}" class="text-sm text-indigo-600 hover:text-indigo-800">← Kembali</a>
        <h2 class="mt-2 text-xl font-bold text-bonusku-navy">{{ $request->request_code }}</h2>
    </div>

    <div class="mb-6 grid gap-4 lg:grid-cols-3">
        <x-card>
            <h3 class="mb-3 text-sm font-semibold text-slate-500">Informasi Permintaan</h3>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Periode PMB</dt><dd class="font-medium">{{ $request->pmbPeriod?->academic_year }} — {{ $request->pmbPeriod?->wave }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Status</dt><dd><x-presenter-status-badge :status="$request->status" /></dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Total Mahasiswa</dt><dd class="font-medium">{{ $request->total_students }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Komisi / Mahasiswa</dt><dd class="font-medium">Rp {{ number_format($request->commission_per_student, 0, ',', '.') }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Total Komisi</dt><dd class="font-semibold text-amber-600">Rp {{ number_format($request->total_commission, 0, ',', '.') }}</dd></div>
            </dl>
        </x-card>

        <x-card class="lg:col-span-2">
            <h3 class="mb-3 text-sm font-semibold text-slate-500">Transfer ke Presenter</h3>
            @if ($request->presenterTransfer)
                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="text-slate-500">Tanggal Transfer</dt><dd class="font-medium">{{ $request->presenterTransfer->transfer_date?->format('d/m/Y') }}</dd></div>
                    <div><dt class="text-slate-500">Nominal</dt><dd class="font-medium text-emerald-600">Rp {{ number_format($request->presenterTransfer->transfer_amount, 0, ',', '.') }}</dd></div>
                    <div><dt class="text-slate-500">Bank Tujuan</dt><dd>{{ $request->presenterTransfer->destination_bank }}</dd></div>
                    <div><dt class="text-slate-500">Rekening</dt><dd>{{ \App\Support\AccountNumberMasker::mask($request->presenterTransfer->destination_account_number) }}</dd></div>
                    @if ($request->finance_note)
                        <div class="sm:col-span-2"><dt class="text-slate-500">Catatan Keuangan</dt><dd>{{ $request->finance_note }}</dd></div>
                    @endif
                    @if ($request->presenterTransfer->transfer_proof_file)
                        <div class="sm:col-span-2">
                            <a href="{{ route('presenter.payouts.proof', $request) }}" target="_blank" class="inline-flex items-center gap-1 rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">
                                Lihat Bukti Transfer
                            </a>
                        </div>
                    @endif
                </dl>
            @else
                <p class="text-sm text-slate-500">Belum ada data transfer ke presenter.</p>
            @endif
        </x-card>
    </div>

    <x-table-card title="Daftar Mahasiswa">
        <table class="min-w-full divide-y divide-slate-100">
            <thead class="bonusku-table-head">
                <tr>
                    <th class="px-5 py-3 text-left">#</th>
                    <th class="px-5 py-3 text-left">NIM</th>
                    <th class="px-5 py-3 text-left">Nama</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($request->details as $detail)
                    <tr>
                        <td class="px-5 py-3 text-sm">{{ $loop->iteration }}</td>
                        <td class="px-5 py-3 text-sm">{{ $detail->nim }}</td>
                        <td class="px-5 py-3 text-sm">{{ $detail->student_name }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-table-card>
</x-admin-layout>

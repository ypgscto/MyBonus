@php use App\Enums\PresenterRequestStatus; use App\Support\AccountNumberMasker; @endphp
<x-admin-layout title="Status Pencairan">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-bonusku-navy">Status Pencairan</h2>
        <p class="text-sm text-bonusku-slate">Pantau progres pencairan komisi presenter Anda.</p>
    </div>

    <x-card class="mb-6">
        <form method="GET" class="grid gap-4 md:grid-cols-4">
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">Periode PMB</label>
                <select name="pmb_period_id" class="bonusku-input">
                    <option value="">Semua</option>
                    @foreach ($periods as $period)
                        <option value="{{ $period->id }}" @selected(($filters['pmb_period_id'] ?? '') == $period->id)>{{ $period->academic_year }} — {{ $period->wave }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">Status Pencairan</label>
                <select name="payout_status" class="bonusku-input">
                    <option value="">Semua</option>
                    <option value="verification" @selected(($filters['payout_status'] ?? '') === 'verification')>Dalam Verifikasi</option>
                    <option value="approved" @selected(($filters['payout_status'] ?? '') === 'approved')>Disetujui</option>
                    <option value="finance" @selected(($filters['payout_status'] ?? '') === 'finance')>Dana di Keuangan</option>
                    <option value="transferred" @selected(($filters['payout_status'] ?? '') === 'transferred')>Sudah Ditransfer</option>
                    <option value="closed" @selected(($filters['payout_status'] ?? '') === 'closed')>Closed</option>
                    <option value="rejected" @selected(($filters['payout_status'] ?? '') === 'rejected')>Ditolak</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">Kode Permintaan</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="bonusku-input">
            </div>
            <div class="flex items-end">
                <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Filter</button>
            </div>
        </form>
    </x-card>

    <x-table-card>
        <table class="min-w-full divide-y divide-slate-100">
            <thead class="bonusku-table-head">
                <tr>
                    <th class="px-5 py-3 text-left">Kode</th>
                    <th class="px-5 py-3 text-left">Periode</th>
                    <th class="px-5 py-3 text-left">Mahasiswa</th>
                    <th class="px-5 py-3 text-left">Total Komisi</th>
                    <th class="px-5 py-3 text-left">Status</th>
                    <th class="px-5 py-3 text-left">Transfer</th>
                    <th class="px-5 py-3 text-left">Bukti</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($payouts as $item)
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-5 py-3 text-sm font-semibold">{{ $item->request_code }}</td>
                        <td class="px-5 py-3 text-sm">{{ $item->pmbPeriod?->wave }}</td>
                        <td class="px-5 py-3 text-sm">{{ $item->total_students }}</td>
                        <td class="px-5 py-3 text-sm font-medium text-amber-600">Rp {{ number_format($item->total_commission, 0, ',', '.') }}</td>
                        <td class="px-5 py-3 text-sm"><x-presenter-status-badge :status="$item->status" mode="payout" /></td>
                        <td class="px-5 py-3 text-sm">
                            @if ($item->presenterTransfer)
                                <div>Rp {{ number_format($item->presenterTransfer->transfer_amount, 0, ',', '.') }}</div>
                                <div class="text-xs text-slate-500">{{ AccountNumberMasker::mask($item->presenterTransfer->destination_account_number) }}</div>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-5 py-3 text-sm">
                            @if ($item->presenterTransfer?->transfer_proof_file)
                                <a href="{{ route('presenter.payouts.proof', $item) }}" target="_blank" class="text-indigo-600 hover:text-indigo-800">Lihat</a>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-6">
                        <x-empty-state icon="currency" title="Belum ada data pencairan" />
                    </td></tr>
                @endforelse
            </tbody>
        </table>
        @if ($payouts->hasPages())
            <div class="border-t border-slate-100 px-5 py-3">{{ $payouts->links() }}</div>
        @endif
    </x-table-card>
</x-admin-layout>

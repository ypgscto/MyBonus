@php use App\Enums\PresenterRequestStatus; @endphp
<x-admin-layout title="Permintaan Saya">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-bonusku-navy">Permintaan Saya</h2>
        <p class="text-sm text-bonusku-slate">Daftar permintaan presenter yang melibatkan akun Anda.</p>
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
                <label class="mb-1 block text-xs font-semibold text-slate-600">Status</label>
                <select name="status" class="bonusku-input">
                    <option value="">Semua</option>
                    @foreach (PresenterRequestStatus::cases() as $status)
                        @if ($status !== PresenterRequestStatus::Draft)
                            <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->presenterLabel() }}</option>
                        @endif
                    @endforeach
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
                    <th class="px-5 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($requests as $item)
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-5 py-3 text-sm font-semibold text-bonusku-navy">{{ $item->request_code }}</td>
                        <td class="px-5 py-3 text-sm">{{ $item->pmbPeriod?->wave }}</td>
                        <td class="px-5 py-3 text-sm">{{ $item->total_students }}</td>
                        <td class="px-5 py-3 text-sm font-medium text-amber-600">Rp {{ number_format($item->total_commission, 0, ',', '.') }}</td>
                        <td class="px-5 py-3 text-sm"><x-presenter-status-badge :status="$item->status" /></td>
                        <td class="px-5 py-3 text-right text-sm">
                            <a href="{{ route('presenter.requests.show', $item) }}" class="rounded-xl bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-6">
                        <x-empty-state icon="document" title="Belum ada permintaan" description="Permintaan akan muncul setelah Admin PMB mengajukan atas nama Anda." />
                    </td></tr>
                @endforelse
            </tbody>
        </table>
        @if ($requests->hasPages())
            <div class="border-t border-slate-100 px-5 py-3">{{ $requests->links() }}</div>
        @endif
    </x-table-card>
</x-admin-layout>

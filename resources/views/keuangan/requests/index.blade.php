<x-admin-layout :title="$title">
    <x-page-header :title="$title" />

    <x-card>
        <x-filter-bar class="sm:grid-cols-2 lg:grid-cols-3">
            <div class="sm:col-span-2">
                <input type="text" name="search" placeholder="Cari kode / presenter..."
                       value="{{ request('search') }}"
                       class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
        </x-filter-bar>

        <div class="overflow-x-auto -mx-5">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Kode</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Presenter</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Periode PMB</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Mahasiswa</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Total Komisi</th>
                        @if (($currentRoute ?? '') === 'keuangan.requests.incoming')
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Nominal Transfer</th>
                        @endif
                        @if (($currentRoute ?? '') === 'keuangan.requests.disbursement-history')
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Tgl Pencairan</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Nominal Pencairan</th>
                        @endif
                        <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($requests as $item)
                        <tr>
                            <td class="px-5 py-3 text-sm font-semibold text-slate-900">{{ $item->request_code }}</td>
                            <td class="px-5 py-3 text-sm text-slate-900">{{ $item->presenter?->name }}</td>
                            <td class="px-5 py-3 text-sm text-slate-600">{{ $item->pmbPeriod?->academic_year }} – {{ $item->pmbPeriod?->wave }}</td>
                            <td class="px-5 py-3 text-sm text-slate-900">{{ $item->total_students }}</td>
                            <td class="px-5 py-3 text-sm text-slate-900">Rp {{ number_format($item->total_commission, 0, ',', '.') }}</td>
                            @if (($currentRoute ?? '') === 'keuangan.requests.incoming')
                                <td class="px-5 py-3 text-sm text-slate-900">Rp {{ number_format($item->verifikatorTransfer?->transfer_amount ?? 0, 0, ',', '.') }}</td>
                            @endif
                            @if (($currentRoute ?? '') === 'keuangan.requests.disbursement-history')
                                <td class="px-5 py-3 text-sm text-slate-600">{{ $item->transferred_to_presenter_at?->format('d M Y H:i') ?? '-' }}</td>
                                <td class="px-5 py-3 text-sm text-slate-900">Rp {{ number_format($item->presenterTransfer?->transfer_amount ?? 0, 0, ',', '.') }}</td>
                            @endif
                            <td class="px-5 py-3 text-sm text-right">
                                <a href="{{ route('keuangan.requests.show', $item) }}" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm text-slate-700 hover:bg-slate-50">
                                    <x-icon name="document" class="h-4 w-4" /> Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ ($currentRoute ?? '') === 'keuangan.requests.disbursement-history' ? 8 : (($currentRoute ?? '') === 'keuangan.requests.incoming' ? 7 : 6) }}"
                                class="px-5 py-8 text-center text-sm text-slate-500">Tidak ada data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $requests->links() }}</div>
    </x-card>
</x-admin-layout>

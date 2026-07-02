<x-admin-layout title="Draft Permintaan" :breadcrumbs="[
    ['label' => 'Dashboard', 'url' => route(auth()->user()->role->dashboardRoute())],
    ['label' => 'Draft Permintaan'],
]">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-bonusku-navy">Draft Permintaan</h2>
            <p class="text-sm text-bonusku-slate">Permintaan yang masih dapat diedit sebelum dikirim ke Verifikator.</p>
        </div>
        <x-primary-button :href="route('presenter-requests.create')">
            <x-icon name="clipboard-plus" class="h-4 w-4" /> Buat Permintaan
        </x-primary-button>
    </div>

    <x-table-card>
        <x-slot name="filters">
            <form method="GET" class="grid gap-3 sm:grid-cols-3">
                <input type="text" name="search" placeholder="Cari kode / presenter..." value="{{ request('search') }}" class="bonusku-input sm:col-span-2">
                <button type="submit" class="rounded-xl bg-bonusku-navy px-4 py-2.5 text-sm font-semibold text-white hover:bg-bonusku-navy-soft">Filter</button>
            </form>
        </x-slot>
        <table class="min-w-full divide-y divide-slate-100">
            <thead class="bonusku-table-head">
                <tr>
                    <th class="px-5 py-3 text-left">Kode</th>
                    <th class="px-5 py-3 text-left">Periode</th>
                    <th class="px-5 py-3 text-left">Presenter</th>
                    <th class="px-5 py-3 text-left">Mahasiswa</th>
                    <th class="px-5 py-3 text-left">Status</th>
                    <th class="px-5 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($requests as $item)
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-5 py-3 text-sm font-bold text-bonusku-navy">{{ $item->request_code }}</td>
                        <td class="px-5 py-3 text-sm text-bonusku-slate">{{ $item->pmbPeriod?->academic_year }} – {{ $item->pmbPeriod?->wave }}</td>
                        <td class="px-5 py-3 text-sm">{{ $item->presenter?->name }}</td>
                        <td class="px-5 py-3 text-sm font-medium">{{ $item->details_count }}</td>
                        <td class="px-5 py-3 text-sm"><x-request-status-badge :status="$item->status" /></td>
                        <td class="px-5 py-3 text-sm text-right">
                            <a href="{{ route('presenter-requests.edit', $item) }}" class="inline-flex items-center gap-1 rounded-xl bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">
                                <x-icon name="pencil" class="h-3.5 w-3.5" /> Edit
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-8">
                        <x-empty-state icon="document-text" title="Belum ada draft permintaan" description="Mulai buat permintaan pertama untuk mencatat calon mahasiswa dari presenter." action-label="Buat Permintaan" :action-url="route('presenter-requests.create')" />
                    </td></tr>
                @endforelse
            </tbody>
        </table>
        @if ($requests->hasPages())
            <x-slot name="footer"><div class="px-2">{{ $requests->links() }}</div></x-slot>
        @endif
    </x-table-card>
</x-admin-layout>

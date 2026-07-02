<x-admin-layout title="Dashboard Admin PMB">
    <x-hero-card>
        <x-slot name="actions">
            <x-primary-button :href="route('presenter-requests.create')">
                <x-icon name="clipboard-plus" class="h-4 w-4" /> Buat Permintaan
            </x-primary-button>
            <x-secondary-button :href="route('presenter-requests.history')" class="!border-white/30 !bg-white/10 !text-white hover:!bg-white/20">
                Riwayat Permintaan
            </x-secondary-button>
        </x-slot>
    </x-hero-card>

    <div class="grid gap-4 grid-cols-2 md:grid-cols-3 xl:grid-cols-6 mb-8">
        <x-stat-card label="Draft Permintaan" :value="$counts['draft']" color="slate" icon="document-text" :href="route('presenter-requests.drafts')" />
        <x-stat-card label="Submitted" :value="$counts['submitted']" color="blue" icon="inbox" />
        <x-stat-card label="Ditolak" :value="$counts['rejected']" color="red" icon="x-circle" />
        <x-stat-card label="Disetujui" :value="$counts['approved']" color="emerald" icon="check" />
        <x-stat-card label="Transfer ke Keuangan" :value="$counts['transferred_to_finance']" color="purple" icon="send" />
        <x-stat-card label="Closed" :value="$counts['closed']" color="gold" icon="lock" />
    </div>

    <div class="grid gap-6 lg:grid-cols-12">
        <x-table-card title="Riwayat Permintaan Terbaru" description="Permintaan yang sudah Anda ajukan." class="lg:col-span-7">
            <x-slot name="filters">
                <a href="{{ route('presenter-requests.history') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">Lihat Semua →</a>
            </x-slot>
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bonusku-table-head">
                    <tr>
                        <th class="px-5 py-3 text-left">Kode</th>
                        <th class="px-5 py-3 text-left">Presenter</th>
                        <th class="px-5 py-3 text-left">Mahasiswa</th>
                        <th class="px-5 py-3 text-left">Status</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($recentRequests as $item)
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-5 py-3 text-sm font-semibold text-bonusku-navy">{{ $item->request_code }}</td>
                            <td class="px-5 py-3 text-sm text-bonusku-slate">{{ $item->presenter?->name }}</td>
                            <td class="px-5 py-3 text-sm">{{ $item->total_students }}</td>
                            <td class="px-5 py-3 text-sm"><x-request-status-badge :status="$item->status" /></td>
                            <td class="px-5 py-3 text-sm text-right">
                                <a href="{{ route('presenter-requests.show', $item) }}" class="inline-flex rounded-lg border border-slate-200 p-2 text-slate-600 hover:bg-indigo-50 hover:text-indigo-600" title="Detail">
                                    <x-icon name="document" class="h-4 w-4" />
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-6">
                            <x-empty-state icon="document" title="Belum ada permintaan presenter" description="Mulai buat permintaan pertama untuk mencatat calon mahasiswa dari presenter." action-label="Buat Permintaan" :action-url="route('presenter-requests.create')" />
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-table-card>

        <x-table-card title="Permintaan Ditolak" class="lg:col-span-5">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bonusku-table-head">
                    <tr>
                        <th class="px-5 py-3 text-left">Kode</th>
                        <th class="px-5 py-3 text-left">Alasan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($rejectedRequests as $item)
                        <tr class="hover:bg-red-50/30">
                            <td class="px-5 py-3 text-sm">
                                <a href="{{ route('presenter-requests.show', $item) }}" class="font-semibold text-indigo-600 hover:text-indigo-800">{{ $item->request_code }}</a>
                            </td>
                            <td class="px-5 py-3 text-sm text-red-600">{{ Str::limit($item->rejection_reason, 50) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="px-5 py-8 text-center text-sm text-bonusku-slate">Tidak ada penolakan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-table-card>
    </div>
</x-admin-layout>

<x-admin-layout title="Dashboard Verifikator">
    <x-hero-card subtitle="Verifikasi permintaan presenter dan transfer komisi ke bagian keuangan secara akurat.">
        <x-slot name="actions">
            <x-primary-button :href="route('verifikator.requests.pending')">
                <x-icon name="inbox" class="h-4 w-4" /> Menunggu Verifikasi
            </x-primary-button>
        </x-slot>
    </x-hero-card>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5 mb-8">
        <x-stat-card label="Menunggu Verifikasi" :value="$counts['pending']" color="gold" icon="inbox" :href="route('verifikator.requests.pending')" />
        <x-stat-card label="Disetujui" :value="$counts['approved']" color="emerald" icon="check" :href="route('verifikator.requests.approved')" />
        <x-stat-card label="Ditolak" :value="$counts['rejected']" color="red" icon="x-circle" :href="route('verifikator.requests.rejected')" />
        <x-stat-card label="Transfer ke Keuangan" :value="$counts['transferred_to_finance']" color="purple" icon="send" :href="route('verifikator.requests.transfer-history')" />
        <x-stat-card label="Total Nominal Transfer" :value="$monthlyTransferAmount" color="indigo" icon="currency" :money="true" />
    </div>

    <x-table-card title="Permintaan Menunggu Verifikasi">
        <x-slot name="filters">
            <a href="{{ route('verifikator.requests.pending') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">Lihat Semua →</a>
        </x-slot>
        <table class="min-w-full divide-y divide-slate-100">
            <thead class="bonusku-table-head">
                <tr>
                    <th class="px-5 py-3 text-left">Kode</th>
                    <th class="px-5 py-3 text-left">Presenter</th>
                    <th class="px-5 py-3 text-left">Mahasiswa</th>
                    <th class="px-5 py-3 text-left">Komisi</th>
                    <th class="px-5 py-3 text-left">Status</th>
                    <th class="px-5 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($pendingRequests as $item)
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-5 py-3 text-sm font-semibold text-bonusku-navy">{{ $item->request_code }}</td>
                        <td class="px-5 py-3 text-sm">{{ $item->presenter?->name }}</td>
                        <td class="px-5 py-3 text-sm">{{ $item->total_students }}</td>
                        <td class="px-5 py-3 text-sm font-medium text-amber-600">Rp {{ number_format($item->total_commission, 0, ',', '.') }}</td>
                        <td class="px-5 py-3 text-sm"><x-request-status-badge :status="$item->status" /></td>
                        <td class="px-5 py-3 text-sm text-right">
                            <a href="{{ route('verifikator.requests.show', $item) }}" class="inline-flex items-center gap-1 rounded-xl bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">
                                Verifikasi
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-6">
                        <x-empty-state icon="inbox" title="Tidak ada permintaan menunggu" description="Semua permintaan sudah diproses." />
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </x-table-card>
</x-admin-layout>

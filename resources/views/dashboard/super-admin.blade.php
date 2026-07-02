<x-admin-layout title="Dashboard Super Admin">
    <x-hero-card>
        <x-slot name="actions">
            @can('create', \App\Models\PresenterRequest::class)
                <x-primary-button :href="route('presenter-requests.create')">
                    <x-icon name="clipboard-plus" class="h-4 w-4" /> Buat Permintaan
                </x-primary-button>
            @endcan
            <x-secondary-button :href="route('reports.index')" class="!border-white/30 !bg-white/10 !text-white hover:!bg-white/20">
                <x-icon name="chart" class="h-4 w-4" /> Lihat Laporan
            </x-secondary-button>
        </x-slot>
    </x-hero-card>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 mb-8">
        <x-stat-card label="Total Presenter Aktif" :value="$activePresenters" color="indigo" icon="presenter" :href="route('master.presenters.index')" />
        <x-stat-card label="Total Permintaan" :value="$totalRequests" color="blue" icon="document" :href="route('presenter-requests.index')" />
        <x-stat-card label="Menunggu Verifikasi" :value="$pendingVerification" color="gold" icon="inbox" />
        <x-stat-card label="Menunggu Pencairan" :value="$pendingDisbursement" color="purple" icon="send" />
        <x-stat-card label="Total Komisi Bulan Ini" :value="$monthlyCommission" color="emerald" icon="currency" :money="true" />
        <x-stat-card label="Permintaan Closed" :value="$totalClosed" color="slate" icon="lock" />
    </div>

    <div class="grid gap-6 lg:grid-cols-3 mb-8">
        <x-card header="Grafik Permintaan per Bulan" class="lg:col-span-2">
            <canvas id="requestsChart" height="120"></canvas>
        </x-card>
        <x-card header="Ringkasan Cepat">
            <ul class="divide-y divide-slate-100">
                <li class="flex justify-between py-3 text-sm">
                    <span class="text-bonusku-slate">Total mahasiswa</span>
                    <span class="font-bold text-bonusku-navy">{{ number_format($totalStudents, 0, ',', '.') }}</span>
                </li>
                <li class="flex justify-between py-3 text-sm">
                    <span class="text-bonusku-slate">Total komisi keseluruhan</span>
                    <span class="font-bold text-emerald-600">Rp {{ number_format($totalCommission, 0, ',', '.') }}</span>
                </li>
                <li class="flex justify-between py-3 text-sm">
                    <span class="text-bonusku-slate">Transfer ke keuangan</span>
                    <span class="font-bold text-bonusku-navy">Rp {{ number_format($totalVerifikatorTransfers, 0, ',', '.') }}</span>
                </li>
            </ul>
        </x-card>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <x-table-card title="Top 10 Presenter — Jumlah Mahasiswa">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bonusku-table-head">
                    <tr>
                        <th class="px-5 py-3 text-left">#</th>
                        <th class="px-5 py-3 text-left">Presenter</th>
                        <th class="px-5 py-3 text-right">Mahasiswa</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($topPresentersByStudents as $item)
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-5 py-3 text-sm text-bonusku-slate">{{ $loop->iteration }}</td>
                            <td class="px-5 py-3 text-sm font-medium text-bonusku-navy">{{ $item->presenter_name }}</td>
                            <td class="px-5 py-3 text-sm text-right font-bold text-indigo-600">{{ number_format($item->total_students, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-5 py-8"><x-empty-state icon="presenter" title="Belum ada data" description="Data akan muncul setelah ada permintaan presenter." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-table-card>
        <x-table-card title="Top 10 Presenter — Total Komisi">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bonusku-table-head">
                    <tr>
                        <th class="px-5 py-3 text-left">#</th>
                        <th class="px-5 py-3 text-left">Presenter</th>
                        <th class="px-5 py-3 text-right">Komisi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($topPresentersByCommission as $item)
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-5 py-3 text-sm text-bonusku-slate">{{ $loop->iteration }}</td>
                            <td class="px-5 py-3 text-sm font-medium text-bonusku-navy">{{ $item->presenter_name }}</td>
                            <td class="px-5 py-3 text-sm text-right font-bold text-amber-600">Rp {{ number_format($item->total_commission, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-5 py-8"><x-empty-state icon="currency" title="Belum ada data komisi" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-table-card>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
    (function () {
        const labels = @json($requestsPerMonth->pluck('label'));
        const data = @json($requestsPerMonth->pluck('total'));
        const ctx = document.getElementById('requestsChart');
        if (!ctx) return;
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Permintaan',
                    data,
                    backgroundColor: 'rgba(79, 70, 229, 0.75)',
                    borderColor: 'rgba(245, 158, 11, 0.8)',
                    borderWidth: 1,
                    borderRadius: 8,
                }],
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
            },
        });
    })();
    </script>
    @endpush
</x-admin-layout>

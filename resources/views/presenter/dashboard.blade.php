<x-admin-layout title="Dashboard Presenter">
    <x-hero-card
        :title="'Halo, '.$presenter->name"
        subtitle="Pantau mahasiswa yang Anda daftarkan dan status pencairan komisi presenter Anda."
    />

    <div class="mb-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Total Mahasiswa" :value="$totalStudents" color="indigo" icon="users" :href="route('presenter.students')" />
        <x-stat-card label="Total Permintaan" :value="$totalRequests" color="purple" icon="document" :href="route('presenter.requests')" />
        <x-stat-card label="Bonus Dalam Proses" :value="$pendingCommission" color="gold" icon="currency" :money="true" :href="route('presenter.payouts')" />
        <x-stat-card label="Bonus Sudah Cair" :value="$paidCommission" color="emerald" icon="check" :money="true" :href="route('presenter.payouts')" />
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <x-table-card title="Mahasiswa Terbaru">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bonusku-table-head">
                    <tr>
                        <th class="px-5 py-3 text-left">NIM</th>
                        <th class="px-5 py-3 text-left">Nama</th>
                        <th class="px-5 py-3 text-left">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($recentStudents as $student)
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-5 py-3 text-sm font-medium text-bonusku-navy">{{ $student->nim }}</td>
                            <td class="px-5 py-3 text-sm">{{ $student->student_name }}</td>
                            <td class="px-5 py-3 text-sm">
                                <x-presenter-status-badge :status="$student->presenterRequest->status" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="p-6">
                            <x-empty-state icon="users" title="Belum ada mahasiswa yang terdaftar atas nama Anda." description="Data akan muncul setelah Admin PMB membuat permintaan presenter." />
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-table-card>

        <x-table-card title="Status Pencairan Terbaru">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bonusku-table-head">
                    <tr>
                        <th class="px-5 py-3 text-left">Kode</th>
                        <th class="px-5 py-3 text-left">Komisi</th>
                        <th class="px-5 py-3 text-left">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($recentRequests as $item)
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-5 py-3 text-sm font-medium text-bonusku-navy">{{ $item->request_code }}</td>
                            <td class="px-5 py-3 text-sm font-medium text-amber-600">Rp {{ number_format($item->total_commission, 0, ',', '.') }}</td>
                            <td class="px-5 py-3 text-sm">
                                <x-presenter-status-badge :status="$item->status" mode="payout" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="p-6">
                            <x-empty-state icon="currency" title="Belum ada permintaan" description="Permintaan akan muncul setelah Admin PMB mengajukan atas nama Anda." />
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-table-card>
    </div>
</x-admin-layout>

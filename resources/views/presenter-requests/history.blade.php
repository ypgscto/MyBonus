<x-admin-layout title="Riwayat Permintaan">
    <x-page-header title="Riwayat Permintaan" description="Permintaan yang sudah dikirim ke Verifikator dan seterusnya." />

    <x-card>
        <x-filter-bar class="sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <input type="text" name="search" placeholder="Cari kode / presenter..."
                       value="{{ request('search') }}"
                       class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <select name="status" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Semua Status</option>
                    @foreach (\App\Enums\PresenterRequestStatus::cases() as $status)
                        @if ($status !== \App\Enums\PresenterRequestStatus::Draft)
                            <option value="{{ $status->value }}" @selected(request('status') === $status->value)>
                                {{ $status->label() }}
                            </option>
                        @endif
                    @endforeach
                </select>
            </div>
        </x-filter-bar>

        <div class="overflow-x-auto -mx-5">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Kode</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Periode</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Presenter</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Dikirim</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Mahasiswa</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Total Komisi</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($requests as $item)
                        <tr>
                            <td class="px-5 py-3 text-sm font-semibold text-slate-900">{{ $item->request_code }}</td>
                            <td class="px-5 py-3 text-sm text-slate-600">{{ $item->pmbPeriod?->academic_year }} – {{ $item->pmbPeriod?->wave }}</td>
                            <td class="px-5 py-3 text-sm text-slate-900">{{ $item->presenter?->name }}</td>
                            <td class="px-5 py-3 text-sm text-slate-600">{{ $item->submitted_at?->format('d M Y H:i') ?? '-' }}</td>
                            <td class="px-5 py-3 text-sm text-slate-900">{{ $item->total_students }}</td>
                            <td class="px-5 py-3 text-sm text-slate-900">Rp {{ number_format($item->total_commission, 0, ',', '.') }}</td>
                            <td class="px-5 py-3 text-sm"><x-request-status-badge :status="$item->status" /></td>
                            <td class="px-5 py-3 text-sm text-right">
                                <a href="{{ route('presenter-requests.show', $item) }}" class="inline-flex items-center rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm text-slate-700 hover:bg-slate-50" title="Detail">
                                    <x-icon name="document" class="h-4 w-4" />
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-5 py-8 text-center text-sm text-slate-500">Belum ada riwayat permintaan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $requests->links() }}</div>
    </x-card>
</x-admin-layout>

<x-admin-layout title="Skema Komisi">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-xl font-semibold text-slate-900">Skema Komisi</h2>
        <a href="{{ route('master.commission-schemes.create') }}" class="inline-flex items-center gap-1 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
            <x-icon name="plus-doc" class="h-4 w-4" /> Tambah Skema
        </a>
    </div>

    <x-card>
        @include('master.partials.filter-bar')

        <div class="overflow-x-auto -mx-5">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">#</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Kategori</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Periode PMB</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Komisi / Mahasiswa</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($schemes as $scheme)
                        <tr>
                            <td class="px-5 py-3 text-sm text-slate-900">{{ $schemes->firstItem() + $loop->index }}</td>
                            <td class="px-5 py-3 text-sm font-semibold text-slate-900">{{ $scheme->presenterCategory?->name ?? '-' }}</td>
                            <td class="px-5 py-3 text-sm text-slate-900">{{ $scheme->pmbPeriod?->academic_year }} – {{ $scheme->pmbPeriod?->wave }}</td>
                            <td class="px-5 py-3 text-sm text-slate-900">Rp {{ number_format($scheme->commission_amount_per_student, 0, ',', '.') }}</td>
                            <td class="px-5 py-3 text-sm"><x-status-badge :status="$scheme->status" /></td>
                            <td class="px-5 py-3 text-sm text-right space-x-1">
                                <a href="{{ route('master.commission-schemes.edit', $scheme) }}"
                                   class="inline-flex items-center rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm text-slate-700 hover:bg-slate-50" title="Edit">
                                    <x-icon name="pencil" class="h-4 w-4" />
                                </a>
                                @include('master.partials.toggle-button', [
                                    'action' => route('master.commission-schemes.toggle-status', $scheme),
                                    'status' => $scheme->status,
                                    'confirm' => $scheme->status->value === 'aktif'
                                        ? 'Nonaktifkan skema ini?'
                                        : 'Aktifkan skema ini?',
                                ])
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-sm text-slate-500">Belum ada data skema komisi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $schemes->links() }}</div>
    </x-card>
</x-admin-layout>

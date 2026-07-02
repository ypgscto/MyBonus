<x-admin-layout :title="$pageTitle">
    <x-page-header :title="$pageTitle" description="Daftar permintaan presenter sesuai peran Anda." />

    <x-card>
        <x-filter-bar class="sm:grid-cols-2 lg:grid-cols-4">
            <div class="sm:col-span-2">
                <input type="text" name="search" placeholder="Cari kode / presenter..."
                       value="{{ request('search') }}"
                       class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <select name="status" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Semua status</option>
                    @foreach (\App\Enums\PresenterRequestStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
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
                        @if (auth()->user()->role === \App\Enums\UserRole::SuperAdmin)
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Dibuat Oleh</th>
                        @endif
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Mahasiswa</th>
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
                            @if (auth()->user()->role === \App\Enums\UserRole::SuperAdmin)
                                <td class="px-5 py-3 text-sm text-slate-600">{{ $item->creator?->name ?? '-' }}</td>
                            @endif
                            <td class="px-5 py-3 text-sm text-slate-900">{{ $item->details_count }}</td>
                            <td class="px-5 py-3 text-sm"><x-request-status-badge :status="$item->status" /></td>
                            <td class="px-5 py-3 text-sm text-right">
                                <a href="{{ route('presenter-requests.show', $item) }}" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm text-slate-700 hover:bg-slate-50">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()->role === \App\Enums\UserRole::SuperAdmin ? 7 : 6 }}" class="px-5 py-8 text-center text-sm text-slate-500">
                                Tidak ada permintaan ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $requests->links() }}</div>
    </x-card>
</x-admin-layout>

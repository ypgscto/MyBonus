<x-admin-layout title="Kategori Presenter">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-xl font-semibold text-slate-900">Kategori Presenter</h2>
        <a href="{{ route('master.presenter-categories.create') }}" class="inline-flex items-center gap-1 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
            <x-icon name="plus-doc" class="h-4 w-4" /> Tambah Kategori
        </a>
    </div>

    <x-card>
        @include('master.partials.filter-bar')

        <div class="overflow-x-auto -mx-5">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">#</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Nama Kategori</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Deskripsi</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($categories as $category)
                        <tr>
                            <td class="px-5 py-3 text-sm text-slate-900">{{ $categories->firstItem() + $loop->index }}</td>
                            <td class="px-5 py-3 text-sm font-semibold text-slate-900">{{ $category->name }}</td>
                            <td class="px-5 py-3 text-sm text-slate-900">{{ $category->description ?? '-' }}</td>
                            <td class="px-5 py-3 text-sm"><x-status-badge :status="$category->status" /></td>
                            <td class="px-5 py-3 text-sm text-right space-x-1">
                                <a href="{{ route('master.presenter-categories.edit', $category) }}"
                                   class="inline-flex items-center rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm text-slate-700 hover:bg-slate-50" title="Edit">
                                    <x-icon name="pencil" class="h-4 w-4" />
                                </a>
                                @include('master.partials.toggle-button', [
                                    'action' => route('master.presenter-categories.toggle-status', $category),
                                    'status' => $category->status,
                                    'confirm' => $category->status->value === 'aktif'
                                        ? 'Nonaktifkan kategori ini?'
                                        : 'Aktifkan kategori ini?',
                                ])
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-sm text-slate-500">Belum ada data kategori presenter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $categories->links() }}</div>
    </x-card>
</x-admin-layout>

<x-admin-layout title="Kelola User">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-xl font-semibold text-slate-900">Kelola User</h2>
        <a href="{{ route('users.create') }}" class="inline-flex items-center gap-1 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
            <x-icon name="plus-doc" class="h-4 w-4" /> Tambah User
        </a>
    </div>

    <x-card>
        <x-filter-bar>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Cari</label>
                <input type="text" name="search" placeholder="Nama, email, atau nomor WA..."
                       value="{{ request('search') }}"
                       class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Role</label>
                <select name="role" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Semua Role</option>
                    @foreach (\App\Enums\UserRole::cases() as $role)
                        <option value="{{ $role->value }}" @selected(request('role') === $role->value)>{{ $role->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
                <select name="status" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Semua Status</option>
                    <option value="aktif" @selected(request('status') === 'aktif')>Aktif</option>
                    <option value="nonaktif" @selected(request('status') === 'nonaktif')>Nonaktif</option>
                </select>
            </div>
        </x-filter-bar>

        <div class="overflow-x-auto -mx-5">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">#</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Nama</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Email</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Nomor WhatsApp</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Role</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Status</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Last Login</th>
                        <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($users as $user)
                        <tr>
                            <td class="px-5 py-3 text-sm text-slate-900">{{ $users->firstItem() + $loop->index }}</td>
                            <td class="px-5 py-3 text-sm font-semibold text-slate-900">
                                <a href="{{ route('users.show', $user) }}" class="hover:text-indigo-600">{{ $user->name }}</a>
                            </td>
                            <td class="px-5 py-3 text-sm text-slate-600">{{ $user->email }}</td>
                            <td class="px-5 py-3 text-sm text-slate-600">{{ $user->phone }}</td>
                            <td class="px-5 py-3 text-sm">
                                <span class="inline-flex rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-medium text-indigo-700 ring-1 ring-indigo-200">
                                    {{ $user->role->label() }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-sm">
                                @if ($user->status === \App\Enums\UserStatus::Aktif)
                                    <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200">Aktif</span>
                                @else
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600 ring-1 ring-slate-200">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-sm text-slate-600">{{ $user->last_login_at?->format('d M Y H:i') ?? '-' }}</td>
                            <td class="px-5 py-3 text-sm text-right">
                                <div class="inline-flex items-center gap-1">
                                    <a href="{{ route('users.edit', $user) }}"
                                       class="inline-flex items-center rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm text-slate-700 hover:bg-slate-50" title="Edit">
                                        <x-icon name="pencil" class="h-4 w-4" />
                                    </a>
                                    @if ($user->id !== auth()->id())
                                        <form method="POST" action="{{ route('users.toggle-status', $user) }}" class="inline"
                                              onsubmit="return confirm('{{ $user->status->value === 'aktif' ? 'Nonaktifkan user ini?' : 'Aktifkan user ini?' }}')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    class="inline-flex items-center rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm {{ $user->status->value === 'aktif' ? 'text-amber-700 hover:bg-amber-50' : 'text-emerald-700 hover:bg-emerald-50' }}"
                                                    title="{{ $user->status->value === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' }}">
                                                <x-icon name="{{ $user->status->value === 'aktif' ? 'x-circle' : 'check' }}" class="h-4 w-4" />
                                            </button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('users.reset-password', $user) }}" class="inline"
                                          onsubmit="return confirm('Reset password user ini?')">
                                        @csrf
                                        <button type="submit"
                                                class="inline-flex items-center rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm text-violet-700 hover:bg-violet-50" title="Reset Password">
                                            <x-icon name="lock" class="h-4 w-4" />
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-8 text-center text-sm text-slate-500">Belum ada data user.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $users->links() }}</div>
    </x-card>
</x-admin-layout>

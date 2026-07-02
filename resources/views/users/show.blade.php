<x-admin-layout title="Detail User">
    <div class="mb-6">
        <a href="{{ route('users.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">← Kembali</a>
        <div class="mt-2 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold text-slate-900">{{ $user->name }}</h2>
            <div class="flex gap-2">
                <a href="{{ route('users.edit', $user) }}" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    <x-icon name="pencil" class="h-4 w-4" /> Edit
                </a>
            </div>
        </div>
    </div>

    <x-card>
        <dl class="grid gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-xs font-medium text-slate-500">Nama</dt>
                <dd class="mt-1 text-sm font-medium text-slate-900">{{ $user->name }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-slate-500">Email</dt>
                <dd class="mt-1 text-sm text-slate-900">{{ $user->email }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-slate-500">Nomor WhatsApp</dt>
                <dd class="mt-1 text-sm text-slate-900">{{ $user->phone }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-slate-500">Role</dt>
                <dd class="mt-1">
                    <span class="inline-flex rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-medium text-indigo-700 ring-1 ring-indigo-200">
                        {{ $user->role->label() }}
                    </span>
                </dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-slate-500">Status</dt>
                <dd class="mt-1">
                    @if ($user->status === \App\Enums\UserStatus::Aktif)
                        <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200">Aktif</span>
                    @else
                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600 ring-1 ring-slate-200">Nonaktif</span>
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-slate-500">Last Login</dt>
                <dd class="mt-1 text-sm text-slate-900">{{ $user->last_login_at?->format('d M Y H:i') ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-slate-500">Dibuat</dt>
                <dd class="mt-1 text-sm text-slate-900">{{ $user->created_at?->format('d M Y H:i') ?? '-' }}</dd>
            </div>
            @if ($user->role === \App\Enums\UserRole::Keuangan)
                <div class="sm:col-span-2 border-t border-slate-100 pt-4">
                    <h3 class="text-sm font-semibold text-slate-900 mb-3">Data Rekening</h3>
                </div>
                <div>
                    <dt class="text-xs font-medium text-slate-500">Bank</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $user->bank_name ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-slate-500">Nomor Rekening</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $user->account_number ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-slate-500">Atas Nama Rekening</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $user->account_holder_name ?? '-' }}</dd>
                </div>
            @endif
        </dl>
    </x-card>
</x-admin-layout>

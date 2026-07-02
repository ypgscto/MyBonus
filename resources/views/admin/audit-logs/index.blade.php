@php
    use App\Enums\AuditAction;
    $inputClass = 'block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
@endphp

<x-admin-layout title="Audit Log">
    <x-page-header title="Audit Log" description="Riwayat aktivitas penting di sistem BONUSKU." />

    <x-card header="Filter" class="mb-6">
        <form method="GET" action="{{ route('admin.audit-logs.index') }}">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Tanggal Awal</label>
                    <input type="date" name="date_from" class="{{ $inputClass }}" value="{{ $filters['date_from'] ?? '' }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Tanggal Akhir</label>
                    <input type="date" name="date_to" class="{{ $inputClass }}" value="{{ $filters['date_to'] ?? '' }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">User</label>
                    <select name="user_id" class="{{ $inputClass }}">
                        <option value="">Semua</option>
                        @foreach ($filterOptions['users'] as $user)
                            <option value="{{ $user->id }}" @selected(($filters['user_id'] ?? '') == $user->id)>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Module</label>
                    <select name="module" class="{{ $inputClass }}">
                        <option value="">Semua</option>
                        @foreach ($filterOptions['modules'] as $module)
                            <option value="{{ $module }}" @selected(($filters['module'] ?? '') === $module)>
                                {{ $filterOptions['moduleLabels'][$module] ?? $module }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Action</label>
                    <select name="action" class="{{ $inputClass }}">
                        <option value="">Semua</option>
                        @foreach (AuditAction::cases() as $action)
                            <option value="{{ $action->value }}" @selected(($filters['action'] ?? '') === $action->value)>
                                {{ $action->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2 flex items-end gap-2">
                    <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Filter</button>
                    <a href="{{ route('admin.audit-logs.index') }}" class="inline-flex items-center rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Reset</a>
                </div>
            </div>
        </form>
    </x-card>

    <x-card>
        <div class="overflow-x-auto -mx-5">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">#</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Waktu</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">User</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Module</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Action</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Referensi</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($logs as $log)
                        @php
                            $action = AuditAction::tryFrom($log->action);
                        @endphp
                        <tr>
                            <td class="px-5 py-3 text-sm text-slate-900">{{ $logs->firstItem() + $loop->index }}</td>
                            <td class="px-5 py-3 text-sm text-slate-900 whitespace-nowrap">{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-3 text-sm text-slate-900">{{ $log->user?->name ?? '-' }}</td>
                            <td class="px-5 py-3 text-sm text-slate-900">{{ $filterOptions['moduleLabels'][$log->module] ?? $log->module }}</td>
                            <td class="px-5 py-3 text-sm text-slate-900">{{ $action?->label() ?? $log->action }}</td>
                            <td class="px-5 py-3 text-sm text-slate-900">{{ $log->reference_id ?? '-' }}</td>
                            <td class="px-5 py-3 text-sm text-slate-500">{{ $log->ip_address ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-8 text-center text-sm text-slate-500">Belum ada data audit log.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $logs->links() }}</div>
    </x-card>
</x-admin-layout>

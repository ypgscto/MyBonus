@php $inputClass = 'block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500'; @endphp

<x-admin-layout title="Notification Log">
    <x-page-header title="Notification Log" description="Riwayat notifikasi WhatsApp yang dikirim sistem." />

    <x-card header="Filter" class="mb-6">
        <form method="GET" action="{{ route('admin.notification-logs.index') }}">
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
                    <label class="block text-sm font-medium text-slate-700 mb-1">Provider</label>
                    <select name="provider" class="{{ $inputClass }}">
                        <option value="">Semua</option>
                        @foreach ($filterOptions['providers'] as $provider)
                            <option value="{{ $provider }}" @selected(($filters['provider'] ?? '') === $provider)>
                                {{ $provider }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
                    <select name="status" class="{{ $inputClass }}">
                        <option value="">Semua</option>
                        @foreach ($filterOptions['statuses'] as $status)
                            <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>
                                {{ $status->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Recipient Role</label>
                    <select name="recipient_role" class="{{ $inputClass }}">
                        <option value="">Semua</option>
                        @foreach ($filterOptions['recipientRoles'] as $role)
                            <option value="{{ $role }}" @selected(($filters['recipient_role'] ?? '') === $role)>
                                {{ $role }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Kode Permintaan</label>
                    <input type="text" name="request_code" class="{{ $inputClass }}"
                           placeholder="PR-..." value="{{ $filters['request_code'] ?? '' }}">
                </div>
                <div class="sm:col-span-2 flex items-end gap-2">
                    <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Filter</button>
                    <a href="{{ route('admin.notification-logs.index') }}" class="inline-flex items-center rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Reset</a>
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
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Kode Permintaan</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Role Penerima</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Nama Penerima</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Telepon</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Provider</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Status</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Pesan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($logs as $log)
                        <tr>
                            <td class="px-5 py-3 text-sm text-slate-900">{{ $logs->firstItem() + $loop->index }}</td>
                            <td class="px-5 py-3 text-sm text-slate-900 whitespace-nowrap">{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-3 text-sm text-slate-900">{{ $log->presenterRequest?->request_code ?? '-' }}</td>
                            <td class="px-5 py-3 text-sm text-slate-900">{{ $log->recipient_role }}</td>
                            <td class="px-5 py-3 text-sm text-slate-900">{{ $log->recipient_name }}</td>
                            <td class="px-5 py-3 text-sm text-slate-900 whitespace-nowrap">{{ $log->recipient_phone }}</td>
                            <td class="px-5 py-3 text-sm text-slate-900">{{ $log->provider }}</td>
                            <td class="px-5 py-3 text-sm">
                                @if ($log->status->value === 'sent')
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">{{ $log->status->label() }}</span>
                                @elseif ($log->status->value === 'failed')
                                    <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">{{ $log->status->label() }}</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">{{ $log->status->label() }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-sm text-slate-500 max-w-[240px]">
                                {{ \Illuminate\Support\Str::limit($log->message, 80) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-8 text-center text-sm text-slate-500">Belum ada data notification log.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $logs->links() }}</div>
    </x-card>
</x-admin-layout>

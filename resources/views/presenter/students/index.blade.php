@php use App\Enums\PresenterRequestStatus; @endphp
<x-admin-layout title="Mahasiswa Saya">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-bonusku-navy">Mahasiswa Saya</h2>
        <p class="text-sm text-bonusku-slate">Daftar mahasiswa yang didaftarkan melalui permintaan presenter Anda.</p>
    </div>

    <x-card class="mb-6">
        <form method="GET" class="grid gap-4 md:grid-cols-5">
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">Periode PMB</label>
                <select name="pmb_period_id" class="bonusku-input">
                    <option value="">Semua</option>
                    @foreach ($periods as $period)
                        <option value="{{ $period->id }}" @selected(($filters['pmb_period_id'] ?? '') == $period->id)>{{ $period->academic_year }} — {{ $period->wave }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">Status Permintaan</label>
                <select name="status" class="bonusku-input">
                    <option value="">Semua</option>
                    @foreach (PresenterRequestStatus::cases() as $status)
                        @if ($status !== PresenterRequestStatus::Draft)
                            <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->presenterLabel() }}</option>
                        @endif
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">Cari NIM/Nama</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="bonusku-input" placeholder="NIM atau nama">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">Tanggal Awal</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="bonusku-input">
            </div>
            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Tanggal Akhir</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="bonusku-input">
                </div>
                <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Filter</button>
            </div>
        </form>
    </x-card>

    <x-table-card>
        <table class="min-w-full divide-y divide-slate-100">
            <thead class="bonusku-table-head">
                <tr>
                    <th class="px-5 py-3 text-left">NIM</th>
                    <th class="px-5 py-3 text-left">Nama Mahasiswa</th>
                    <th class="px-5 py-3 text-left">Periode PMB</th>
                    <th class="px-5 py-3 text-left">Kode Permintaan</th>
                    <th class="px-5 py-3 text-left">Status</th>
                    <th class="px-5 py-3 text-left">Tgl Pengajuan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($students as $student)
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-5 py-3 text-sm font-medium">{{ $student->nim }}</td>
                        <td class="px-5 py-3 text-sm">{{ $student->student_name }}</td>
                        <td class="px-5 py-3 text-sm">{{ $student->presenterRequest?->pmbPeriod?->wave }}</td>
                        <td class="px-5 py-3 text-sm">{{ $student->presenterRequest?->request_code }}</td>
                        <td class="px-5 py-3 text-sm"><x-presenter-status-badge :status="$student->presenterRequest->status" /></td>
                        <td class="px-5 py-3 text-sm">{{ $student->presenterRequest?->request_date?->format('d/m/Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-6">
                        <x-empty-state icon="users" title="Belum ada mahasiswa yang terdaftar atas nama Anda." description="Data akan muncul setelah Admin PMB membuat permintaan presenter." />
                    </td></tr>
                @endforelse
            </tbody>
        </table>
        @if ($students->hasPages())
            <div class="border-t border-slate-100 px-5 py-3">{{ $students->links() }}</div>
        @endif
    </x-table-card>
</x-admin-layout>

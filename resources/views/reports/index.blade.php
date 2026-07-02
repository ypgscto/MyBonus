<x-admin-layout title="Laporan">
    <x-page-header title="Laporan" description="Filter dan tampilkan laporan sistem BONUSKU." />

    <x-card header="Filter Laporan" class="mb-6">
        <form method="GET" action="{{ route('reports.index') }}">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Jenis Laporan <span class="text-red-600">*</span></label>
                    <select name="type" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                        <option value="">-- Pilih Jenis Laporan --</option>
                        @foreach ($filterOptions['reportTypes'] as $reportType)
                            <option value="{{ $reportType->value }}" @selected(($filters['type'] ?? '') === $reportType->value)>
                                {{ $reportType->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Periode PMB</label>
                    <select name="pmb_period_id" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Semua</option>
                        @foreach ($filterOptions['pmbPeriods'] as $period)
                            <option value="{{ $period->id }}" @selected(($filters['pmb_period_id'] ?? '') == $period->id)>
                                {{ $period->academic_year }} – {{ $period->wave }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Tanggal Awal</label>
                    <input type="date" name="date_from" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" value="{{ $filters['date_from'] ?? '' }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Tanggal Akhir</label>
                    <input type="date" name="date_to" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" value="{{ $filters['date_to'] ?? '' }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Presenter</label>
                    <select name="presenter_id" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Semua</option>
                        @foreach ($filterOptions['presenters'] as $presenter)
                            <option value="{{ $presenter->id }}" @selected(($filters['presenter_id'] ?? '') == $presenter->id)>{{ $presenter->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Kategori Presenter</label>
                    <select name="presenter_category_id" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Semua</option>
                        @foreach ($filterOptions['categories'] as $category)
                            <option value="{{ $category->id }}" @selected(($filters['presenter_category_id'] ?? '') == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Status Permintaan</label>
                    <select name="status" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Semua</option>
                        @foreach ($filterOptions['statuses'] as $status)
                            <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Admin Pembuat</label>
                    <select name="created_by" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @disabled(auth()->user()->role->value === 'admin_pmb')>
                        <option value="">Semua</option>
                        @foreach ($filterOptions['admins'] as $admin)
                            <option value="{{ $admin->id }}" @selected(($filters['created_by'] ?? auth()->id()) == $admin->id)>{{ $admin->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Verifikator</label>
                    <select name="verifikator_id" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Semua</option>
                        @foreach ($filterOptions['verifikators'] as $verifikator)
                            <option value="{{ $verifikator->id }}" @selected(($filters['verifikator_id'] ?? '') == $verifikator->id)>{{ $verifikator->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Keuangan</label>
                    <select name="keuangan_id" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Semua</option>
                        @foreach ($filterOptions['keuanganUsers'] as $keuangan)
                            <option value="{{ $keuangan->id }}" @selected(($filters['keuangan_id'] ?? '') == $keuangan->id)>{{ $keuangan->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="mt-6 flex flex-wrap gap-2">
                <button type="submit" class="inline-flex items-center gap-1 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Tampilkan Laporan</button>
                <a href="{{ route('reports.index') }}" class="inline-flex items-center rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Reset</a>
            </div>
        </form>
    </x-card>

    @if ($result)
        <x-card>
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4 -mt-1 pb-4 border-b border-slate-200">
                <h3 class="font-semibold text-slate-900">{{ $result['title'] }}</h3>
                <div class="flex gap-2">
                    <form method="POST" action="{{ route('reports.export.excel') }}">
                        @csrf
                        @foreach ($filters as $key => $value)
                            @if ($value !== null && $value !== '')
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach
                        <button type="submit" class="inline-flex items-center rounded-lg border border-green-200 px-3 py-1.5 text-sm text-green-700 opacity-50 cursor-not-allowed" disabled title="Segera hadir">Excel</button>
                    </form>
                    <form method="POST" action="{{ route('reports.export.pdf') }}">
                        @csrf
                        @foreach ($filters as $key => $value)
                            @if ($value !== null && $value !== '')
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach
                        <button type="submit" class="inline-flex items-center rounded-lg border border-red-200 px-3 py-1.5 text-sm text-red-700 opacity-50 cursor-not-allowed" disabled title="Segera hadir">PDF</button>
                    </form>
                </div>
            </div>
            @include('reports.tables', ['type' => $result['type'], 'rows' => $result['rows']])
            @if ($result['rows'] instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
                <div class="mt-4 pt-4 border-t border-slate-200">
                    {{ $result['rows']->links() }}
                </div>
            @endif
        </x-card>
    @endif
</x-admin-layout>

<x-admin-layout title="Edit Periode PMB">
    <div class="mb-6">
        <a href="{{ route('master.pmb-periods.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">← Kembali</a>
        <h2 class="mt-2 text-xl font-semibold text-slate-900">Edit Periode PMB</h2>
    </div>

    <x-card>
        <form method="POST" action="{{ route('master.pmb-periods.update', $period) }}">
            @csrf
            @method('PUT')
            @include('master.pmb-periods._form', ['period' => $period])
            <div class="mt-6 flex gap-2">
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Perbarui</button>
                <a href="{{ route('master.pmb-periods.index') }}" class="inline-flex items-center rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Batal</a>
            </div>
        </form>
    </x-card>
</x-admin-layout>

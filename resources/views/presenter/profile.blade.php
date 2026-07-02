<x-admin-layout title="Profil Saya">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-bonusku-navy">Profil Saya</h2>
        <p class="text-sm text-bonusku-slate">Informasi akun presenter Anda.</p>
    </div>

    <x-card class="max-w-2xl">
        <dl class="space-y-4 text-sm">
            <div><dt class="text-slate-500">Nama Presenter</dt><dd class="mt-1 font-semibold text-bonusku-navy">{{ $presenter->name }}</dd></div>
            <div><dt class="text-slate-500">Kategori</dt><dd class="mt-1">{{ $presenter->category?->name }}</dd></div>
            <div><dt class="text-slate-500">Email</dt><dd class="mt-1">{{ $presenter->email }}</dd></div>
            <div><dt class="text-slate-500">Nomor HP</dt><dd class="mt-1">{{ $presenter->phone }}</dd></div>
            <div><dt class="text-slate-500">Bank</dt><dd class="mt-1">{{ $presenter->bank_name }}</dd></div>
            <div><dt class="text-slate-500">Nomor Rekening</dt><dd class="mt-1">{{ $maskedAccount }}</dd></div>
            <div><dt class="text-slate-500">Atas Nama Rekening</dt><dd class="mt-1">{{ $presenter->account_holder_name }}</dd></div>
            <div><dt class="text-slate-500">Status Akun</dt><dd class="mt-1"><x-status-badge :status="$presenter->status" /></dd></div>
        </dl>

        <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            Perubahan data rekening hanya dapat dilakukan melalui Admin PMB.
        </div>
    </x-card>
</x-admin-layout>

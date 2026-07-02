<x-admin-layout title="Edit Presenter">
    <div class="mb-6">
        <a href="{{ route('master.presenters.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">← Kembali</a>
        <h2 class="mt-2 text-xl font-semibold text-slate-900">Edit Presenter</h2>
    </div>

    <x-card>
        <form method="POST" action="{{ route('master.presenters.update', $presenter) }}">
            @csrf
            @method('PUT')
            @include('master.presenters._form', ['presenter' => $presenter, 'categories' => $categories])
            <div class="mt-6 flex flex-wrap gap-2">
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Perbarui</button>
                <a href="{{ route('master.presenters.index') }}" class="inline-flex items-center rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Batal</a>
            </div>
        </form>

        <div class="mt-6 border-t border-slate-200 pt-6">
            <h3 class="text-sm font-semibold text-bonusku-navy">Akun Login Presenter</h3>
            @if ($presenter->user_id)
                <p class="mt-1 text-sm text-bonusku-slate">
                    Akun login sudah dibuat untuk <strong>{{ $presenter->email }}</strong>.
                    @if ($presenter->account_created_at)
                        Dibuat pada {{ $presenter->account_created_at->format('d/m/Y H:i') }}.
                    @endif
                </p>
                <form method="POST" action="{{ route('master.presenters.resend-account-email', $presenter) }}" class="mt-4">
                    @csrf
                    <button
                        type="submit"
                        class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-medium text-amber-800 hover:bg-amber-100"
                        data-confirm="Kirim ulang email akun dengan password baru ke {{ $presenter->email }}?"
                        data-confirm-title="Kirim Ulang Email Akun"
                    >
                        Kirim Ulang Email Akun
                    </button>
                </form>
            @else
                <p class="mt-1 text-sm text-bonusku-slate">Presenter ini belum memiliki akun login.</p>
                <form method="POST" action="{{ route('master.presenters.resend-account-email', $presenter) }}" class="mt-4">
                    @csrf
                    <button
                        type="submit"
                        class="rounded-lg border border-indigo-300 bg-indigo-50 px-4 py-2 text-sm font-medium text-indigo-800 hover:bg-indigo-100"
                        data-confirm="Buat akun login dan kirim email ke {{ $presenter->email }}?"
                        data-confirm-title="Buat & Kirim Akun Presenter"
                    >
                        Buat & Kirim Email Akun
                    </button>
                </form>
            @endif
        </div>
    </x-card>
</x-admin-layout>

<x-admin-layout title="Ubah Password">
    <div class="mb-6 max-w-lg">
        <h2 class="text-xl font-bold text-bonusku-navy">Ubah Password</h2>
        <p class="mt-1 text-sm text-bonusku-slate">
            @if (auth()->user()->must_change_password)
                Anda wajib mengganti password sementara sebelum melanjutkan.
            @else
                Perbarui password akun presenter Anda.
            @endif
        </p>
    </div>

    <x-card class="max-w-lg">
        <form method="POST" action="{{ route('presenter.change-password.update') }}" class="space-y-4">
            @csrf
            <div>
                <label for="current_password" class="mb-1 block text-sm font-semibold text-bonusku-navy">Password Lama</label>
                <input type="password" name="current_password" id="current_password" required class="bonusku-input @error('current_password') !border-red-500 @enderror">
                @error('current_password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password" class="mb-1 block text-sm font-semibold text-bonusku-navy">Password Baru</label>
                <input type="password" name="password" id="password" required class="bonusku-input @error('password') !border-red-500 @enderror">
                @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password_confirmation" class="mb-1 block text-sm font-semibold text-bonusku-navy">Konfirmasi Password Baru</label>
                <input type="password" name="password_confirmation" id="password_confirmation" required class="bonusku-input">
            </div>
            <button type="submit" class="rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-2.5 text-sm font-bold text-white hover:from-indigo-700 hover:to-violet-700">
                Simpan Password
            </button>
        </form>
    </x-card>
</x-admin-layout>

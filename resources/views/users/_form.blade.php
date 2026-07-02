@php
    use App\Enums\UserRole;
    use App\Enums\UserStatus;
    $user = $user ?? null;
    $isEdit = filled($user);
@endphp

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Nama <span class="text-red-600">*</span></label>
        <input type="text" name="name" id="name"
               class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('name') border-red-500 @enderror"
               value="{{ old('name', $user?->name) }}" required>
        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email <span class="text-red-600">*</span></label>
        <input type="email" name="email" id="email"
               class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('email') border-red-500 @enderror"
               value="{{ old('email', $user?->email) }}" required>
        @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>

<div class="grid gap-4 sm:grid-cols-2 mt-4">
    <div>
        <label for="phone" class="block text-sm font-medium text-slate-700 mb-1">Nomor WhatsApp <span class="text-red-600">*</span></label>
        <input type="text" name="phone" id="phone"
               class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('phone') border-red-500 @enderror"
               value="{{ old('phone', $user?->phone) }}" placeholder="08xxxxxxxxxx" required>
        @error('phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        <p class="mt-1 text-xs text-slate-500">Format: 08xx, 628xx, atau +628xx</p>
    </div>
    <div>
        <label for="role" class="block text-sm font-medium text-slate-700 mb-1">Role <span class="text-red-600">*</span></label>
        <select name="role" id="role" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('role') border-red-500 @enderror" required>
            <option value="">Pilih role</option>
            @foreach (UserRole::cases() as $roleOption)
                <option value="{{ $roleOption->value }}" @selected(old('role', $user?->role?->value) === $roleOption->value)>
                    {{ $roleOption->label() }}
                </option>
            @endforeach
        </select>
        @error('role')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>

<div class="mt-4">
    <label for="status" class="block text-sm font-medium text-slate-700 mb-1">Status <span class="text-red-600">*</span></label>
    <select name="status" id="status" class="block w-full max-w-xs rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('status') border-red-500 @enderror" required>
        @foreach (UserStatus::cases() as $statusOption)
            <option value="{{ $statusOption->value }}" @selected(old('status', $user?->status?->value ?? UserStatus::Aktif->value) === $statusOption->value)>
                {{ $statusOption->label() }}
            </option>
        @endforeach
    </select>
    @error('status')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>

<div id="bankFields" class="mt-6 hidden rounded-xl border border-amber-100 bg-amber-50/50 p-4">
    <h3 class="text-sm font-semibold text-slate-900 mb-1">Data Rekening Keuangan</h3>
    <p class="text-xs text-slate-500 mb-4">Wajib diisi untuk user dengan role Keuangan. Data ini digunakan otomatis saat transfer dana.</p>
    <div class="grid gap-4 sm:grid-cols-3">
        <div>
            <label for="bank_name" class="block text-sm font-medium text-slate-700 mb-1">Bank <span class="text-red-600">*</span></label>
            <input type="text" name="bank_name" id="bank_name"
                   class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('bank_name') border-red-500 @enderror"
                   value="{{ old('bank_name', $user?->bank_name) }}">
            @error('bank_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="account_number" class="block text-sm font-medium text-slate-700 mb-1">Nomor Rekening <span class="text-red-600">*</span></label>
            <input type="text" name="account_number" id="account_number"
                   class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('account_number') border-red-500 @enderror"
                   value="{{ old('account_number', $user?->account_number) }}">
            @error('account_number')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="account_holder_name" class="block text-sm font-medium text-slate-700 mb-1">Atas Nama Rekening <span class="text-red-600">*</span></label>
            <input type="text" name="account_holder_name" id="account_holder_name"
                   class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('account_holder_name') border-red-500 @enderror"
                   value="{{ old('account_holder_name', $user?->account_holder_name) }}">
            @error('account_holder_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>
</div>

<div class="grid gap-4 sm:grid-cols-2 mt-4">
    <div>
        <label for="password" class="block text-sm font-medium text-slate-700 mb-1">
            Password @if (! $isEdit)<span class="text-red-600">*</span>@endif
        </label>
        <input type="password" name="password" id="password"
               class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('password') border-red-500 @enderror"
               @if (! $isEdit) required @endif autocomplete="new-password">
        @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        @if ($isEdit)
            <p class="mt-1 text-xs text-slate-500">Kosongkan jika tidak ingin mengubah password.</p>
        @endif
    </div>
    <div>
        <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1">Konfirmasi Password</label>
        <input type="password" name="password_confirmation" id="password_confirmation"
               class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
               @if (! $isEdit) required @endif autocomplete="new-password">
    </div>
</div>

@push('scripts')
<script>
(function () {
    const roleSelect = document.getElementById('role');
    const bankFields = document.getElementById('bankFields');
    const bankInputs = bankFields?.querySelectorAll('input[name="bank_name"], input[name="account_number"], input[name="account_holder_name"]');

    function toggleBankFields() {
        const isKeuangan = roleSelect?.value === '{{ UserRole::Keuangan->value }}';
        if (isKeuangan) {
            bankFields?.classList.remove('hidden');
            bankInputs?.forEach((input) => input.required = true);
        } else {
            bankFields?.classList.add('hidden');
            bankInputs?.forEach((input) => input.required = false);
        }
    }

    roleSelect?.addEventListener('change', toggleBankFields);
    toggleBankFields();
})();
</script>
@endpush

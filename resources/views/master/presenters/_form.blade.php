@php $presenter = $presenter ?? null; @endphp

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label for="presenter_category_id" class="block text-sm font-medium text-slate-700 mb-1">Kategori Presenter <span class="text-red-600">*</span></label>
        <select name="presenter_category_id" id="presenter_category_id"
                class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('presenter_category_id') border-red-500 @enderror" required>
            <option value="">Pilih kategori</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}"
                    @selected(old('presenter_category_id', $presenter?->presenter_category_id) == $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
        @error('presenter_category_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Nama Presenter <span class="text-red-600">*</span></label>
        <input type="text" name="name" id="name"
               class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('name') border-red-500 @enderror"
               value="{{ old('name', $presenter?->name) }}" required>
        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>

<div class="grid gap-4 sm:grid-cols-3 mt-4">
    <div>
        <label for="bank_name" class="block text-sm font-medium text-slate-700 mb-1">Bank <span class="text-red-600">*</span></label>
        <input type="text" name="bank_name" id="bank_name"
               class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('bank_name') border-red-500 @enderror"
               value="{{ old('bank_name', $presenter?->bank_name) }}" required>
        @error('bank_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="account_number" class="block text-sm font-medium text-slate-700 mb-1">Nomor Rekening <span class="text-red-600">*</span></label>
        <input type="text" name="account_number" id="account_number"
               class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('account_number') border-red-500 @enderror"
               value="{{ old('account_number', $presenter?->account_number) }}" required>
        @error('account_number')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="account_holder_name" class="block text-sm font-medium text-slate-700 mb-1">Atas Nama Rekening <span class="text-red-600">*</span></label>
        <input type="text" name="account_holder_name" id="account_holder_name"
               class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('account_holder_name') border-red-500 @enderror"
               value="{{ old('account_holder_name', $presenter?->account_holder_name) }}" required>
        @error('account_holder_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>

<div class="grid gap-4 sm:grid-cols-2 mt-4">
    <div>
        <label for="phone" class="block text-sm font-medium text-slate-700 mb-1">Nomor HP <span class="text-red-600">*</span></label>
        <input type="text" name="phone" id="phone"
               class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('phone') border-red-500 @enderror"
               value="{{ old('phone', $presenter?->phone) }}" placeholder="08xxxxxxxxxx" required>
        @error('phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email <span class="text-red-600">*</span></label>
        <input type="email" name="email" id="email"
               class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('email') border-red-500 @enderror"
               value="{{ old('email', $presenter?->email) }}" required>
        @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>

<div class="mt-4">
    <label for="address" class="block text-sm font-medium text-slate-700 mb-1">Alamat</label>
    <textarea name="address" id="address" rows="2"
              class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('address') border-red-500 @enderror">{{ old('address', $presenter?->address) }}</textarea>
    @error('address')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>

<div class="mt-4">
    <label for="note" class="block text-sm font-medium text-slate-700 mb-1">Catatan</label>
    <textarea name="note" id="note" rows="2"
              class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('note') border-red-500 @enderror">{{ old('note', $presenter?->note) }}</textarea>
    @error('note')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>

<div class="mt-4">
    <label for="status" class="block text-sm font-medium text-slate-700 mb-1">Status <span class="text-red-600">*</span></label>
    <select name="status" id="status" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('status') border-red-500 @enderror" required>
        <option value="aktif" @selected(old('status', $presenter?->status?->value ?? 'aktif') === 'aktif')>Aktif</option>
        <option value="nonaktif" @selected(old('status', $presenter?->status?->value) === 'nonaktif')>Nonaktif</option>
    </select>
    @error('status')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>

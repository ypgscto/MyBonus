<div class="grid gap-4 sm:grid-cols-12">
    <div class="sm:col-span-4">
        <label class="block text-sm font-medium text-slate-700 mb-1">NIM / No. Pendaftaran <span class="text-red-600">*</span></label>
        <input type="text" name="nim" class="nim-check-input block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('nim') border-red-500 @enderror"
               value="{{ old('nim') }}" required autocomplete="off">
        @error('nim')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div class="sm:col-span-8">
        <label class="block text-sm font-medium text-slate-700 mb-1">Nama Mahasiswa <span class="text-red-600">*</span></label>
        <input type="text" name="student_name" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('student_name') border-red-500 @enderror"
               value="{{ old('student_name') }}" required>
        @error('student_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div class="sm:col-span-4">
        <label class="block text-sm font-medium text-slate-700 mb-1">Tanggal Lahir</label>
        <input type="date" name="birth_date" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('birth_date') border-red-500 @enderror"
               value="{{ old('birth_date') }}">
        @error('birth_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div class="sm:col-span-4">
        <label class="block text-sm font-medium text-slate-700 mb-1">Tanggal Bayar</label>
        <input type="date" name="payment_date" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('payment_date') border-red-500 @enderror"
               value="{{ old('payment_date') }}">
        @error('payment_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div class="sm:col-span-4">
        <label class="block text-sm font-medium text-slate-700 mb-1">Bukti Pembayaran</label>
        <input type="file" name="payment_proof" accept=".jpg,.jpeg,.png,.pdf"
               class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('payment_proof') border-red-500 @enderror">
        @error('payment_proof')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        <p class="mt-1 text-xs text-slate-500">JPG, JPEG, PNG, PDF — maks. 5 MB</p>
    </div>
    <div class="sm:col-span-12">
        <label class="block text-sm font-medium text-slate-700 mb-1">Catatan</label>
        <textarea name="note" rows="2" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('note') }}</textarea>
    </div>
</div>

@foreach ($errors->getMessages() as $key => $messages)
    @if (str_starts_with($key, 'details.'))
        <div class="mt-3 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ $messages[0] }}</div>
    @endif
@endforeach

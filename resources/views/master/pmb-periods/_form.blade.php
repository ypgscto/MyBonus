@php $period = $period ?? null; @endphp

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label for="academic_year" class="block text-sm font-medium text-slate-700 mb-1">Tahun Akademik <span class="text-red-600">*</span></label>
        <input type="text" name="academic_year" id="academic_year"
               class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('academic_year') border-red-500 @enderror"
               value="{{ old('academic_year', $period?->academic_year) }}"
               placeholder="2026/2027" required>
        @error('academic_year')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="wave" class="block text-sm font-medium text-slate-700 mb-1">Gelombang <span class="text-red-600">*</span></label>
        <input type="text" name="wave" id="wave"
               class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('wave') border-red-500 @enderror"
               value="{{ old('wave', $period?->wave) }}"
               placeholder="Gelombang 1" required>
        @error('wave')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>

<div class="grid gap-4 sm:grid-cols-2 mt-4">
    <div>
        <label for="start_date" class="block text-sm font-medium text-slate-700 mb-1">Tanggal Mulai <span class="text-red-600">*</span></label>
        <input type="date" name="start_date" id="start_date"
               class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('start_date') border-red-500 @enderror"
               value="{{ old('start_date', $period?->start_date?->format('Y-m-d')) }}" required>
        @error('start_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="end_date" class="block text-sm font-medium text-slate-700 mb-1">Tanggal Selesai <span class="text-red-600">*</span></label>
        <input type="date" name="end_date" id="end_date"
               class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('end_date') border-red-500 @enderror"
               value="{{ old('end_date', $period?->end_date?->format('Y-m-d')) }}" required>
        @error('end_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>

<div class="mt-4">
    <label for="status" class="block text-sm font-medium text-slate-700 mb-1">Status <span class="text-red-600">*</span></label>
    <select name="status" id="status" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('status') border-red-500 @enderror" required>
        <option value="aktif" @selected(old('status', $period?->status?->value ?? 'aktif') === 'aktif')>Aktif</option>
        <option value="nonaktif" @selected(old('status', $period?->status?->value) === 'nonaktif')>Nonaktif</option>
    </select>
    @error('status')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>

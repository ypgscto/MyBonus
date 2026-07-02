@php $scheme = $scheme ?? null; @endphp

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label for="presenter_category_id" class="block text-sm font-medium text-slate-700 mb-1">Kategori Presenter <span class="text-red-600">*</span></label>
        <select name="presenter_category_id" id="presenter_category_id"
                class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('presenter_category_id') border-red-500 @enderror" required>
            <option value="">Pilih kategori</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}"
                    @selected(old('presenter_category_id', $scheme?->presenter_category_id) == $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
        @error('presenter_category_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="pmb_period_id" class="block text-sm font-medium text-slate-700 mb-1">Periode PMB <span class="text-red-600">*</span></label>
        <select name="pmb_period_id" id="pmb_period_id"
                class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('pmb_period_id') border-red-500 @enderror" required>
            <option value="">Pilih periode</option>
            @foreach ($periods as $period)
                <option value="{{ $period->id }}"
                    @selected(old('pmb_period_id', $scheme?->pmb_period_id) == $period->id)>
                    {{ $period->academic_year }} – {{ $period->wave }}
                </option>
            @endforeach
        </select>
        @error('pmb_period_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>

<div class="grid gap-4 sm:grid-cols-2 mt-4">
    <div>
        <label for="commission_amount_per_student" class="block text-sm font-medium text-slate-700 mb-1">Nominal Komisi per Mahasiswa <span class="text-red-600">*</span></label>
        <div class="flex rounded-lg shadow-sm">
            <span class="inline-flex items-center rounded-l-lg border border-r-0 border-slate-300 bg-slate-50 px-3 text-sm text-slate-500">Rp</span>
            <input type="number" name="commission_amount_per_student" id="commission_amount_per_student"
                   class="block w-full rounded-r-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('commission_amount_per_student') border-red-500 @enderror"
                   value="{{ old('commission_amount_per_student', $scheme?->commission_amount_per_student) }}"
                   min="0" step="0.01" required>
        </div>
        @error('commission_amount_per_student')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="status" class="block text-sm font-medium text-slate-700 mb-1">Status <span class="text-red-600">*</span></label>
        <select name="status" id="status" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('status') border-red-500 @enderror" required>
            <option value="aktif" @selected(old('status', $scheme?->status?->value ?? 'aktif') === 'aktif')>Aktif</option>
            <option value="nonaktif" @selected(old('status', $scheme?->status?->value) === 'nonaktif')>Nonaktif</option>
        </select>
        @error('status')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>

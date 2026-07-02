@php $category = $category ?? null; @endphp

<div>
    <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Nama Kategori <span class="text-red-600">*</span></label>
    <input type="text" name="name" id="name"
           class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('name') border-red-500 @enderror"
           value="{{ old('name', $category?->name) }}" required>
    @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>

<div class="mt-4">
    <label for="description" class="block text-sm font-medium text-slate-700 mb-1">Deskripsi</label>
    <textarea name="description" id="description" rows="3"
              class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('description') border-red-500 @enderror">{{ old('description', $category?->description) }}</textarea>
    @error('description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>

<div class="mt-4">
    <label for="status" class="block text-sm font-medium text-slate-700 mb-1">Status <span class="text-red-600">*</span></label>
    <select name="status" id="status" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('status') border-red-500 @enderror" required>
        <option value="aktif" @selected(old('status', $category?->status?->value ?? 'aktif') === 'aktif')>Aktif</option>
        <option value="nonaktif" @selected(old('status', $category?->status?->value) === 'nonaktif')>Nonaktif</option>
    </select>
    @error('status')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>

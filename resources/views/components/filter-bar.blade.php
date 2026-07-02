<form method="GET" {{ $attributes->merge(['class' => 'mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4 items-end']) }}>
    {{ $slot }}
    <div class="flex gap-2 sm:col-span-2 lg:col-span-1">
        <button type="submit" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
            Filter
        </button>
        <a href="{{ request()->url() }}" class="inline-flex items-center rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            Reset
        </a>
    </div>
</form>

<form method="POST" action="{{ $action }}" class="inline">
    @csrf
    @method('PATCH')
    <button type="submit"
            data-confirm="{{ $confirm }}"
            data-confirm-title="{{ $status->value === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' }}"
            title="{{ $status->value === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' }}"
            class="inline-flex items-center rounded-lg border px-2.5 py-1.5 text-sm {{ $status->value === 'aktif' ? 'border-amber-200 text-amber-700 hover:bg-amber-50' : 'border-green-200 text-green-700 hover:bg-green-50' }}">
        @if ($status->value === 'aktif')
            <x-icon name="x-circle" class="h-4 w-4" />
        @else
            <x-icon name="check" class="h-4 w-4" />
        @endif
    </button>
</form>

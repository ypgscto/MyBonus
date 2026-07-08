@props([
    'request',
])

@php
    $note = $request->bankTransferNote();
@endphp

<x-card header="Notes Transfer Bank">
    <p class="mb-3 text-xs text-slate-500">
        Salin teks berikut ke kolom keterangan/berita transfer saat melakukan transfer bank.
    </p>
    <div class="flex items-start gap-2 rounded-lg border border-slate-200 bg-slate-50 p-3">
        <p id="bankTransferNote-{{ $request->id }}" class="flex-1 break-all text-sm font-medium text-slate-900">{{ $note }}</p>
        <x-copy-text-button
            :target-id="'bankTransferNote-'.$request->id"
            label="Salin"
            title="Salin notes transfer"
            class="shrink-0"
        />
    </div>
</x-card>

@props(['preview', 'live' => false])

@php
    $available = $preview['available'] ?? false;
    $totalStudents = (int) ($preview['total_students'] ?? 0);
    $perStudent = $preview['commission_per_student'] ?? null;
    $totalCommission = (float) ($preview['total_commission'] ?? 0);
@endphp

<x-card header="Estimasi Komisi" class="mb-6" {{ $attributes->merge(['id' => $live ? 'commission-preview-card' : null]) }}>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-xs font-medium uppercase tracking-wide text-amber-700">Preview Live</p>
            <p class="mt-1 text-sm text-slate-500">Dikunci otomatis saat permintaan dikirim ke Verifikator.</p>
        </div>
        <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">Draft</span>
    </div>

    <div class="mt-4 grid gap-4 sm:grid-cols-3" @if($live) id="commission-preview-values" @endif>
        <div>
            <span class="text-xs text-slate-500 block">Mahasiswa</span>
            <span class="text-lg font-bold text-slate-900" data-field="total_students">{{ $totalStudents }}</span>
        </div>
        <div>
            <span class="text-xs text-slate-500 block">Komisi / Mahasiswa</span>
            <span class="text-lg font-bold text-slate-900" data-field="commission_per_student">
                @if ($perStudent !== null)
                    Rp {{ number_format($perStudent, 0, ',', '.') }}
                @else
                    -
                @endif
            </span>
        </div>
        <div>
            <span class="text-xs text-slate-500 block">Total Komisi</span>
            <span class="text-lg font-bold text-amber-600" data-field="total_commission">
                Rp {{ number_format($totalCommission, 0, ',', '.') }}
            </span>
        </div>
    </div>

    <p class="mt-4 text-sm @if($available) text-slate-500 @else text-red-600 @endif" data-field="message" @if($available && empty($preview['message'])) hidden @endif>
        {{ $preview['message'] ?? '' }}
    </p>

    @if (! empty($preview['presenter_category']) || ! empty($preview['pmb_period_label']))
        <p class="mt-2 text-xs text-slate-500" data-field="meta">
            {{ $preview['presenter_category'] ?? '-' }} · {{ $preview['pmb_period_label'] ?? '-' }}
        </p>
    @endif
</x-card>

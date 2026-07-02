@props(['request'])

@php
    use App\Enums\PresenterRequestStatus;

    $status = $request->status;
    $isRejected = $status === PresenterRequestStatus::RejectedByVerifikator;

    $steps = [
        ['key' => 'draft', 'label' => 'Draft'],
        ['key' => 'submitted', 'label' => 'Submitted'],
        ['key' => 'approved', 'label' => 'Approved'],
        ['key' => 'transfer_finance', 'label' => 'Transfer ke Keuangan'],
        ['key' => 'received', 'label' => 'Dana Diterima'],
        ['key' => 'transfer_presenter', 'label' => 'Transfer ke Presenter'],
        ['key' => 'closed', 'label' => 'Closed'],
    ];

    $progressIndex = match ($status) {
        PresenterRequestStatus::Draft => 0,
        PresenterRequestStatus::Submitted => 1,
        PresenterRequestStatus::RejectedByVerifikator => 1,
        PresenterRequestStatus::ApprovedByVerifikator => 2,
        PresenterRequestStatus::TransferredToFinance => 3,
        PresenterRequestStatus::ReceivedByFinance => 4,
        PresenterRequestStatus::TransferredToPresenter => 5,
        PresenterRequestStatus::Closed => 6,
        PresenterRequestStatus::Cancelled => 0,
        default => 0,
    };
@endphp

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-200/80 bg-white p-5 shadow-card']) }}>
    <h3 class="mb-4 text-sm font-semibold text-bonusku-navy">Progress Workflow</h3>

    @if ($isRejected)
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <p class="font-semibold">Permintaan ditolak oleh Verifikator</p>
            @if ($request->rejection_reason)
                <p class="mt-1 text-red-700">{{ $request->rejection_reason }}</p>
            @endif
        </div>
    @endif

    <div class="overflow-x-auto pb-2">
        <ol class="flex min-w-[640px] items-center justify-between gap-1">
            @foreach ($steps as $index => $step)
                @php
                    $isComplete = ! $isRejected && $index < $progressIndex;
                    $isActive = ! $isRejected && $index === $progressIndex;
                    $isRejectedStep = $isRejected && $index === 1;
                    $isPending = ! $isComplete && ! $isActive && ! $isRejectedStep;

                    $circleClass = match (true) {
                        $isRejectedStep => 'bg-red-500 text-white ring-red-200',
                        $isComplete => 'bg-emerald-500 text-white ring-emerald-200',
                        $isActive => 'bg-gradient-to-br from-indigo-500 to-violet-600 text-white ring-indigo-200 shadow-md shadow-indigo-500/30',
                        default => 'bg-slate-100 text-slate-400 ring-slate-200',
                    };

                    $labelClass = match (true) {
                        $isRejectedStep => 'text-red-600 font-semibold',
                        $isComplete => 'text-emerald-700 font-medium',
                        $isActive => 'text-indigo-700 font-semibold',
                        default => 'text-slate-400',
                    };
                @endphp
                <li class="flex flex-1 flex-col items-center text-center">
                    <div class="flex w-full items-center">
                        @if ($index > 0)
                            <div class="h-0.5 flex-1 {{ $index <= $progressIndex && ! $isRejected ? 'bg-emerald-400' : 'bg-slate-200' }}"></div>
                        @endif
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold ring-2 {{ $circleClass }}">
                            @if ($isComplete)
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            @elseif ($isRejectedStep)
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            @else
                                {{ $index + 1 }}
                            @endif
                        </div>
                        @if ($index < count($steps) - 1)
                            <div class="h-0.5 flex-1 {{ $index < $progressIndex && ! $isRejected ? 'bg-emerald-400' : 'bg-slate-200' }}"></div>
                        @endif
                    </div>
                    <span class="mt-2 max-w-[5.5rem] text-[10px] leading-tight sm:text-xs {{ $labelClass }}">{{ $step['label'] }}</span>
                </li>
            @endforeach
        </ol>
    </div>
</div>

<?php

namespace App\Http\Controllers\Master;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StorePmbPeriodRequest;
use App\Http\Requests\Master\UpdatePmbPeriodRequest;
use App\Models\PmbPeriod;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PmbPeriodController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    public function index(Request $request): View
    {
        $periods = PmbPeriod::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($q) use ($search) {
                    $q->where('academic_year', 'like', "%{$search}%")
                        ->orWhere('wave', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('master.pmb-periods.index', compact('periods'));
    }

    public function create(): View
    {
        return view('master.pmb-periods.create');
    }

    public function store(StorePmbPeriodRequest $request): RedirectResponse
    {
        $period = PmbPeriod::create($request->validated());
        $this->auditLog->logPmbPeriodCreated($period);

        return redirect()
            ->route('master.pmb-periods.index')
            ->with('status', 'Periode PMB berhasil ditambahkan.');
    }

    public function edit(PmbPeriod $pmbPeriod): View
    {
        return view('master.pmb-periods.edit', ['period' => $pmbPeriod]);
    }

    public function update(UpdatePmbPeriodRequest $request, PmbPeriod $pmbPeriod): RedirectResponse
    {
        $oldAttributes = $pmbPeriod->toArray();
        $pmbPeriod->update($request->validated());

        return redirect()
            ->route('master.pmb-periods.index')
            ->with('status', 'Periode PMB berhasil diperbarui.');
    }

    public function toggleStatus(PmbPeriod $pmbPeriod): RedirectResponse
    {
        $oldAttributes = $pmbPeriod->only(['status']);
        $newStatus = $pmbPeriod->status === RecordStatus::Aktif
            ? RecordStatus::Nonaktif
            : RecordStatus::Aktif;

        $pmbPeriod->update(['status' => $newStatus]);

        $message = $newStatus === RecordStatus::Aktif
            ? 'Periode PMB berhasil diaktifkan.'
            : 'Periode PMB berhasil dinonaktifkan.';

        return redirect()
            ->route('master.pmb-periods.index')
            ->with('status', $message);
    }
}

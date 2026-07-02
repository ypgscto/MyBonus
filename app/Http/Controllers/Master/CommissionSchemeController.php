<?php

namespace App\Http\Controllers\Master;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreCommissionSchemeRequest;
use App\Http\Requests\Master\UpdateCommissionSchemeRequest;
use App\Models\CommissionScheme;
use App\Models\PresenterCategory;
use App\Models\PmbPeriod;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommissionSchemeController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    public function index(Request $request): View
    {
        $schemes = CommissionScheme::query()
            ->with(['presenterCategory', 'pmbPeriod'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($q) use ($search) {
                    $q->whereHas('presenterCategory', fn ($cq) => $cq->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('pmbPeriod', function ($pq) use ($search) {
                            $pq->where('academic_year', 'like', "%{$search}%")
                                ->orWhere('wave', 'like', "%{$search}%");
                        });
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('master.commission-schemes.index', compact('schemes'));
    }

    public function create(): View
    {
        $categories = PresenterCategory::query()
            ->where('status', RecordStatus::Aktif)
            ->orderBy('name')
            ->get();

        $periods = PmbPeriod::query()
            ->where('status', RecordStatus::Aktif)
            ->orderByDesc('start_date')
            ->get();

        return view('master.commission-schemes.create', compact('categories', 'periods'));
    }

    public function store(StoreCommissionSchemeRequest $request): RedirectResponse
    {
        $scheme = CommissionScheme::create($request->validated());
        $this->auditLog->logCommissionSchemeCreated($scheme);

        return redirect()
            ->route('master.commission-schemes.index')
            ->with('status', 'Skema komisi berhasil ditambahkan.');
    }

    public function edit(CommissionScheme $commissionScheme): View
    {
        $categories = PresenterCategory::query()
            ->where('status', RecordStatus::Aktif)
            ->orWhere('id', $commissionScheme->presenter_category_id)
            ->orderBy('name')
            ->get();

        $periods = PmbPeriod::query()
            ->where('status', RecordStatus::Aktif)
            ->orWhere('id', $commissionScheme->pmb_period_id)
            ->orderByDesc('start_date')
            ->get();

        return view('master.commission-schemes.edit', [
            'scheme' => $commissionScheme,
            'categories' => $categories,
            'periods' => $periods,
        ]);
    }

    public function update(UpdateCommissionSchemeRequest $request, CommissionScheme $commissionScheme): RedirectResponse
    {
        $oldAttributes = $commissionScheme->toArray();
        $commissionScheme->update($request->validated());

        return redirect()
            ->route('master.commission-schemes.index')
            ->with('status', 'Skema komisi berhasil diperbarui.');
    }

    public function toggleStatus(CommissionScheme $commissionScheme): RedirectResponse
    {
        $oldAttributes = $commissionScheme->only(['status']);
        $newStatus = $commissionScheme->status === RecordStatus::Aktif
            ? RecordStatus::Nonaktif
            : RecordStatus::Aktif;

        $commissionScheme->update(['status' => $newStatus]);

        $message = $newStatus === RecordStatus::Aktif
            ? 'Skema komisi berhasil diaktifkan.'
            : 'Skema komisi berhasil dinonaktifkan.';

        return redirect()
            ->route('master.commission-schemes.index')
            ->with('status', $message);
    }
}

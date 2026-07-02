<?php

namespace App\Http\Controllers\Master;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StorePresenterCategoryRequest;
use App\Http\Requests\Master\UpdatePresenterCategoryRequest;
use App\Models\PresenterCategory;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PresenterCategoryController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    public function index(Request $request): View
    {
        $categories = PresenterCategory::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('master.presenter-categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('master.presenter-categories.create');
    }

    public function store(StorePresenterCategoryRequest $request): RedirectResponse
    {
        $category = PresenterCategory::create($request->validated());
        $this->auditLog->logPresenterCategoryCreated($category);

        return redirect()
            ->route('master.presenter-categories.index')
            ->with('status', 'Kategori presenter berhasil ditambahkan.');
    }

    public function edit(PresenterCategory $presenterCategory): View
    {
        return view('master.presenter-categories.edit', ['category' => $presenterCategory]);
    }

    public function update(UpdatePresenterCategoryRequest $request, PresenterCategory $presenterCategory): RedirectResponse
    {
        $oldAttributes = $presenterCategory->toArray();
        $presenterCategory->update($request->validated());
        $this->auditLog->logPresenterCategoryUpdated($presenterCategory, $oldAttributes);

        return redirect()
            ->route('master.presenter-categories.index')
            ->with('status', 'Kategori presenter berhasil diperbarui.');
    }

    public function toggleStatus(PresenterCategory $presenterCategory): RedirectResponse
    {
        $oldAttributes = $presenterCategory->only(['status']);
        $newStatus = $presenterCategory->status === RecordStatus::Aktif
            ? RecordStatus::Nonaktif
            : RecordStatus::Aktif;

        $presenterCategory->update(['status' => $newStatus]);

        if ($newStatus === RecordStatus::Nonaktif) {
            $this->auditLog->logPresenterCategoryDeactivated($presenterCategory, $oldAttributes);
        } else {
            $this->auditLog->logPresenterCategoryUpdated($presenterCategory, $oldAttributes);
        }

        $message = $newStatus === RecordStatus::Aktif
            ? 'Kategori presenter berhasil diaktifkan.'
            : 'Kategori presenter berhasil dinonaktifkan.';

        return redirect()
            ->route('master.presenter-categories.index')
            ->with('status', $message);
    }
}

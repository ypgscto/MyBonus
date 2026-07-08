<?php

namespace App\Http\Controllers\Master;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StorePresenterRequest;
use App\Http\Requests\Master\UpdatePresenterRequest;
use App\Models\Presenter;
use App\Models\PresenterCategory;
use App\Services\AuditLogService;
use App\Services\PresenterAccountService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PresenterController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
        private readonly PresenterAccountService $presenterAccountService,
    ) {}

    public function index(Request $request): View
    {
        $presenters = Presenter::query()
            ->with(['category', 'user'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('account_number', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('master.presenters.index', compact('presenters'));
    }

    public function create(): View
    {
        $categories = PresenterCategory::query()
            ->where('status', RecordStatus::Aktif)
            ->orderBy('name')
            ->get();

        return view('master.presenters.create', compact('categories'));
    }

    public function store(StorePresenterRequest $request): RedirectResponse
    {
        $plainPassword = null;

        try {
            $presenter = DB::transaction(function () use ($request, &$plainPassword) {
                $presenter = Presenter::create($request->validated());
                $this->auditLog->logPresenterCreated($presenter);
                $plainPassword = $this->presenterAccountService->provisionAccount($presenter);

                return $presenter->fresh();
            });
        } catch (UniqueConstraintViolationException $exception) {
            if (str_contains($exception->getMessage(), 'users_email_unique')) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors([
                        'email' => 'Email sudah digunakan oleh user atau presenter lain.',
                    ]);
            }

            throw $exception;
        }

        $emailResult = $plainPassword
            ? $this->presenterAccountService->sendAccountEmail($presenter, $plainPassword)
            : ['success' => true];

        $redirect = redirect()
            ->route('master.presenters.index')
            ->with('status', 'Presenter berhasil ditambahkan.');

        if (! $emailResult['success']) {
            $redirect->with('warning', $emailResult['message'] ?? 'Email akun gagal dikirim.');
        }

        return $redirect;
    }

    public function edit(Presenter $presenter): View
    {
        $presenter->load('user');
        $categories = PresenterCategory::query()
            ->where('status', RecordStatus::Aktif)
            ->orWhere('id', $presenter->presenter_category_id)
            ->orderBy('name')
            ->get();

        return view('master.presenters.edit', compact('presenter', 'categories'));
    }

    public function update(UpdatePresenterRequest $request, Presenter $presenter): RedirectResponse
    {
        $oldAttributes = $presenter->toArray();
        $presenter->update($request->validated());

        if ($presenter->user) {
            $presenter->user->update([
                'name' => $presenter->name,
                'email' => $presenter->email,
                'phone' => $presenter->phone,
            ]);
        }

        $this->auditLog->logPresenterUpdated($presenter, $oldAttributes);

        return redirect()
            ->route('master.presenters.index')
            ->with('status', 'Presenter berhasil diperbarui.');
    }

    public function toggleStatus(Presenter $presenter): RedirectResponse
    {
        $oldAttributes = $presenter->only(['status']);
        $newStatus = $presenter->status === RecordStatus::Aktif
            ? RecordStatus::Nonaktif
            : RecordStatus::Aktif;

        $presenter->update(['status' => $newStatus]);

        if ($newStatus === RecordStatus::Nonaktif) {
            $this->auditLog->logPresenterDeactivated($presenter, $oldAttributes);
        } else {
            $this->auditLog->logPresenterUpdated($presenter, $oldAttributes);
        }

        $message = $newStatus === RecordStatus::Aktif
            ? 'Presenter berhasil diaktifkan.'
            : 'Presenter berhasil dinonaktifkan.';

        return redirect()
            ->route('master.presenters.index')
            ->with('status', $message);
    }

    public function resendAccountEmail(Presenter $presenter): RedirectResponse
    {
        $result = $this->presenterAccountService->resendAccountEmail($presenter);

        return redirect()
            ->route('master.presenters.edit', $presenter)
            ->with($result['success'] ? 'status' : 'warning', $result['message']);
    }
}

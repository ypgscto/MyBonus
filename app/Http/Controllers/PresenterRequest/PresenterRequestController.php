<?php

namespace App\Http\Controllers\PresenterRequest;

use App\Enums\PresenterRequestStatus;
use App\Enums\RecordStatus;
use App\Enums\UserRole;
use App\Exceptions\DuplicateNimValidationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\PresenterRequest\StorePresenterRequestWithDetailsRequest;
use App\Http\Requests\PresenterRequest\UpdatePresenterRequestHeaderRequest;
use App\Models\Presenter;
use App\Models\PresenterRequest;
use App\Models\PresenterRequestDetail;
use App\Models\PmbPeriod;
use App\Services\AuditLogService;
use App\Services\PaymentProofService;
use App\Services\PresenterRequestSubmitService;
use App\Support\NotificationFlash;
use App\Support\RequestCodeGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PresenterRequestController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
        private readonly RequestCodeGenerator $requestCodeGenerator,
        private readonly PresenterRequestSubmitService $submitService,
        private readonly PaymentProofService $paymentProofService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', PresenterRequest::class);

        $user = $request->user();
        $query = PresenterRequest::query()
            ->with(['presenter.category', 'pmbPeriod', 'creator'])
            ->withCount('details');

        $query = match ($user->role) {
            UserRole::SuperAdmin => $query,
            UserRole::AdminPmb => $query->where('created_by', $user->id),
            UserRole::Verifikator => $query->whereIn('status', [
                PresenterRequestStatus::Submitted,
                PresenterRequestStatus::RejectedByVerifikator,
                PresenterRequestStatus::ApprovedByVerifikator,
                PresenterRequestStatus::TransferredToFinance,
                PresenterRequestStatus::ReceivedByFinance,
                PresenterRequestStatus::TransferredToPresenter,
                PresenterRequestStatus::Closed,
            ]),
            UserRole::Keuangan => $query->whereIn('status', [
                PresenterRequestStatus::TransferredToFinance,
                PresenterRequestStatus::ReceivedByFinance,
                PresenterRequestStatus::TransferredToPresenter,
                PresenterRequestStatus::Closed,
            ]),
            default => $query->whereRaw('1 = 0'),
        };

        $requests = $query
            ->when($request->filled('search'), function ($builder) use ($request) {
                $search = $request->string('search')->toString();
                $builder->where(function ($q) use ($search) {
                    $q->where('request_code', 'like', "%{$search}%")
                        ->orWhereHas('presenter', fn ($pq) => $pq->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest('submitted_at')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $pageTitle = match ($user->role) {
            UserRole::SuperAdmin => 'Semua Permintaan',
            UserRole::Verifikator => 'Daftar Permintaan',
            UserRole::Keuangan => 'Daftar Permintaan Keuangan',
            default => 'Permintaan Presenter',
        };

        return view('presenter-requests.index', compact('requests', 'pageTitle'));
    }

    public function create(): View
    {
        $this->authorize('create', PresenterRequest::class);

        $periods = PmbPeriod::query()->where('status', RecordStatus::Aktif)->orderByDesc('start_date')->get();
        $presenters = Presenter::query()->with('category')->where('status', RecordStatus::Aktif)->orderBy('name')->get();

        return view('presenter-requests.create', compact('periods', 'presenters'));
    }

    public function storeDraft(StorePresenterRequestWithDetailsRequest $request): RedirectResponse
    {
        $this->authorize('create', PresenterRequest::class);

        $presenterRequest = PresenterRequest::create([
            'request_code' => $this->requestCodeGenerator->generate(),
            'pmb_period_id' => $request->pmb_period_id,
            'presenter_id' => $request->presenter_id,
            'created_by' => $request->user()->id,
            'status' => PresenterRequestStatus::Draft,
            'request_date' => now()->toDateString(),
            'admin_note' => $request->admin_note,
        ]);

        $this->auditLog->logDraftCreated($presenterRequest);
        $this->persistDetails($request, $presenterRequest);

        if ($request->input('action') === 'submit') {
            try {
                $result = $this->submitService->submit($presenterRequest->fresh(), $request->user()->id);

                return NotificationFlash::apply(
                    redirect()
                        ->route('presenter-requests.show', $presenterRequest)
                        ->with('status', 'Permintaan berhasil dikirim ke Verifikator.'),
                    $result->notifications
                );
            } catch (DuplicateNimValidationException $e) {
                $this->auditLog->logDuplicateNimFailed($presenterRequest, $e->report);

                return redirect()
                    ->route('presenter-requests.edit', $presenterRequest)
                    ->with('duplicate_nim_message', $e->getMessage())
                    ->with('duplicate_nim_report', $e->report)
                    ->with('error', $e->getMessage());
            } catch (\Illuminate\Validation\ValidationException $e) {
                return redirect()
                    ->route('presenter-requests.edit', $presenterRequest)
                    ->withErrors($e->errors())
                    ->with('error', 'Permintaan gagal dikirim. Periksa kembali data calon mahasiswa.');
            }
        }

        return redirect()
            ->route('presenter-requests.edit', $presenterRequest)
            ->with('status', 'Draft permintaan berhasil disimpan. Anda dapat melanjutkan pengisian data.');
    }

    public function drafts(Request $request): View
    {
        $this->authorize('viewAny', PresenterRequest::class);

        $requests = PresenterRequest::query()
            ->with(['presenter.category', 'pmbPeriod'])
            ->withCount('details')
            ->when($request->user()->role !== UserRole::SuperAdmin, fn ($q) => $q->where('created_by', $request->user()->id))
            ->draft()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($q) use ($search) {
                    $q->where('request_code', 'like', "%{$search}%")
                        ->orWhereHas('presenter', fn ($pq) => $pq->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('presenter-requests.drafts', compact('requests'));
    }

    public function history(Request $request): View
    {
        $this->authorize('viewAny', PresenterRequest::class);

        $requests = PresenterRequest::query()
            ->with(['presenter.category', 'pmbPeriod'])
            ->when($request->user()->role !== UserRole::SuperAdmin, fn ($q) => $q->where('created_by', $request->user()->id))
            ->history()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($q) use ($search) {
                    $q->where('request_code', 'like', "%{$search}%")
                        ->orWhereHas('presenter', fn ($pq) => $pq->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest('submitted_at')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('presenter-requests.history', compact('requests'));
    }

    public function show(PresenterRequest $presenterRequest): View
    {
        $this->authorize('view', $presenterRequest);

        $presenterRequest->load(['presenter.category', 'pmbPeriod', 'details', 'submitter', 'creator', 'notificationLogs' => fn ($q) => $q->latest('created_at')]);

        return view('presenter-requests.show', ['request' => $presenterRequest]);
    }

    public function edit(PresenterRequest $presenterRequest): View
    {
        $this->authorize('update', $presenterRequest);

        $presenterRequest->load(['presenter.category', 'pmbPeriod', 'details']);
        $periods = PmbPeriod::query()->where('status', RecordStatus::Aktif)->orderByDesc('start_date')->get();
        $presenters = Presenter::query()->with('category')->where('status', RecordStatus::Aktif)->orderBy('name')->get();

        return view('presenter-requests.edit', [
            'request' => $presenterRequest,
            'periods' => $periods,
            'presenters' => $presenters,
        ]);
    }

    public function update(UpdatePresenterRequestHeaderRequest $httpRequest, PresenterRequest $presenterRequest): RedirectResponse
    {
        $this->authorize('update', $presenterRequest);

        $oldAttributes = $presenterRequest->toArray();
        $presenterRequest->update($httpRequest->validated());
        $this->auditLog->logDraftUpdated($presenterRequest, $oldAttributes);

        return redirect()
            ->route('presenter-requests.edit', $presenterRequest)
            ->with('status', 'Data permintaan berhasil diperbarui.');
    }

    public function submit(Request $request, PresenterRequest $presenterRequest): RedirectResponse
    {
        $this->authorize('submit', $presenterRequest);

        try {
            $result = $this->submitService->submit($presenterRequest, $request->user()->id);
        } catch (DuplicateNimValidationException $e) {
            $this->auditLog->logDuplicateNimFailed($presenterRequest, $e->report);

            return redirect()
                ->route('presenter-requests.edit', $presenterRequest)
                ->with('duplicate_nim_message', $e->getMessage())
                ->with('duplicate_nim_report', $e->report)
                ->with('error', $e->getMessage());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()
                ->route('presenter-requests.edit', $presenterRequest)
                ->withErrors($e->errors())
                ->with('error', 'Permintaan gagal dikirim. Periksa kembali data calon mahasiswa.');
        }

        return NotificationFlash::apply(
            redirect()
                ->route('presenter-requests.show', $presenterRequest)
                ->with('status', 'Permintaan berhasil dikirim ke Verifikator.'),
            $result->notifications
        );
    }

    public function checkNim(Request $httpRequest, PresenterRequest $presenterRequest): JsonResponse
    {
        $this->authorize('update', $presenterRequest);

        $nim = trim($httpRequest->string('nim')->toString());
        $excludeDetailId = $httpRequest->integer('exclude_detail_id') ?: null;

        if ($nim === '') {
            return response()->json([
                'valid' => false,
                'message' => 'NIM wajib diisi.',
            ]);
        }

        $validator = app(\App\Services\DuplicateNimValidatorService::class);

        $existingDetails = $presenterRequest->details()
            ->when($excludeDetailId, fn ($q) => $q->where('id', '!=', $excludeDetailId))
            ->get()
            ->each(fn ($detail) => $detail->setRelation('presenterRequest', $presenterRequest));

        $simulated = $existingDetails->push(new PresenterRequestDetail([
            'nim' => $nim,
            'student_name' => $httpRequest->string('student_name')->toString() ?: '-',
        ]));

        $within = $validator->validateWithinCurrentRequest($simulated);
        $blocking = $validator->getBlockingConflictsOnly($presenterRequest->id, [$nim]);
        $warnings = $validator->getDraftWarningsOnly($presenterRequest->id, [$nim]);

        return response()->json([
            'valid' => empty($within) && empty($blocking),
            'within_current' => ! empty($within),
            'blocking' => $blocking,
            'warnings' => $warnings,
        ]);
    }

    public function presenterInfo(Presenter $presenter): JsonResponse
    {
        $this->authorize('create', PresenterRequest::class);

        $presenter->load('category');

        return response()->json([
            'id' => $presenter->id,
            'name' => $presenter->name,
            'category' => $presenter->category?->name,
            'bank_name' => $presenter->bank_name,
            'account_number' => $presenter->account_number,
            'account_holder_name' => $presenter->account_holder_name,
            'phone' => $presenter->phone,
            'email' => $presenter->email,
        ]);
    }

    private function persistDetails(StorePresenterRequestWithDetailsRequest $request, PresenterRequest $presenterRequest): void
    {
        foreach ($request->input('details', []) as $index => $row) {
            if (empty($row['nim']) && empty($row['student_name'])) {
                continue;
            }

            $data = [
                'presenter_request_id' => $presenterRequest->id,
                'nim' => $row['nim'],
                'student_name' => $row['student_name'],
                'birth_date' => $row['birth_date'] ?? null,
                'payment_date' => $row['payment_date'] ?? null,
                'note' => $row['note'] ?? null,
            ];

            $file = $request->file("details.{$index}.payment_proof");
            if ($file) {
                [$filename] = $this->paymentProofService->store($file);
                $data['payment_proof_file'] = $filename;
            }

            PresenterRequestDetail::create($data);
        }

        if ($presenterRequest->details()->exists()) {
            $this->auditLog->logDraftUpdated($presenterRequest->fresh(), $presenterRequest->toArray(), [
                'detail_action' => 'bulk_added_on_create',
            ]);
        }
    }
}

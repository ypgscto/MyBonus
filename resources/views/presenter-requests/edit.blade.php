<x-admin-layout title="Edit Draft Permintaan">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-2">
        <div>
            <a href="{{ route('presenter-requests.drafts') }}" class="text-sm text-indigo-600 hover:text-indigo-800">← Kembali ke Draft</a>
            <h2 class="mt-2 text-xl font-semibold text-slate-900">Edit Draft — {{ $request->request_code }}</h2>
        </div>
        <x-request-status-badge :status="$request->status" />
    </div>

    @include('presenter-requests.partials.duplicate-nim-report')

    <x-card header="Data Permintaan" class="mb-6">
        <form method="POST" action="{{ route('presenter-requests.update', $request) }}">
            @csrf
            @method('PUT')
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="pmb_period_id" class="block text-sm font-medium text-slate-700 mb-1">Periode PMB <span class="text-red-600">*</span></label>
                    <select name="pmb_period_id" id="pmb_period_id" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('pmb_period_id') border-red-500 @enderror" required>
                        @foreach ($periods as $period)
                            <option value="{{ $period->id }}" @selected(old('pmb_period_id', $request->pmb_period_id) == $period->id)>
                                {{ $period->academic_year }} – {{ $period->wave }}
                            </option>
                        @endforeach
                    </select>
                    @error('pmb_period_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="presenter_id" class="block text-sm font-medium text-slate-700 mb-1">Presenter <span class="text-red-600">*</span></label>
                    <select name="presenter_id" id="presenter_id" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('presenter_id') border-red-500 @enderror" required>
                        @foreach ($presenters as $presenter)
                            <option value="{{ $presenter->id }}" @selected(old('presenter_id', $request->presenter_id) == $presenter->id)>
                                {{ $presenter->name }} ({{ $presenter->category?->name }})
                            </option>
                        @endforeach
                    </select>
                    @error('presenter_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
            @include('presenter-requests.partials.presenter-info-card')
            <div class="mt-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Tanggal Pengajuan</label>
                <input type="text" class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-500" value="{{ $request->request_date->format('d M Y') }}" readonly disabled>
            </div>
            <div class="mt-4">
                <label for="admin_note" class="block text-sm font-medium text-slate-700 mb-1">Catatan Admin</label>
                <textarea name="admin_note" id="admin_note" rows="2" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('admin_note', $request->admin_note) }}</textarea>
            </div>
            <button type="submit" class="mt-4 inline-flex items-center gap-1 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Simpan Perubahan Header
            </button>
        </form>
    </x-card>

    <x-card class="mb-6">
        <div class="mb-4 -mt-1 pb-4 border-b border-slate-200">
            <h3 class="font-semibold text-slate-900">Calon Mahasiswa ({{ $request->details->count() }})</h3>
        </div>
        <div class="overflow-x-auto -mx-5 -mb-5">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">#</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">NIM</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Nama</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Tgl Lahir</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Tgl Bayar</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Bukti</th>
                        <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($request->details as $detail)
                        <tr>
                            <td class="px-5 py-3 text-sm text-slate-900">{{ $loop->iteration }}</td>
                            <td class="px-5 py-3 text-sm text-slate-900">{{ $detail->nim }}</td>
                            <td class="px-5 py-3 text-sm text-slate-900">{{ $detail->student_name }}</td>
                            <td class="px-5 py-3 text-sm text-slate-600">{{ $detail->birth_date?->format('d/m/Y') ?? '-' }}</td>
                            <td class="px-5 py-3 text-sm text-slate-600">{{ $detail->payment_date?->format('d/m/Y') ?? '-' }}</td>
                            <td class="px-5 py-3 text-sm">
                                @if ($detail->payment_proof_file)
                                    <a href="{{ route('payment-proofs.download', $detail) }}" class="inline-flex items-center rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm text-slate-700 hover:bg-slate-50" target="_blank" title="Bukti">
                                        <x-icon name="document" class="h-4 w-4" />
                                    </a>
                                @else
                                    <span class="text-xs text-slate-500">Belum ada</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-sm text-right space-x-1">
                                <button type="button" class="edit-detail-btn inline-flex items-center rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm text-slate-700 hover:bg-slate-50"
                                        data-id="{{ $detail->id }}"
                                        data-nim="{{ $detail->nim }}"
                                        data-name="{{ $detail->student_name }}"
                                        data-birth="{{ $detail->birth_date?->format('Y-m-d') }}"
                                        data-payment="{{ $detail->payment_date?->format('Y-m-d') }}"
                                        data-note="{{ $detail->note }}">
                                    <x-icon name="pencil" class="h-4 w-4" />
                                </button>
                                <form method="POST" action="{{ route('presenter-requests.details.destroy', [$request, $detail]) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            data-confirm="Hapus calon mahasiswa ini?"
                                            data-confirm-title="Hapus Mahasiswa"
                                            class="inline-flex items-center rounded-lg border border-red-200 px-2.5 py-1.5 text-sm text-red-700 hover:bg-red-50">
                                        <x-icon name="x-circle" class="h-4 w-4" />
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-8 text-center text-sm text-slate-500">Belum ada calon mahasiswa.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <x-card header="Tambah Calon Mahasiswa" class="mb-6">
        <form method="POST" action="{{ route('presenter-requests.details.store', $request) }}" enctype="multipart/form-data" id="addStudentForm">
            @csrf
            @include('presenter-requests.partials.detail-fields', ['prefix' => 'add', 'checkNim' => true, 'requestId' => $request->id])
            <button type="submit" class="mt-4 inline-flex items-center gap-1 rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700">
                Tambah Mahasiswa
            </button>
        </form>
    </x-card>

    <x-card class="border-amber-200">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="font-semibold text-slate-900">Kirim ke Verifikator</h3>
                <p class="text-sm text-slate-500">Pastikan semua data lengkap termasuk bukti pembayaran setiap mahasiswa.</p>
            </div>
            <form method="POST" action="{{ route('presenter-requests.submit', $request) }}">
                @csrf
                <button type="submit"
                        data-confirm="Kirim permintaan ke Verifikator? Data tidak dapat diedit setelah dikirim."
                        data-confirm-title="Kirim ke Verifikator"
                        class="inline-flex items-center gap-1 rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                    <x-icon name="send" class="h-4 w-4" /> Kirim ke Verifikator
                </button>
            </form>
        </div>
    </x-card>

    <x-modal name="edit-detail" maxWidth="2xl">
        <form id="editDetailForm" method="POST" enctype="multipart/form-data" class="p-6">
            @csrf
            @method('PUT')
            <h3 class="text-lg font-semibold text-slate-900 mb-4">Edit Calon Mahasiswa</h3>
            @include('presenter-requests.partials.detail-fields', ['prefix' => 'edit'])
            <p class="text-xs text-slate-500 mb-4">Kosongkan upload jika tidak ingin mengganti bukti pembayaran.</p>
            <div class="flex justify-end gap-3">
                <button type="button" @click="$dispatch('close-modal', 'edit-detail')" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Batal</button>
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Simpan Perubahan</button>
            </div>
        </form>
    </x-modal>

    @push('scripts')
    <script>
    document.querySelectorAll('.edit-detail-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            const form = document.getElementById('editDetailForm');
            form.action = `{{ url('presenter-requests/'.$request->id.'/details') }}/${id}`;
            form.dataset.excludeDetailId = id;
            form.querySelector('[name="nim"]').value = this.getAttribute('data-nim');
            form.querySelector('[name="student_name"]').value = this.getAttribute('data-name');
            form.querySelector('[name="birth_date"]').value = this.getAttribute('data-birth') || '';
            form.querySelector('[name="payment_date"]').value = this.getAttribute('data-payment') || '';
            form.querySelector('[name="note"]').value = this.getAttribute('data-note') || '';
            checkNimField(form.querySelector('[name="nim"]'));
            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'edit-detail' }));
        });
    });

    const checkNimUrl = @json(route('presenter-requests.check-nim', $request));
    const existingNims = @json($request->details->pluck('nim')->values());

    function renderNimFeedback(container, data) {
        if (!container) return;
        container.className = 'mb-3 rounded-lg p-3 text-sm';
        let html = '';
        if (data.within_current) {
            container.classList.add('border', 'border-red-200', 'bg-red-50', 'text-red-800');
            html = '<strong>NIM duplikat!</strong> NIM ini sudah ada dalam permintaan yang sedang dibuat.';
        } else if (data.blocking && data.blocking.length) {
            container.classList.add('border', 'border-red-200', 'bg-red-50', 'text-red-800');
            html = '<strong>NIM tidak dapat digunakan.</strong><ul class="mb-0 mt-2 list-disc pl-5">';
            data.blocking.forEach(row => { html += `<li>${row.detail_message}</li>`; });
            html += '</ul>';
        } else if (data.warnings && data.warnings.length) {
            container.classList.add('border', 'border-amber-200', 'bg-amber-50', 'text-amber-800');
            html = '<strong>Peringatan:</strong><ul class="mb-0 mt-2 list-disc pl-5">';
            data.warnings.forEach(row => { html += `<li>${row.detail_message}</li>`; });
            html += '</ul>';
        } else if (data.valid) {
            container.classList.add('border', 'border-green-200', 'bg-green-50', 'text-green-800');
            html = 'NIM dapat digunakan.';
        } else {
            container.classList.add('hidden');
            return;
        }
        container.classList.remove('hidden');
        container.innerHTML = html;
    }

    async function checkNimField(input) {
        const nim = input.value.trim();
        const container = document.getElementById('nim-live-feedback');
        const form = input.closest('form');
        const excludeDetailId = form?.dataset?.excludeDetailId || '';
        const studentName = form?.querySelector('[name="student_name"]')?.value || '';

        if (!nim) {
            container?.classList.add('hidden');
            return;
        }

        if (!excludeDetailId && existingNims.includes(nim)) {
            renderNimFeedback(container, { within_current: true });
            return;
        }

        try {
            const params = new URLSearchParams({ nim, student_name: studentName });
            if (excludeDetailId) params.set('exclude_detail_id', excludeDetailId);
            const res = await fetch(`${checkNimUrl}?${params.toString()}`);
            const data = await res.json();
            renderNimFeedback(container, data);
        } catch (e) {
            container?.classList.add('hidden');
        }
    }

    document.querySelectorAll('.nim-check-input').forEach(input => {
        input.addEventListener('blur', () => checkNimField(input));
        input.addEventListener('input', () => {
            clearTimeout(input._nimTimer);
            input._nimTimer = setTimeout(() => checkNimField(input), 400);
        });
    });

    document.getElementById('addStudentForm')?.addEventListener('submit', function (e) {
        const nim = this.querySelector('[name="nim"]')?.value?.trim();
        if (nim && existingNims.includes(nim)) {
            e.preventDefault();
            alert('NIM sudah ada dalam permintaan ini.');
        }
    });
    </script>
    @endpush
</x-admin-layout>

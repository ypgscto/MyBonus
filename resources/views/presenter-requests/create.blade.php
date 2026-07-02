<x-admin-layout title="Buat Permintaan Presenter" :breadcrumbs="[
    ['label' => 'Dashboard', 'url' => route(auth()->user()->role->dashboardRoute())],
    ['label' => 'Buat Permintaan'],
]">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-bonusku-navy">Buat Permintaan Presenter</h2>
        <p class="mt-1 text-sm text-bonusku-slate">Ikuti langkah berikut untuk mencatat permintaan dan calon mahasiswa.</p>
    </div>

    <form method="POST" action="{{ route('presenter-requests.store') }}" enctype="multipart/form-data"
          x-data="{ step: 1, rows: [0], next() { if (this.step < 4) this.step++ }, prev() { if (this.step > 1) this.step-- }, addRow() { this.rows.push(this.rows.length) }, removeRow(i) { if (this.rows.length > 1) this.rows.splice(i, 1) } }"
          id="createRequestForm">
        @csrf
        <input type="hidden" name="action" id="form-action" value="draft">

        {{-- Stepper --}}
        <div class="mb-8 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-card sm:p-6">
            <ol class="flex flex-wrap items-center justify-between gap-2">
                @foreach ([1 => 'Periode & Presenter', 2 => 'Data Mahasiswa', 3 => 'Bukti Pembayaran', 4 => 'Review & Submit'] as $num => $label)
                    <li class="flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold transition"
                              :class="step >= {{ $num }} ? 'bg-gradient-to-br from-indigo-600 to-violet-600 text-white shadow-md' : 'bg-slate-100 text-slate-400'">{{ $num }}</span>
                        <span class="hidden text-sm font-medium sm:inline" :class="step >= {{ $num }} ? 'text-indigo-700' : 'text-slate-400'">{{ $label }}</span>
                    </li>
                @endforeach
            </ol>
        </div>

        {{-- Step 1 --}}
        <div x-show="step === 1" x-cloak>
            <x-card header="Informasi Permintaan" class="mb-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="pmb_period_id" class="mb-1 block text-sm font-semibold text-bonusku-navy">Periode PMB <span class="text-red-500">*</span></label>
                        <select name="pmb_period_id" id="pmb_period_id" class="bonusku-input @error('pmb_period_id') !border-red-500 @enderror" required>
                            <option value="">Pilih periode</option>
                            @foreach ($periods as $period)
                                <option value="{{ $period->id }}" @selected(old('pmb_period_id') == $period->id)>{{ $period->academic_year }} – {{ $period->wave }}</option>
                            @endforeach
                        </select>
                        @error('pmb_period_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="presenter_id" class="mb-1 block text-sm font-semibold text-bonusku-navy">Presenter <span class="text-red-500">*</span></label>
                        <select name="presenter_id" id="presenter_id" class="bonusku-input @error('presenter_id') !border-red-500 @enderror" required>
                            <option value="">Pilih presenter</option>
                            @foreach ($presenters as $presenter)
                                <option value="{{ $presenter->id }}" @selected(old('presenter_id') == $presenter->id)>{{ $presenter->name }} ({{ $presenter->category?->name }})</option>
                            @endforeach
                        </select>
                        @error('presenter_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                @include('presenter-requests.partials.presenter-info-card')
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-bonusku-navy">Tanggal Pengajuan</label>
                        <input type="text" class="bonusku-input bg-slate-50 text-bonusku-slate" value="{{ now()->format('d M Y') }}" readonly disabled>
                    </div>
                    <div>
                        <label for="admin_note" class="mb-1 block text-sm font-semibold text-bonusku-navy">Catatan Admin</label>
                        <textarea name="admin_note" id="admin_note" rows="2" class="bonusku-input">{{ old('admin_note') }}</textarea>
                    </div>
                </div>
            </x-card>
            <div class="flex justify-end">
                <button type="button" @click="next()" class="rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md">Lanjut →</button>
            </div>
        </div>

        {{-- Step 2 & 3 combined in one visible section for form fields --}}
        <div x-show="step === 2 || step === 3" x-cloak>
            <x-card header="Detail Calon Mahasiswa" class="mb-4">
                <div class="mb-4 flex justify-between">
                    <p class="text-sm text-bonusku-slate">Tambahkan data calon mahasiswa beserta bukti pembayaran.</p>
                    <button type="button" @click="addRow()" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">+ Tambah Baris</button>
                </div>
                <template x-for="(row, index) in rows" :key="row">
                    <div class="mb-4 rounded-xl border border-slate-200 bg-slate-50/50 p-4">
                        <div class="mb-3 flex justify-between">
                            <span class="text-sm font-semibold text-bonusku-navy" x-text="'Mahasiswa #' + (index + 1)"></span>
                            <button type="button" x-show="rows.length > 1" @click="removeRow(index)" class="text-xs text-red-600">Hapus</button>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-12">
                            <div class="sm:col-span-4">
                                <label class="mb-1 block text-xs font-medium text-bonusku-slate">NIM</label>
                                <input type="text" :name="'details[' + index + '][nim]'" class="bonusku-input" required>
                            </div>
                            <div class="sm:col-span-8">
                                <label class="mb-1 block text-xs font-medium text-bonusku-slate">Nama Mahasiswa</label>
                                <input type="text" :name="'details[' + index + '][student_name]'" class="bonusku-input" required>
                            </div>
                            <div class="sm:col-span-4">
                                <label class="mb-1 block text-xs font-medium text-bonusku-slate">Tanggal Lahir</label>
                                <input type="date" :name="'details[' + index + '][birth_date]'" class="bonusku-input">
                            </div>
                            <div class="sm:col-span-4">
                                <label class="mb-1 block text-xs font-medium text-bonusku-slate">Tanggal Bayar</label>
                                <input type="date" :name="'details[' + index + '][payment_date]'" class="bonusku-input">
                            </div>
                            <div class="sm:col-span-4">
                                <label class="mb-1 block text-xs font-medium text-bonusku-slate">Bukti Pembayaran</label>
                                <input type="file" :name="'details[' + index + '][payment_proof]'" accept=".jpg,.jpeg,.png,.pdf" class="bonusku-input text-xs">
                            </div>
                            <div class="sm:col-span-12">
                                <label class="mb-1 block text-xs font-medium text-bonusku-slate">Catatan</label>
                                <textarea :name="'details[' + index + '][note]'" rows="2" class="bonusku-input"></textarea>
                            </div>
                        </div>
                    </div>
                </template>
            </x-card>
            <div class="flex justify-between">
                <button type="button" @click="step = 1" class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">← Kembali</button>
                <button type="button" @click="step = 4" class="rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md">Review →</button>
            </div>
        </div>

        {{-- Step 4 Review --}}
        <div x-show="step === 4" x-cloak>
            <x-card header="Review Pengajuan" class="mb-4">
                <p class="text-sm text-bonusku-slate">Pastikan semua data sudah benar sebelum menyimpan draft atau mengirim ke Verifikator.</p>
                <ul class="mt-4 space-y-2 text-sm text-bonusku-navy">
                    <li class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-emerald-500"></span> Periode PMB & presenter sudah dipilih</li>
                    <li class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-emerald-500"></span> Data calon mahasiswa terisi</li>
                    <li class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-amber-500"></span> Untuk kirim ke Verifikator: semua field wajib lengkap termasuk bukti bayar</li>
                </ul>
            </x-card>
            <div class="flex flex-wrap justify-between gap-3">
                <button type="button" @click="step = 2" class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">← Edit Data</button>
                <div class="flex flex-wrap gap-3">
                    <button type="submit" onclick="document.getElementById('form-action').value='draft'" class="inline-flex items-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-5 py-2.5 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">
                        <x-icon name="document-text" class="h-4 w-4" /> Simpan Draft
                    </button>
                    <button type="submit" onclick="document.getElementById('form-action').value='submit'"
                            data-confirm="Kirim permintaan ke Verifikator? Data tidak dapat diedit setelah dikirim."
                            data-confirm-title="Kirim ke Verifikator"
                            class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md hover:from-emerald-600 hover:to-teal-700">
                        <x-icon name="send" class="h-4 w-4" /> Kirim ke Verifikator
                    </button>
                </div>
            </div>
        </div>
    </form>
</x-admin-layout>

<div
    x-data="{
        open: false,
        title: '',
        message: '',
        form: null,
        show(event) {
            this.title = event.detail.title || 'Konfirmasi';
            this.message = event.detail.message || 'Apakah Anda yakin ingin melanjutkan?';
            this.form = event.detail.form || null;
            this.open = true;
        },
        confirm() {
            if (this.form) this.form.submit();
            this.open = false;
        }
    }"
    x-on:open-confirm.window="show($event)"
    x-show="open"
    x-cloak
    class="relative z-[100]"
    role="dialog"
    aria-modal="true"
>
    <div x-show="open" x-transition.opacity class="fixed inset-0 bg-bonusku-navy/60 backdrop-blur-sm"></div>
    <div class="fixed inset-0 z-[100] flex items-center justify-center p-4">
        <div x-show="open" x-transition class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-200">
            <div class="bg-gradient-to-r from-indigo-600 to-violet-600 px-6 py-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20">
                        <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-white" x-text="title"></h3>
                </div>
            </div>
            <div class="px-6 py-5">
                <p class="text-sm text-bonusku-slate" x-text="message"></p>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="open = false" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Batal
                    </button>
                    <button type="button" @click="confirm()" class="rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md hover:from-indigo-700 hover:to-violet-700">
                        Ya, Lanjutkan
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

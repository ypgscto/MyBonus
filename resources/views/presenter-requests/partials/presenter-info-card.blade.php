<div id="presenter-info-card" class="mt-4 hidden overflow-hidden rounded-xl border border-indigo-100 bg-gradient-to-br from-indigo-50/80 to-violet-50/50 p-5 ring-1 ring-indigo-100">
    <h3 class="mb-3 flex items-center gap-2 text-sm font-semibold text-indigo-800">
        <x-icon name="presenter" class="h-4 w-4" /> Informasi Presenter
    </h3>
    <div class="grid gap-3 sm:grid-cols-3">
        <div><span class="text-xs font-medium text-bonusku-slate block">Nama</span><span id="pi-name" class="font-semibold text-bonusku-navy">-</span></div>
        <div><span class="text-xs font-medium text-bonusku-slate block">Kategori</span><span id="pi-category" class="text-bonusku-navy">-</span></div>
        <div><span class="text-xs font-medium text-bonusku-slate block">Bank</span><span id="pi-bank" class="text-bonusku-navy">-</span></div>
        <div><span class="text-xs font-medium text-bonusku-slate block">Nomor Rekening</span><span id="pi-account" class="text-bonusku-navy">-</span></div>
        <div><span class="text-xs font-medium text-bonusku-slate block">Atas Nama</span><span id="pi-holder" class="text-bonusku-navy">-</span></div>
        <div><span class="text-xs font-medium text-bonusku-slate block">No. HP</span><span id="pi-phone" class="text-bonusku-navy">-</span></div>
        <div class="sm:col-span-3"><span class="text-xs font-medium text-bonusku-slate block">Email</span><span id="pi-email" class="text-bonusku-navy">-</span></div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const select = document.getElementById('presenter_id');
    const card = document.getElementById('presenter-info-card');
    if (!select || !card) return;
    async function loadPresenter(id) {
        if (!id) { card.classList.add('hidden'); return; }
        try {
            const res = await fetch(`{{ url('presenter-requests/presenters') }}/${id}/info`);
            const data = await res.json();
            document.getElementById('pi-name').textContent = data.name || '-';
            document.getElementById('pi-category').textContent = data.category || '-';
            document.getElementById('pi-bank').textContent = data.bank_name || '-';
            document.getElementById('pi-account').textContent = data.account_number || '-';
            document.getElementById('pi-holder').textContent = data.account_holder_name || '-';
            document.getElementById('pi-phone').textContent = data.phone || '-';
            document.getElementById('pi-email').textContent = data.email || '-';
            card.classList.remove('hidden');
        } catch (e) { card.classList.add('hidden'); }
    }
    select.addEventListener('change', () => loadPresenter(select.value));
    if (select.value) loadPresenter(select.value);
});
</script>
@endpush

<x-admin-layout title="Dashboard Keuangan">
    <x-hero-card subtitle="Kelola dana masuk, konfirmasi penerimaan, dan pencairan komisi ke presenter.">
        <x-slot name="actions">
            <x-primary-button :href="route('keuangan.requests.incoming')">
                <x-icon name="inbox" class="h-4 w-4" /> Dana Masuk
            </x-primary-button>
        </x-slot>
    </x-hero-card>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5 mb-8">
        <x-stat-card label="Dana Masuk" :value="$counts['awaiting_confirmation']" color="blue" icon="inbox" :href="route('keuangan.requests.incoming')" />
        <x-stat-card label="Belum Diteruskan" :value="$counts['awaiting_presenter_transfer']" color="gold" icon="inbox" :href="route('keuangan.requests.to-transfer')" />
        <x-stat-card label="Sudah Transfer Presenter" :value="$counts['transferred_to_presenter']" color="emerald" icon="send" :href="route('keuangan.requests.disbursement-history')" />
        <x-stat-card label="Closed" :value="$counts['closed']" color="slate" icon="lock" :href="route('keuangan.requests.closed')" />
        <x-stat-card label="Total Pencairan Bulan Ini" :value="$monthlyTransferAmount" color="purple" icon="currency" :money="true" />
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        <x-card header="Aksi Cepat">
            <div class="space-y-2">
                <a href="{{ route('keuangan.requests.incoming') }}" class="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3 text-sm transition hover:border-indigo-200 hover:bg-indigo-50/50">
                    <span class="flex items-center gap-2 font-medium text-bonusku-navy"><x-icon name="inbox" class="h-4 w-4 text-indigo-500" /> Dana Masuk dari Verifikator</span>
                    <span class="rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-bold text-indigo-800">{{ $counts['awaiting_confirmation'] }}</span>
                </a>
                <a href="{{ route('keuangan.requests.to-transfer') }}" class="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3 text-sm transition hover:border-amber-200 hover:bg-amber-50/50">
                    <span class="flex items-center gap-2 font-medium text-bonusku-navy"><x-icon name="send" class="h-4 w-4 text-amber-500" /> Transfer ke Presenter</span>
                    <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-bold text-amber-800">{{ $counts['awaiting_presenter_transfer'] }}</span>
                </a>
            </div>
        </x-card>
        <x-card header="Ringkasan {{ now()->translatedFormat('F Y') }}">
            <ul class="divide-y divide-slate-100">
                <li class="flex justify-between py-3 text-sm">
                    <span class="text-bonusku-slate">Pencairan bulan ini</span>
                    <span class="font-bold text-emerald-600">Rp {{ number_format($monthlyTransferAmount, 0, ',', '.') }}</span>
                </li>
                <li class="flex justify-between py-3 text-sm">
                    <span class="text-bonusku-slate">Dana diterima</span>
                    <span class="font-bold text-bonusku-navy">{{ $counts['received'] }}</span>
                </li>
            </ul>
        </x-card>
    </div>
</x-admin-layout>

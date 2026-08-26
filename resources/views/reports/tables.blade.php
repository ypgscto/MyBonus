@php
    use App\Enums\ReportType;
    use App\Services\ReportService;
    $th = 'px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500';
    $td = 'px-5 py-3 text-sm text-slate-900';
@endphp

<div class="overflow-x-auto -mx-5 -mb-5">
    @switch($type)
        @case(ReportType::PresenterRequests)
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="{{ $th }}">Kode</th><th class="{{ $th }}">Tanggal</th><th class="{{ $th }}">Periode</th><th class="{{ $th }}">Presenter</th><th class="{{ $th }}">Admin</th>
                        <th class="{{ $th }}">Mahasiswa</th><th class="{{ $th }}">Total Komisi</th><th class="{{ $th }}">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($rows as $row)
                        <tr>
                            <td class="{{ $td }} font-semibold">{{ $row->request_code }}</td>
                            <td class="{{ $td }}">{{ $row->request_date?->format('d/m/Y') }}</td>
                            <td class="{{ $td }}">{{ $row->pmbPeriod?->academic_year }} – {{ $row->pmbPeriod?->wave }}</td>
                            <td class="{{ $td }}">{{ $row->presenter?->name }}</td>
                            <td class="{{ $td }}">{{ $row->creator?->name }}</td>
                            <td class="{{ $td }}">{{ $row->total_students }}</td>
                            <td class="{{ $td }}">Rp {{ number_format($row->total_commission, 0, ',', '.') }}</td>
                            <td class="{{ $td }}"><x-request-status-badge :status="$row->status" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-5 py-8 text-center text-sm text-slate-500">Tidak ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @break

        @case(ReportType::StudentDetails)
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="{{ $th }}">Kode Permintaan</th><th class="{{ $th }}">Presenter</th><th class="{{ $th }}">Periode</th><th class="{{ $th }}">NIM</th><th class="{{ $th }}">Nama Mahasiswa</th><th class="{{ $th }}">Tgl Lahir</th><th class="{{ $th }}">Tgl Bayar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($rows as $row)
                        <tr>
                            <td class="{{ $td }}">{{ $row->presenterRequest?->request_code }}</td>
                            <td class="{{ $td }}">{{ $row->presenterRequest?->presenter?->name }}</td>
                            <td class="{{ $td }}">{{ $row->presenterRequest?->pmbPeriod?->academic_year }} – {{ $row->presenterRequest?->pmbPeriod?->wave }}</td>
                            <td class="{{ $td }}">{{ $row->nim }}</td>
                            <td class="{{ $td }}">{{ $row->student_name }}</td>
                            <td class="{{ $td }}">{{ $row->birth_date?->format('d/m/Y') ?? '-' }}</td>
                            <td class="{{ $td }}">{{ $row->payment_date?->format('d/m/Y') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-8 text-center text-sm text-slate-500">Tidak ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @break

        @case(ReportType::DuplicateNim)
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="{{ $th }}">NIM</th><th class="{{ $th }}">Nama Mahasiswa</th><th class="{{ $th }}">Kode Permintaan</th><th class="{{ $th }}">Presenter</th><th class="{{ $th }}">Periode</th><th class="{{ $th }}">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($rows as $row)
                        <tr>
                            <td class="{{ $td }} font-semibold">{{ $row->nim }}</td>
                            <td class="{{ $td }}">{{ $row->student_name }}</td>
                            <td class="{{ $td }}">{{ $row->presenterRequest?->request_code }}</td>
                            <td class="{{ $td }}">{{ $row->presenterRequest?->presenter?->name }}</td>
                            <td class="{{ $td }}">{{ $row->presenterRequest?->pmbPeriod?->academic_year }} – {{ $row->presenterRequest?->pmbPeriod?->wave }}</td>
                            <td class="{{ $td }}"><x-request-status-badge :status="$row->presenterRequest?->status" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-8 text-center text-sm text-slate-500">Tidak ada NIM duplikat pada filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @break

        @case(ReportType::VerifikatorTransfers)
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="{{ $th }}">Kode</th>
                        <th class="{{ $th }}">Presenter</th>
                        <th class="{{ $th }}">Tgl Transfer</th>
                        <th class="{{ $th }}">Nominal</th>
                        <th class="{{ $th }}">Verifikator</th>
                        <th class="{{ $th }}">Penerima Keuangan</th>
                        <th class="{{ $th }}">Catatan</th>
                        <th class="{{ $th }}">Bukti TF</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($rows as $row)
                        @php $transfer = $row->verifikatorTransfer; @endphp
                        <tr>
                            <td class="{{ $td }}">{{ $row->request_code }}</td>
                            <td class="{{ $td }}">{{ $row->presenter?->name }}</td>
                            <td class="{{ $td }}">{{ $transfer?->transfer_date?->format('d/m/Y') }}</td>
                            <td class="{{ $td }}">Rp {{ number_format($transfer?->transfer_amount ?? 0, 0, ',', '.') }}</td>
                            <td class="{{ $td }}">{{ $transfer?->transferrer?->name }}</td>
                            <td class="{{ $td }}">{{ $transfer?->financeUser?->name }}</td>
                            <td class="{{ $td }} text-slate-600">{{ $transfer?->note ?? '-' }}</td>
                            <td class="{{ $td }}">
                                @if ($transfer?->transfer_proof_file)
                                    <a href="{{ route('verifikator-transfer-proofs.download', $row) }}"
                                       target="_blank"
                                       class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                       title="Unduh bukti transfer">
                                        <x-icon name="document" class="h-4 w-4" /> Unduh
                                    </a>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-5 py-8 text-center text-sm text-slate-500">Tidak ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @break

        @case(ReportType::PresenterTransfers)
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="{{ $th }}">Kode</th><th class="{{ $th }}">Presenter</th><th class="{{ $th }}">Tgl Transfer</th><th class="{{ $th }}">Nominal</th><th class="{{ $th }}">Rekening Tujuan</th><th class="{{ $th }}">Keuangan</th><th class="{{ $th }}">Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($rows as $row)
                        @php $transfer = $row->presenterTransfer; @endphp
                        <tr>
                            <td class="{{ $td }}">{{ $row->request_code }}</td>
                            <td class="{{ $td }}">{{ $row->presenter?->name }}</td>
                            <td class="{{ $td }}">{{ $transfer?->transfer_date?->format('d/m/Y') }}</td>
                            <td class="{{ $td }}">Rp {{ number_format($transfer?->transfer_amount ?? 0, 0, ',', '.') }}</td>
                            <td class="{{ $td }} text-slate-600">{{ $transfer?->destination_bank }} – {{ $transfer?->destination_account_number }}</td>
                            <td class="{{ $td }}">{{ $transfer?->transferrer?->name }}</td>
                            <td class="{{ $td }} text-slate-600">{{ $transfer?->note ?? $row->finance_note ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-8 text-center text-sm text-slate-500">Tidak ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @break

        @case(ReportType::TransferVariance)
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="{{ $th }}">Kode</th><th class="{{ $th }}">Presenter</th><th class="{{ $th }}">Total Komisi</th><th class="{{ $th }}">Transfer Verifikator</th><th class="{{ $th }}">Transfer Presenter</th><th class="{{ $th }}">Selisih</th><th class="{{ $th }}">Catatan Selisih</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($rows as $row)
                        @php
                            $flags = ReportService::varianceFlags($row);
                            $vAmount = $row->verifikatorTransfer?->transfer_amount;
                            $pAmount = $row->presenterTransfer?->transfer_amount;
                            $notes = collect([
                                $flags['verifikator'] && $row->verifikatorTransfer?->note ? 'Verifikator: '.$row->verifikatorTransfer->note : null,
                                $flags['presenter'] && $row->presenterTransfer?->note ? 'Keuangan: '.$row->presenterTransfer->note : null,
                            ])->filter()->implode(' | ');
                        @endphp
                        <tr>
                            <td class="{{ $td }} font-semibold">{{ $row->request_code }}</td>
                            <td class="{{ $td }}">{{ $row->presenter?->name }}</td>
                            <td class="{{ $td }}">Rp {{ number_format($row->total_commission, 0, ',', '.') }}</td>
                            <td class="{{ $td }}">
                                @if ($vAmount !== null)
                                    Rp {{ number_format($vAmount, 0, ',', '.') }}
                                    @if ($flags['verifikator'])<span class="ml-1 inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">Selisih</span>@endif
                                @else - @endif
                            </td>
                            <td class="{{ $td }}">
                                @if ($pAmount !== null)
                                    Rp {{ number_format($pAmount, 0, ',', '.') }}
                                    @if ($flags['presenter'])<span class="ml-1 inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">Selisih</span>@endif
                                @else - @endif
                            </td>
                            <td class="{{ $td }}">
                                @if ($flags['any'])
                                    <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">Ada Selisih</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">Sesuai</span>
                                @endif
                            </td>
                            <td class="{{ $td }} text-slate-500">{{ $notes ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-8 text-center text-sm text-slate-500">Tidak ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @break

        @case(ReportType::Rejected)
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="{{ $th }}">Kode</th><th class="{{ $th }}">Presenter</th><th class="{{ $th }}">Periode</th><th class="{{ $th }}">Ditolak Oleh</th><th class="{{ $th }}">Tanggal</th><th class="{{ $th }}">Alasan Penolakan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($rows as $row)
                        <tr>
                            <td class="{{ $td }}">{{ $row->request_code }}</td>
                            <td class="{{ $td }}">{{ $row->presenter?->name }}</td>
                            <td class="{{ $td }}">{{ $row->pmbPeriod?->academic_year }} – {{ $row->pmbPeriod?->wave }}</td>
                            <td class="{{ $td }}">{{ $row->rejector?->name }}</td>
                            <td class="{{ $td }}">{{ $row->rejected_at?->format('d/m/Y H:i') }}</td>
                            <td class="{{ $td }} text-red-600">{{ $row->rejection_reason }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-8 text-center text-sm text-slate-500">Tidak ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @break

        @case(ReportType::Closed)
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="{{ $th }}">Kode</th><th class="{{ $th }}">Presenter</th><th class="{{ $th }}">Periode</th><th class="{{ $th }}">Total Komisi</th><th class="{{ $th }}">Ditutup Oleh</th><th class="{{ $th }}">Tanggal Closed</th><th class="{{ $th }}">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($rows as $row)
                        <tr>
                            <td class="{{ $td }}">{{ $row->request_code }}</td>
                            <td class="{{ $td }}">{{ $row->presenter?->name }}</td>
                            <td class="{{ $td }}">{{ $row->pmbPeriod?->academic_year }} – {{ $row->pmbPeriod?->wave }}</td>
                            <td class="{{ $td }}">Rp {{ number_format($row->total_commission, 0, ',', '.') }}</td>
                            <td class="{{ $td }}">{{ $row->closer?->name }}</td>
                            <td class="{{ $td }}">{{ $row->closed_at?->format('d/m/Y H:i') }}</td>
                            <td class="{{ $td }}"><x-request-status-badge :status="$row->status" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-8 text-center text-sm text-slate-500">Tidak ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @break

        @case(ReportType::ActivePresenters)
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="{{ $th }}">#</th><th class="{{ $th }}">Presenter</th><th class="{{ $th }}">HP</th><th class="{{ $th }}">Total Permintaan</th><th class="{{ $th }}">Total Mahasiswa</th><th class="{{ $th }}">Total Komisi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($rows as $row)
                        <tr>
                            <td class="{{ $td }}">{{ $loop->iteration + ($rows->currentPage() - 1) * $rows->perPage() }}</td>
                            <td class="{{ $td }} font-semibold">{{ $row->presenter_name }}</td>
                            <td class="{{ $td }}">{{ $row->presenter_phone }}</td>
                            <td class="{{ $td }}">{{ $row->total_requests }}</td>
                            <td class="{{ $td }}">{{ $row->total_students }}</td>
                            <td class="{{ $td }}">Rp {{ number_format($row->total_commission, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-8 text-center text-sm text-slate-500">Tidak ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @break

        @case(ReportType::AuditActivity)
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="{{ $th }}">Waktu</th><th class="{{ $th }}">User</th><th class="{{ $th }}">Aksi</th><th class="{{ $th }}">Modul</th><th class="{{ $th }}">Referensi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($rows as $row)
                        <tr>
                            <td class="{{ $td }}">{{ $row->created_at?->format('d/m/Y H:i:s') }}</td>
                            <td class="{{ $td }}">{{ $row->user?->name ?? '-' }}</td>
                            <td class="{{ $td }}"><span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">{{ $row->action }}</span></td>
                            <td class="{{ $td }}">{{ $row->module }}</td>
                            <td class="{{ $td }}">{{ $row->reference_id ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-sm text-slate-500">Tidak ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @break
    @endswitch
</div>

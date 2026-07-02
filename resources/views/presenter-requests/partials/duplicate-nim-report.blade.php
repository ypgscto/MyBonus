@if (session('duplicate_nim_message') && session('duplicate_nim_report'))
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
        <strong>{{ session('duplicate_nim_message') }}</strong>
        <div class="mt-3 overflow-x-auto">
            <table class="min-w-full divide-y divide-red-200 border border-red-200 bg-white rounded-lg">
                <thead class="bg-red-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-red-700">NIM</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-red-700">Nama Mahasiswa</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-red-700">Kode Permintaan Sebelumnya</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-red-700">Presenter Sebelumnya</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-red-700">Status Permintaan Sebelumnya</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-red-700">Tanggal Pengajuan Sebelumnya</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-red-100">
                    @foreach (session('duplicate_nim_report') as $row)
                        <tr>
                            <td class="px-3 py-2 text-sm">{{ $row['nim'] }}</td>
                            <td class="px-3 py-2 text-sm">{{ $row['student_name'] }}</td>
                            <td class="px-3 py-2 text-sm">{{ $row['previous_request_code'] }}</td>
                            <td class="px-3 py-2 text-sm">{{ $row['previous_presenter_name'] }}</td>
                            <td class="px-3 py-2 text-sm">{{ $row['previous_status_label'] ?? $row['previous_status'] }}</td>
                            <td class="px-3 py-2 text-sm">{{ $row['previous_request_date'] }}</td>
                        </tr>
                        <tr>
                            <td colspan="6" class="px-3 py-2 text-xs text-slate-500">{{ $row['detail_message'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

<div id="nim-live-feedback" class="hidden mb-3 rounded-lg p-3 text-sm" role="alert"></div>

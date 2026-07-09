import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:open_file/open_file.dart';
import 'package:path_provider/path_provider.dart';
import 'package:provider/provider.dart';
import 'dart:io';
import 'dart:typed_data';
import 'package:bonusku_mobile/core/network/api_client.dart';
import 'package:bonusku_mobile/core/theme/app_theme.dart';
import 'package:bonusku_mobile/core/utils/formatters.dart';
import 'package:bonusku_mobile/core/widgets/app_widgets.dart';
import 'package:bonusku_mobile/core/widgets/commission_preview_card.dart';
import 'package:bonusku_mobile/core/widgets/copy_text_button.dart';
import 'package:bonusku_mobile/models/models.dart';
import 'package:bonusku_mobile/providers/app_providers.dart';

class RequestDetailScreen extends StatefulWidget {
  const RequestDetailScreen({super.key, required this.id});

  final int id;

  @override
  State<RequestDetailScreen> createState() => _RequestDetailScreenState();
}

class _RequestDetailScreenState extends State<RequestDetailScreen> {
  PresenterRequestModel? _request;
  String? _bankNote;
  List<Map<String, dynamic>> _financeUsers = [];
  bool _loading = true;
  String? _error;
  bool _actionLoading = false;
  bool _proofOpening = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final role = context.read<AuthProvider>().user!.role;
      PresenterRequestModel request;

      switch (role) {
        case 'presenter':
          request = await AppState.requestRepository.show('presenter/requests', widget.id);
        case 'verifikator':
          request = await AppState.verifikatorRepository.show('verifikator/requests', widget.id);
          _financeUsers = await AppState.verifikatorRepository.financeUsers();
        case 'keuangan':
          request = await AppState.keuanganRepository.show('keuangan/requests', widget.id);
        case 'admin_pmb':
        case 'super_admin':
          request = await AppState.adminRepository.show('admin/presenter-requests', widget.id);
        default:
          throw ApiException('Role tidak didukung');
      }

      final bankNote = await _resolveBankNote(role, request);

      setState(() {
        _request = request;
        _bankNote = bankNote;
        _loading = false;
      });
    } on ApiException catch (e) {
      setState(() {
        _error = e.message;
        _loading = false;
      });
    }
  }

  Future<String?> _resolveBankNote(String role, PresenterRequestModel request) async {
    if (role == 'presenter') return null;

    var note = request.bankTransferNote;
    if (note != null && note.isNotEmpty) return note;

    final prefix = switch (role) {
      'verifikator' => 'verifikator/requests',
      'keuangan' => 'keuangan/requests',
      _ => null,
    };

    if (prefix != null) {
      try {
        final repo = role == 'verifikator'
            ? AppState.verifikatorRepository
            : AppState.keuanganRepository;
        note = await repo.bankTransferNote(prefix, widget.id);
        if (note.isNotEmpty) return note;
      } catch (_) {
        // fall through to computed note
      }
    }

    if (request.details.isNotEmpty) {
      return request.bankTransferNoteOrComputed;
    }

    return null;
  }

  bool _showsTransferForm(String role) {
    final status = _request?.status;
    if (status == null) return false;

    return (role == 'verifikator' && status == 'approved_by_verifikator') ||
        (role == 'keuangan' && status == 'received_by_finance');
  }

  Future<void> _runAction(Future<void> Function() action, String successMessage) async {
    setState(() => _actionLoading = true);
    try {
      await action();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(successMessage)));
        await _load();
      }
    } on ApiException catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
      }
    } finally {
      if (mounted) setState(() => _actionLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final role = context.watch<AuthProvider>().user!.role;
    final paymentProofSection = _buildPaymentProofSection(role);

    return Scaffold(
      appBar: AppBar(
        title: Text(_request?.requestCode ?? 'Detail Permintaan'),
        actions: [
          if (_request != null) CopyTextButton(text: _request!.requestCode, compact: true),
          const SizedBox(width: 8),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(child: Text(_error!))
              : RefreshIndicator(
                  onRefresh: _load,
                  child: ListView(
                    padding: const EdgeInsets.all(16),
                    children: [
                      Row(
                        children: [
                          Expanded(
                            child: Text(
                              _request!.requestCode,
                              style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                                    fontWeight: FontWeight.w800,
                                  ),
                            ),
                          ),
                          StatusBadge(label: _request!.statusLabel),
                        ],
                      ),
                      const SizedBox(height: 16),
                      _buildRequestCard(role),
                      if (paymentProofSection != null) ...[
                        const SizedBox(height: 12),
                        paymentProofSection,
                      ],
                      const SizedBox(height: 12),
                      _buildCommissionCard(),
                      if (_request!.presenter != null && role != 'presenter') ...[
                        const SizedBox(height: 12),
                        _buildPresenterCard(role),
                      ],
                      if (_bankNote != null && _bankNote!.isNotEmpty && !_showsTransferForm(role)) ...[
                        const SizedBox(height: 12),
                        BankTransferNoteCard(note: _bankNote!),
                      ],
                      const SizedBox(height: 12),
                      _buildStudentsCard(role),
                      if (_request!.verifikatorTransfer != null && role != 'keuangan') ...[
                        const SizedBox(height: 12),
                        _buildTransferCard('Transfer ke Keuangan', _request!.verifikatorTransfer!),
                      ],
                      if (_request!.presenterTransfer != null) ...[
                        const SizedBox(height: 12),
                        _buildTransferCard(
                          'Transfer ke Presenter',
                          _request!.presenterTransfer!,
                          showProofButton: role == 'presenter',
                          onOpenProof: role == 'presenter' ? _openPresenterTransferProof : null,
                        ),
                      ],
                      const SizedBox(height: 12),
                      ..._buildActions(role),
                    ],
                  ),
                ),
    );
  }

  bool _usesCompactStudentLayout(String role) =>
      role == 'verifikator' || role == 'keuangan' || role == 'presenter';

  bool _canViewPaymentProof(String role, String status) {
    if (role == 'verifikator') return status != 'draft';

    return false;
  }

  bool _canViewVerifikatorTransferProof(String role, String status) {
    if (role != 'keuangan') return false;

    return const {
      'transferred_to_finance',
      'received_by_finance',
      'transferred_to_presenter',
      'closed',
    }.contains(status);
  }

  Future<void> _openProofFile(DownloadedFile file, {required String title}) async {
    final lowerName = file.filename.toLowerCase();
    if (lowerName.endsWith('.jpg') ||
        lowerName.endsWith('.jpeg') ||
        lowerName.endsWith('.png')) {
      await _showImageProof(file.bytes, title: title);
      return;
    }

    final tempDir = await getTemporaryDirectory();
    final safeName = file.filename.replaceAll(RegExp(r'[\\/:*?"<>|]'), '_');
    final savedFile = File('${tempDir.path}/$safeName');
    await savedFile.writeAsBytes(file.bytes, flush: true);

    final result = await OpenFile.open(savedFile.path);
    if (!mounted) return;

    if (result.type != ResultType.done) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result.message)),
      );
    }
  }

  Future<void> _openPaymentProof(StudentDetailModel detail) async {
    setState(() => _proofOpening = true);

    try {
      final file = await AppState.requestRepository.downloadPaymentProof(detail.id);
      if (!mounted) return;
      await _openProofFile(file, title: 'Bukti Pembayaran Admin PMB');
    } on ApiException catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
      }
    } finally {
      if (mounted) setState(() => _proofOpening = false);
    }
  }

  Future<void> _openVerifikatorTransferProof() async {
    setState(() => _proofOpening = true);

    try {
      final file = await AppState.keuanganRepository.downloadVerifikatorProof(widget.id);
      if (!mounted) return;
      await _openProofFile(file, title: 'Bukti Transfer Verifikator');
    } on ApiException catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
      }
    } finally {
      if (mounted) setState(() => _proofOpening = false);
    }
  }

  Future<void> _openPresenterTransferProof() async {
    setState(() => _proofOpening = true);

    try {
      final file = await AppState.requestRepository.downloadPresenterTransferProof(widget.id);
      if (!mounted) return;
      await _openProofFile(file, title: 'Bukti Transfer ke Presenter');
    } on ApiException catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
      }
    } finally {
      if (mounted) setState(() => _proofOpening = false);
    }
  }

  Future<void> _showImageProof(List<int> bytes, {required String title}) async {
    await showDialog<void>(
      context: context,
      builder: (context) => Dialog(
        insetPadding: const EdgeInsets.all(16),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 12, 8, 0),
              child: Row(
                children: [
                  Expanded(
                    child: Text(
                      title,
                      style: const TextStyle(fontWeight: FontWeight.w700),
                    ),
                  ),
                  IconButton(
                    onPressed: () => Navigator.of(context).pop(),
                    icon: const Icon(Icons.close),
                  ),
                ],
              ),
            ),
            Flexible(
              child: InteractiveViewer(
                child: Image.memory(Uint8List.fromList(bytes)),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget? _buildPaymentProofSection(String role) {
    if (role == 'keuangan') {
      return _buildVerifikatorTransferProofSection(role);
    }

    if (!_usesCompactStudentLayout(role)) return null;
    if (!_canViewPaymentProof(role, _request!.status)) return null;

    final proofDetail = _request!.firstPaymentProofDetail;
    if (proofDetail == null) return null;

    return AppCard(
      title: 'Bukti Pembayaran Admin PMB',
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(
            'Bukti pembayaran yang diunggah Admin PMB saat mengajukan permintaan ini.',
            style: Theme.of(context).textTheme.bodySmall?.copyWith(color: AppColors.textSecondary),
          ),
          const SizedBox(height: 12),
          OutlinedButton.icon(
            onPressed: _proofOpening ? null : () => _openPaymentProof(proofDetail),
            icon: _proofOpening
                ? const SizedBox(
                    width: 16,
                    height: 16,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Icon(Icons.receipt_long_outlined),
            label: const Text('Lihat Bukti Pembayaran'),
          ),
        ],
      ),
    );
  }

  Widget? _buildVerifikatorTransferProofSection(String role) {
    if (!_canViewVerifikatorTransferProof(role, _request!.status)) return null;
    if (_request!.verifikatorTransfer == null) return null;

    return AppCard(
      title: 'Bukti Transfer Verifikator',
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(
            'Bukti transfer yang diunggah Verifikator saat mengirim dana ke keuangan.',
            style: Theme.of(context).textTheme.bodySmall?.copyWith(color: AppColors.textSecondary),
          ),
          const SizedBox(height: 12),
          OutlinedButton.icon(
            onPressed: _proofOpening ? null : _openVerifikatorTransferProof,
            icon: _proofOpening
                ? const SizedBox(
                    width: 16,
                    height: 16,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Icon(Icons.receipt_long_outlined),
            label: const Text('Lihat Bukti Transfer Verifikator'),
          ),
        ],
      ),
    );
  }

  Widget _buildRequestCard(String role) {
    final period = _request!.pmbPeriod;
    final periodLabel = period != null
        ? '${period['academic_year'] ?? ''} – ${period['wave'] ?? ''}'
        : '-';
    final compact = _usesCompactStudentLayout(role);

    return AppCard(
      title: 'Data Permintaan',
      child: Column(
        children: [
          InfoTile(label: 'Kode Permintaan', value: _request!.requestCode, copyable: true, highlight: true),
          InfoTile(label: 'Periode PMB', value: periodLabel),
          if (compact)
            Row(
              children: [
                Expanded(
                  child: InfoTile(label: 'Tanggal Pengajuan', value: Formatters.date(_request!.requestDate)),
                ),
                Expanded(
                  child: InfoTile(label: 'Tanggal Bayar', value: Formatters.date(_request!.paymentDate)),
                ),
              ],
            )
          else
            InfoTile(label: 'Tanggal Pengajuan', value: Formatters.date(_request!.requestDate)),
          InfoTile(label: 'Dikirim', value: Formatters.dateTime(_request!.submittedAt)),
          if (_request!.rejectionReason != null)
            InfoTile(label: 'Alasan Penolakan', value: _request!.rejectionReason!),
          if (_request!.adminNote != null)
            InfoTile(label: 'Catatan Admin', value: _request!.adminNote!),
        ],
      ),
    );
  }

  Widget _buildCommissionCard() {
    if (_request!.commissionIsPreview || _request!.isDraft) {
      return CommissionPreviewCard(
        totalStudents: _request!.studentCount,
        commissionPerStudent: _request!.commissionPerStudent,
        totalCommission: _request!.totalCommission ?? 0,
        isPreview: true,
      );
    }

    return AppCard(
      title: 'Komisi',
      child: Row(
        children: [
          Expanded(child: InfoTile(label: 'Mahasiswa', value: '${_request!.totalStudents ?? 0}')),
          Expanded(child: InfoTile(label: 'Per Mhs', value: Formatters.currency(_request!.commissionPerStudent))),
          Expanded(child: InfoTile(label: 'Total', value: Formatters.currency(_request!.totalCommission), highlight: true)),
        ],
      ),
    );
  }

  Widget _buildPresenterCard(String role) {
    final p = _request!.presenter!;
    return AppCard(
      title: 'Data Presenter',
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          InfoTile(label: 'Nama Presenter', value: p.name, highlight: true),
          if (p.phone != null && p.phone!.isNotEmpty)
            InfoTile(label: 'Telepon', value: p.phone!, copyable: true),
          if (p.email != null && p.email!.isNotEmpty)
            InfoTile(label: 'Email', value: p.email!),
          const SizedBox(height: 8),
          BankAccountPanel(
            title: role == 'keuangan' ? 'REKENING TUJUAN PRESENTER' : 'REKENING PRESENTER',
            bankName: p.bankName ?? '-',
            accountNumber: p.accountNumber ?? '-',
            accountHolder: p.accountHolderName ?? '-',
          ),
        ],
      ),
    );
  }

  Widget _buildStudentsCard(String role) {
    final compact = _usesCompactStudentLayout(role);

    return AppCard(
      title: 'Calon Mahasiswa (${_request!.details.length})',
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          ..._request!.details.asMap().entries.map((entry) {
          final i = entry.key + 1;
          final d = entry.value;

          if (compact) {
            return Container(
              margin: EdgeInsets.only(bottom: entry.key == _request!.details.length - 1 ? 0 : 8),
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
              decoration: BoxDecoration(
                color: AppColors.surface,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: AppColors.border),
              ),
              child: Row(
                children: [
                  CircleAvatar(
                    radius: 14,
                    backgroundColor: AppColors.primary.withValues(alpha: 0.12),
                    child: Text(
                      '$i',
                      style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: AppColors.primary),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(d.studentName, style: const TextStyle(fontWeight: FontWeight.w700)),
                        const SizedBox(height: 2),
                        Text(
                          d.nim,
                          style: Theme.of(context).textTheme.bodySmall?.copyWith(color: AppColors.textSecondary),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            );
          }

          return Container(
            margin: EdgeInsets.only(bottom: entry.key == _request!.details.length - 1 ? 0 : 10),
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: AppColors.surface,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: AppColors.border),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    CircleAvatar(
                      radius: 14,
                      backgroundColor: AppColors.primary.withValues(alpha: 0.12),
                      child: Text('$i', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: AppColors.primary)),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Text(d.studentName, style: const TextStyle(fontWeight: FontWeight.w700)),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                InfoTile(label: 'NIM', value: d.nim, copyable: true),
                Row(
                  children: [
                    Expanded(child: InfoTile(label: 'Tgl Lahir', value: Formatters.date(d.birthDate))),
                    Expanded(child: InfoTile(label: 'Tgl Bayar', value: Formatters.date(d.paymentDate))),
                  ],
                ),
              ],
            ),
          );
        }),
        ],
      ),
    );
  }

  Widget _buildTransferCard(
    String title,
    Map<String, dynamic> transfer, {
    bool showProofButton = false,
    VoidCallback? onOpenProof,
  }) {
    return AppCard(
      title: title,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          InfoTile(label: 'Nominal', value: Formatters.currency(transfer['transfer_amount'] as num?), highlight: true),
          InfoTile(label: 'Tanggal', value: Formatters.date(transfer['transfer_date']?.toString())),
          InfoTile(label: 'Bank', value: transfer['destination_bank']?.toString() ?? '-'),
          InfoTile(
            label: 'Nomor Rekening',
            value: transfer['destination_account_number']?.toString() ?? '-',
            copyable: true,
          ),
          InfoTile(label: 'Atas Nama', value: transfer['destination_account_name']?.toString() ?? '-'),
          if (showProofButton && onOpenProof != null) ...[
            const SizedBox(height: 12),
            OutlinedButton.icon(
              onPressed: _proofOpening ? null : onOpenProof,
              icon: _proofOpening
                  ? const SizedBox(
                      width: 16,
                      height: 16,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Icon(Icons.receipt_long_outlined),
              label: const Text('Lihat Bukti Transfer'),
            ),
          ],
        ],
      ),
    );
  }

  List<Widget> _buildActions(String role) {
    if (_actionLoading) {
      return [const Center(child: Padding(padding: EdgeInsets.all(16), child: CircularProgressIndicator()))];
    }

    final status = _request!.status;

    if (role == 'verifikator' && _request!.canVerifikatorVerify) {
      return [
        AppCard(
          title: 'Aksi Verifikasi',
          child: Column(
            children: [
              SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  onPressed: () => _showApproveDialog(),
                  icon: const Icon(Icons.check),
                  label: const Text('Setujui'),
                  style: ElevatedButton.styleFrom(backgroundColor: AppColors.success),
                ),
              ),
              const SizedBox(height: 8),
              SizedBox(
                width: double.infinity,
                child: OutlinedButton.icon(
                  onPressed: () => _showRejectDialog(),
                  icon: const Icon(Icons.close, color: AppColors.danger),
                  label: const Text('Tolak', style: TextStyle(color: AppColors.danger)),
                ),
              ),
            ],
          ),
        ),
      ];
    }

    if (role == 'verifikator' && _request!.canVerifikatorTransfer) {
      return [
        _VerifikatorTransferForm(
          requestId: widget.id,
          financeUsers: _financeUsers,
          defaultAmount: _request!.totalCommission,
          bankTransferNote: _bankNote ?? _request!.bankTransferNoteOrComputed,
          onDone: _load,
        ),
      ];
    }

    if (role == 'verifikator') {
      return [_VerifikatorStatusNotice(request: _request!)];
    }

    if (role == 'keuangan' && status == 'transferred_to_finance') {
      return [
        SizedBox(
          width: double.infinity,
          child: ElevatedButton.icon(
            onPressed: () => _runAction(
              () => AppState.keuanganRepository.confirmReceived(widget.id),
              'Dana berhasil dikonfirmasi',
            ),
            icon: const Icon(Icons.check),
            label: const Text('Konfirmasi Dana Diterima'),
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.success),
          ),
        ),
      ];
    }

    if (role == 'keuangan' && status == 'received_by_finance') {
      return [
        _KeuanganTransferForm(
          requestId: widget.id,
          defaultAmount: _request!.totalCommission,
          bankTransferNote: _bankNote ?? _request!.bankTransferNoteOrComputed,
          onDone: _load,
        ),
      ];
    }

    if (role == 'keuangan' && status == 'transferred_to_presenter') {
      return [
        SizedBox(
          width: double.infinity,
          child: ElevatedButton.icon(
            onPressed: () => _runAction(
              () => AppState.keuanganRepository.closeRequest(widget.id),
              'Permintaan ditutup',
            ),
            icon: const Icon(Icons.lock_outline),
            label: const Text('Tutup Permintaan'),
          ),
        ),
      ];
    }

    return const [];
  }

  Future<void> _showApproveDialog() async {
    final noteController = TextEditingController();
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Setujui Permintaan'),
        content: TextField(
          controller: noteController,
          decoration: const InputDecoration(labelText: 'Catatan Verifikator (opsional)'),
          maxLines: 2,
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Batal')),
          ElevatedButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Setujui')),
        ],
      ),
    );

    if (ok == true) {
      await _runAction(
        () => AppState.verifikatorRepository.approve(widget.id, note: noteController.text),
        'Permintaan disetujui',
      );
    }
  }

  Future<void> _showRejectDialog() async {
    final reasonController = TextEditingController();
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Tolak Permintaan'),
        content: TextField(
          controller: reasonController,
          decoration: const InputDecoration(labelText: 'Alasan Penolakan'),
          maxLines: 3,
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Batal')),
          ElevatedButton(
            onPressed: () => Navigator.pop(ctx, true),
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.danger),
            child: const Text('Tolak'),
          ),
        ],
      ),
    );

    if (ok == true && reasonController.text.trim().isNotEmpty) {
      await _runAction(
        () => AppState.verifikatorRepository.reject(widget.id, reasonController.text.trim()),
        'Permintaan ditolak',
      );
    }
  }
}

class _VerifikatorTransferForm extends StatefulWidget {
  const _VerifikatorTransferForm({
    required this.requestId,
    required this.financeUsers,
    required this.defaultAmount,
    required this.bankTransferNote,
    required this.onDone,
  });

  final int requestId;
  final List<Map<String, dynamic>> financeUsers;
  final double? defaultAmount;
  final String bankTransferNote;
  final Future<void> Function() onDone;

  @override
  State<_VerifikatorTransferForm> createState() => _VerifikatorTransferFormState();
}

class _VerifikatorTransferFormState extends State<_VerifikatorTransferForm> {
  int? _financeUserId;
  final _amountController = TextEditingController();
  final _noteController = TextEditingController();
  XFile? _proof;
  bool _loading = false;

  Map<String, dynamic>? get _selectedFinance {
    if (_financeUserId == null) return null;
    for (final u in widget.financeUsers) {
      if (u['id'] == _financeUserId) return u;
    }
    return null;
  }

  @override
  void initState() {
    super.initState();
    final amount = widget.defaultAmount ?? 0;
    _amountController.text = amount == amount.roundToDouble() ? '${amount.toInt()}' : '$amount';
    _amountController.addListener(() => setState(() {}));
  }

  @override
  void dispose() {
    _amountController.dispose();
    _noteController.dispose();
    super.dispose();
  }

  bool get _amountDiffers {
    final expected = widget.defaultAmount ?? 0;
    final entered = double.tryParse(_amountController.text.replaceAll(',', '.')) ?? expected;
    return (entered - expected).abs() > 0.009;
  }

  Future<void> _pickProof() async {
    final picker = ImagePicker();
    final file = await picker.pickImage(source: ImageSource.gallery);
    if (file != null) setState(() => _proof = file);
  }

  Future<void> _submit() async {
    if (_financeUserId == null || _proof == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Pilih user keuangan dan upload bukti transfer')),
      );
      return;
    }

    if (_amountDiffers && _noteController.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Catatan alasan selisih wajib diisi jika nominal transfer berbeda dari total komisi'),
        ),
      );
      return;
    }

    setState(() => _loading = true);

    try {
      final formData = FormData.fromMap({
        'transfer_date': DateTime.now().toIso8601String().split('T').first,
        'transfer_amount': _amountController.text,
        'finance_user_id': _financeUserId,
        'transfer_proof': await MultipartFile.fromFile(_proof!.path, filename: _proof!.name),
        if (_noteController.text.trim().isNotEmpty) 'note': _noteController.text.trim(),
      });

      await AppState.apiClient.post('/verifikator/requests/${widget.requestId}/transfer', formData: formData);

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Transfer ke Keuangan berhasil')),
        );
        await widget.onDone();
      }
    } on ApiException catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
      }
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final selected = _selectedFinance;

    return AppCard(
      title: 'Transfer ke Keuangan',
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          BankTransferNoteCard(note: widget.bankTransferNote, embedded: true),
          const SizedBox(height: 12),
          DropdownButtonFormField<int>(
            value: _financeUserId,
            decoration: const InputDecoration(labelText: 'User Keuangan Penerima'),
            items: widget.financeUsers
                .map(
                  (u) => DropdownMenuItem<int>(
                    value: u['id'] as int,
                    child: Text(u['name']?.toString() ?? ''),
                  ),
                )
                .toList(),
            onChanged: (v) => setState(() => _financeUserId = v),
          ),
          if (selected != null) ...[
            const SizedBox(height: 12),
            BankAccountPanel(
              title: 'REKENING TUJUAN (USER KEUANGAN)',
              bankName: selected['bank_name']?.toString() ?? '-',
              accountNumber: selected['account_number']?.toString() ?? '-',
              accountHolder: selected['account_holder_name']?.toString() ?? '-',
            ),
          ],
          const SizedBox(height: 12),
          TextField(
            controller: _amountController,
            keyboardType: TextInputType.number,
            decoration: InputDecoration(
              labelText: 'Nominal Transfer',
              helperText: widget.defaultAmount != null
                  ? 'Total komisi: ${Formatters.currency(widget.defaultAmount)}'
                  : null,
            ),
          ),
          if (_amountDiffers) ...[
            const SizedBox(height: 12),
            TextField(
              controller: _noteController,
              maxLines: 2,
              decoration: const InputDecoration(
                labelText: 'Catatan Alasan Selisih',
                helperText: 'Wajib diisi karena nominal berbeda dari total komisi',
              ),
            ),
          ],
          const SizedBox(height: 12),
          OutlinedButton.icon(
            onPressed: _pickProof,
            icon: const Icon(Icons.upload_file),
            label: Text(_proof == null ? 'Pilih Bukti Transfer' : _proof!.name),
          ),
          const SizedBox(height: 12),
          ElevatedButton(
            onPressed: _loading ? null : _submit,
            child: Text(_loading ? 'Mengirim...' : 'Transfer ke Keuangan'),
          ),
        ],
      ),
    );
  }
}

class _KeuanganTransferForm extends StatefulWidget {
  const _KeuanganTransferForm({
    required this.requestId,
    required this.defaultAmount,
    required this.bankTransferNote,
    required this.onDone,
  });

  final int requestId;
  final double? defaultAmount;
  final String bankTransferNote;
  final Future<void> Function() onDone;

  @override
  State<_KeuanganTransferForm> createState() => _KeuanganTransferFormState();
}

class _KeuanganTransferFormState extends State<_KeuanganTransferForm> {
  final _amountController = TextEditingController();
  XFile? _proof;
  bool _loading = false;

  @override
  void initState() {
    super.initState();
    _amountController.text = '${widget.defaultAmount ?? 0}';
  }

  @override
  void dispose() {
    _amountController.dispose();
    super.dispose();
  }

  Future<void> _pickProof() async {
    final picker = ImagePicker();
    final file = await picker.pickImage(source: ImageSource.gallery);
    if (file != null) setState(() => _proof = file);
  }

  Future<void> _submit() async {
    if (_proof == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Upload bukti transfer wajib')),
      );
      return;
    }

    setState(() => _loading = true);

    try {
      final formData = FormData.fromMap({
        'transfer_date': DateTime.now().toIso8601String().split('T').first,
        'transfer_amount': _amountController.text,
        'transfer_proof': await MultipartFile.fromFile(_proof!.path, filename: _proof!.name),
      });

      await AppState.apiClient.post('/keuangan/requests/${widget.requestId}/transfer', formData: formData);

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Transfer ke Presenter berhasil')),
        );
        await widget.onDone();
      }
    } on ApiException catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
      }
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return AppCard(
      title: 'Transfer ke Presenter',
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          BankTransferNoteCard(note: widget.bankTransferNote, embedded: true),
          const SizedBox(height: 12),
          TextField(
            controller: _amountController,
            keyboardType: TextInputType.number,
            decoration: const InputDecoration(labelText: 'Nominal Transfer'),
          ),
          const SizedBox(height: 12),
          OutlinedButton.icon(
            onPressed: _pickProof,
            icon: const Icon(Icons.upload_file),
            label: Text(_proof == null ? 'Pilih Bukti Transfer' : _proof!.name),
          ),
          const SizedBox(height: 12),
          ElevatedButton(
            onPressed: _loading ? null : _submit,
            child: Text(_loading ? 'Mengirim...' : 'Transfer ke Presenter'),
          ),
        ],
      ),
    );
  }
}

class _VerifikatorStatusNotice extends StatelessWidget {
  const _VerifikatorStatusNotice({required this.request});

  final PresenterRequestModel request;

  @override
  Widget build(BuildContext context) {
    final message = switch (request.status) {
      'rejected_by_verifikator' => 'Permintaan ini telah ditolak. Tidak ada aksi yang dapat dilakukan.',
      'transferred_to_finance' ||
      'received_by_finance' ||
      'transferred_to_presenter' ||
      'closed' =>
        'Permintaan ini sudah diverifikasi dan bukti transfer telah dikirim ke Keuangan. Tidak dapat melakukan transfer lagi.',
      _ => 'Permintaan ini tidak memerlukan aksi verifikator saat ini.',
    };

    return AppCard(
      title: 'Status Verifikator',
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(message),
          if (request.status == 'rejected_by_verifikator' && request.rejectionReason != null) ...[
            const SizedBox(height: 12),
            Text(
              'Alasan Penolakan',
              style: Theme.of(context).textTheme.labelSmall?.copyWith(
                    color: AppColors.textSecondary,
                    fontWeight: FontWeight.w600,
                  ),
            ),
            const SizedBox(height: 4),
            Text(request.rejectionReason!),
          ],
        ],
      ),
    );
  }
}

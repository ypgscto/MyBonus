import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:bonusku_mobile/core/network/api_client.dart';
import 'package:bonusku_mobile/core/theme/app_theme.dart';
import 'package:bonusku_mobile/core/widgets/app_widgets.dart';
import 'package:bonusku_mobile/core/widgets/commission_preview_card.dart';
import 'package:bonusku_mobile/core/widgets/copy_text_button.dart';
import 'package:bonusku_mobile/models/models.dart';
import 'package:bonusku_mobile/providers/app_providers.dart';

class DraftEditScreen extends StatefulWidget {
  const DraftEditScreen({super.key, required this.id});

  final int id;

  @override
  State<DraftEditScreen> createState() => _DraftEditScreenState();
}

class _DraftEditScreenState extends State<DraftEditScreen> {
  PresenterRequestModel? _request;
  List<Map<String, dynamic>> _pmbPeriods = [];
  List<Map<String, dynamic>> _presenters = [];
  int? _pmbPeriodId;
  int? _presenterId;
  final _adminNoteController = TextEditingController();
  Map<String, dynamic>? _commissionPreview;
  bool _previewLoading = false;
  bool _loading = true;
  bool _saving = false;
  bool _submitting = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _adminNoteController.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final results = await Future.wait([
        AppState.adminRepository.show('admin/presenter-requests', widget.id),
        AppState.lookupRepository.pmbPeriods(),
        AppState.lookupRepository.presenters(),
      ]);

      final request = results[0] as PresenterRequestModel;
      final periods = results[1] as List<Map<String, dynamic>>;
      final presenters = results[2] as List<Map<String, dynamic>>;

      setState(() {
        _request = request;
        _pmbPeriods = periods;
        _presenters = presenters;
        _pmbPeriodId = request.pmbPeriod?['id'] as int? ?? periods.firstOrNull?['id'] as int?;
        _presenterId = request.presenter?.id ?? presenters.firstOrNull?['id'] as int?;
        _adminNoteController.text = request.adminNote ?? '';
        _commissionPreview = _previewFromRequest(request);
        _loading = false;
      });

      await _refreshCommissionPreview();
    } on ApiException catch (e) {
      setState(() {
        _error = e.message;
        _loading = false;
      });
    }
  }

  Map<String, dynamic> _previewFromRequest(PresenterRequestModel request) {
    return {
      'available': request.commissionPerStudent != null,
      'total_students': request.studentCount,
      'commission_per_student': request.commissionPerStudent,
      'total_commission': request.totalCommission ?? 0,
      'is_preview': true,
      'presenter_category': request.presenter?.name,
      'pmb_period_label': request.pmbPeriod?['label']?.toString(),
    };
  }

  Future<void> _refreshCommissionPreview() async {
    if (_pmbPeriodId == null || _presenterId == null) return;

    setState(() => _previewLoading = true);
    try {
      final preview = await AppState.adminRepository.commissionPreview(
        widget.id,
        presenterId: _presenterId,
        pmbPeriodId: _pmbPeriodId,
      );
      if (!mounted) return;
      setState(() {
        _commissionPreview = preview;
        _previewLoading = false;
      });
    } on ApiException {
      if (mounted) setState(() => _previewLoading = false);
    }
  }

  Future<void> _saveHeader() async {
    if (_pmbPeriodId == null || _presenterId == null) return;

    setState(() => _saving = true);
    try {
      final updated = await AppState.adminRepository.updateDraftHeader(
        widget.id,
        pmbPeriodId: _pmbPeriodId!,
        presenterId: _presenterId!,
        adminNote: _adminNoteController.text.trim(),
      );
      setState(() => _request = updated);
      await _refreshCommissionPreview();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Data permintaan disimpan')));
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  Future<void> _submit() async {
    final request = _request;
    if (request == null || request.details.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Tambahkan minimal 1 mahasiswa sebelum submit')),
      );
      return;
    }

    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Kirim ke Verifikator?'),
        content: Text('Permintaan ${request.requestCode} akan dikirim ke Verifikator.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Batal')),
          FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Kirim')),
        ],
      ),
    );

    if (confirm != true) return;

    final incomplete = request.details.where((d) => !d.isReadyForSubmit).toList();
    if (incomplete.isNotEmpty) {
      _showIncompleteStudentsDialog(incomplete);
      return;
    }

    setState(() => _submitting = true);
    try {
      await AppState.adminRepository.submitDraft(widget.id);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Permintaan berhasil dikirim')));
      context.pop(true);
    } on ApiException catch (e) {
      if (!mounted) return;
      if (e.errors?['duplicate_nim_report'] != null) {
        _showDuplicateNimDialog(e.message, e.errors!['duplicate_nim_report']);
      } else if (e.errors != null && e.errors!.isNotEmpty) {
        _showValidationErrorsDialog(e.message, e.errors!);
      } else {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
      }
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  void _showValidationErrorsDialog(String message, Map<String, dynamic> errors) {
    final items = <String>[];
    errors.forEach((key, value) {
      if (key == 'duplicate_nim_report') return;
      if (value is List) {
        items.addAll(value.map((e) => e.toString()));
      } else if (value != null) {
        items.add(value.toString());
      }
    });

    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Data Belum Lengkap'),
        content: SingleChildScrollView(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(message),
              if (items.isNotEmpty) ...[
                const SizedBox(height: 12),
                ...items.map(
                  (item) => Padding(
                    padding: const EdgeInsets.only(bottom: 6),
                    child: Text('• $item', style: Theme.of(context).textTheme.bodySmall),
                  ),
                ),
              ],
            ],
          ),
        ),
        actions: [
          FilledButton(onPressed: () => Navigator.pop(ctx), child: const Text('OK')),
        ],
      ),
    );
  }

  void _showIncompleteStudentsDialog(List<StudentDetailModel> students) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Lengkapi Data Mahasiswa'),
        content: SingleChildScrollView(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: students.map((student) {
              final missing = student.missingSubmitFields.join(', ');
              return Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: Text(
                  '• ${student.studentName} (${student.nim}): kurang $missing',
                  style: Theme.of(context).textTheme.bodySmall,
                ),
              );
            }).toList(),
          ),
        ),
        actions: [
          FilledButton(onPressed: () => Navigator.pop(ctx), child: const Text('OK')),
        ],
      ),
    );
  }

  void _showDuplicateNimDialog(String message, dynamic report) {
    final items = report is List ? report : const [];
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('NIM Duplikat'),
        content: SingleChildScrollView(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(message),
              const SizedBox(height: 12),
              ...items.whereType<Map>().map((item) {
                return Padding(
                  padding: const EdgeInsets.only(bottom: 8),
                  child: Text(
                    '• ${item['nim']}: ${item['detail_message'] ?? item['message'] ?? '-'}',
                    style: Theme.of(context).textTheme.bodySmall,
                  ),
                );
              }),
            ],
          ),
        ),
        actions: [
          FilledButton(onPressed: () => Navigator.pop(ctx), child: const Text('OK')),
        ],
      ),
    );
  }

  Future<void> _deleteStudent(StudentDetailModel detail) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Hapus Mahasiswa?'),
        content: Text('Hapus ${detail.studentName} (${detail.nim}) dari draft?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Batal')),
          FilledButton(
            onPressed: () => Navigator.pop(ctx, true),
            style: FilledButton.styleFrom(backgroundColor: AppColors.danger),
            child: const Text('Hapus'),
          ),
        ],
      ),
    );

    if (confirm != true) return;

    try {
      await AppState.adminRepository.deleteStudent(widget.id, detail.id);
      await _load();
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return Scaffold(
        appBar: AppBar(title: const Text('Edit Draft')),
        body: const Center(child: CircularProgressIndicator()),
      );
    }

    if (_error != null || _request == null) {
      return Scaffold(
        appBar: AppBar(title: const Text('Edit Draft')),
        body: Center(child: Text(_error ?? 'Data tidak ditemukan')),
      );
    }

    final request = _request!;

    return Scaffold(
      appBar: AppBar(
        title: Text(request.requestCode),
        actions: [
          CopyTextButton(text: request.requestCode, compact: true),
          const SizedBox(width: 8),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _load,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            if (_commissionPreview != null)
              Padding(
                padding: const EdgeInsets.only(bottom: 16),
                child: CommissionPreviewCard.fromPreviewMap(
                  _commissionPreview!,
                  loading: _previewLoading,
                ),
              ),
            AppCard(
              title: 'Header Permintaan',
              child: Column(
                children: [
                  DropdownButtonFormField<int>(
                    value: _pmbPeriodId,
                    decoration: const InputDecoration(labelText: 'Periode PMB'),
                    items: _pmbPeriods
                        .map((p) => DropdownMenuItem(
                              value: p['id'] as int,
                              child: Text(p['label']?.toString() ?? '-'),
                            ))
                        .toList(),
                    onChanged: (v) {
                      setState(() => _pmbPeriodId = v);
                      _refreshCommissionPreview();
                    },
                  ),
                  const SizedBox(height: 12),
                  DropdownButtonFormField<int>(
                    value: _presenterId,
                    decoration: const InputDecoration(labelText: 'Presenter'),
                    items: _presenters
                        .map((p) => DropdownMenuItem(
                              value: p['id'] as int,
                              child: Text(p['name']?.toString() ?? '-'),
                            ))
                        .toList(),
                    onChanged: (v) {
                      setState(() => _presenterId = v);
                      _refreshCommissionPreview();
                    },
                  ),
                  const SizedBox(height: 12),
                  TextField(
                    controller: _adminNoteController,
                    decoration: const InputDecoration(labelText: 'Catatan Admin'),
                    maxLines: 2,
                  ),
                  const SizedBox(height: 12),
                  OutlinedButton(
                    onPressed: _saving ? null : _saveHeader,
                    child: _saving ? const Text('Menyimpan...') : const Text('Simpan Header'),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: Text(
                    'Mahasiswa (${request.details.length})',
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
                  ),
                ),
                TextButton.icon(
                  onPressed: () async {
                    final added = await context.push<bool>('/admin/drafts/${widget.id}/students/add');
                    if (added == true) _load();
                  },
                  icon: const Icon(Icons.person_add_outlined, size: 18),
                  label: const Text('Tambah'),
                ),
              ],
            ),
            const SizedBox(height: 8),
            if (request.details.isEmpty)
              AppCard(
                child: Text(
                  'Belum ada mahasiswa. Tambahkan minimal 1 mahasiswa sebelum submit.',
                  style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: AppColors.textSecondary),
                ),
              )
            else
              ...request.details.map((detail) => _StudentTile(
                    detail: detail,
                    onEdit: () async {
                      final updated = await context.push<bool>(
                        '/admin/drafts/${widget.id}/students/${detail.id}/edit',
                        extra: detail,
                      );
                      if (updated == true) _load();
                    },
                    onDelete: () => _deleteStudent(detail),
                  )),
            const SizedBox(height: 20),
            FilledButton(
              onPressed: _submitting ? null : _submit,
              child: _submitting
                  ? const SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                    )
                  : const Text('Kirim ke Verifikator'),
            ),
            const SizedBox(height: 32),
          ],
        ),
      ),
    );
  }
}

class _StudentTile extends StatelessWidget {
  const _StudentTile({
    required this.detail,
    required this.onEdit,
    required this.onDelete,
  });

  final StudentDetailModel detail;
  final VoidCallback onEdit;
  final VoidCallback onDelete;

  @override
  Widget build(BuildContext context) {
    final missing = detail.missingSubmitFields;

    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: AppCard(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            InkWell(
              onTap: onEdit,
              borderRadius: BorderRadius.circular(16),
              child: Padding(
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            detail.studentName,
                            style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w700),
                          ),
                        ),
                        if (detail.isReadyForSubmit)
                          const Icon(Icons.check_circle, size: 18, color: AppColors.success),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Row(
                      children: [
                        Text('NIM: ${detail.nim}', style: Theme.of(context).textTheme.bodySmall),
                        const SizedBox(width: 6),
                        CopyTextButton(text: detail.nim, compact: true),
                      ],
                    ),
                    if (detail.birthDate != null)
                      Text('Lahir: ${detail.birthDate}', style: Theme.of(context).textTheme.bodySmall),
                    if (detail.paymentDate != null)
                      Text('Bayar: ${detail.paymentDate}', style: Theme.of(context).textTheme.bodySmall),
                    if (missing.isNotEmpty)
                      Padding(
                        padding: const EdgeInsets.only(top: 6),
                        child: Text(
                          'Kurang: ${missing.join(', ')}',
                          style: Theme.of(context).textTheme.bodySmall?.copyWith(color: AppColors.warning),
                        ),
                      ),
                  ],
                ),
              ),
            ),
            const Divider(height: 1),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
              child: Row(
                children: [
                  Expanded(
                    child: TextButton.icon(
                      onPressed: onEdit,
                      icon: const Icon(Icons.edit_outlined, size: 18),
                      label: const Text('Edit'),
                    ),
                  ),
                  IconButton(
                    onPressed: onDelete,
                    tooltip: 'Hapus',
                    icon: const Icon(Icons.delete_outline, color: AppColors.danger),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

extension _FirstOrNull<E> on List<E> {
  E? get firstOrNull => isEmpty ? null : first;
}

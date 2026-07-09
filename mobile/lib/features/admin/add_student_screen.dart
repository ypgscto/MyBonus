import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';
import 'package:intl/intl.dart';
import 'package:bonusku_mobile/core/network/api_client.dart';
import 'package:bonusku_mobile/core/theme/app_theme.dart';
import 'package:bonusku_mobile/core/widgets/app_widgets.dart';
import 'package:bonusku_mobile/models/models.dart';
import 'package:bonusku_mobile/providers/app_providers.dart';

class AddStudentScreen extends StatefulWidget {
  const AddStudentScreen({
    super.key,
    required this.requestId,
    this.detail,
  });

  final int requestId;
  final StudentDetailModel? detail;

  bool get isEdit => detail != null;

  @override
  State<AddStudentScreen> createState() => _AddStudentScreenState();
}

class _AddStudentScreenState extends State<AddStudentScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nimController = TextEditingController();
  final _nameController = TextEditingController();
  final _noteController = TextEditingController();

  DateTime? _birthDate;
  DateTime? _paymentDate;
  String? _paymentProofPath;
  String? _nimStatus;
  bool _nimValid = false;
  bool _checkingNim = false;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    final detail = widget.detail;
    if (detail != null) {
      _nimController.text = detail.nim;
      _nameController.text = detail.studentName;
      _birthDate = detail.birthDate != null ? DateTime.tryParse(detail.birthDate!) : null;
      _paymentDate = detail.paymentDate != null ? DateTime.tryParse(detail.paymentDate!) : null;
      _nimValid = true;
      _nimStatus = 'NIM valid';
    }
  }

  @override
  void dispose() {
    _nimController.dispose();
    _nameController.dispose();
    _noteController.dispose();
    super.dispose();
  }

  Future<void> _checkNim() async {
    final nim = _nimController.text.trim();
    if (nim.isEmpty) return;

    setState(() {
      _checkingNim = true;
      _nimStatus = null;
      _nimValid = false;
    });

    try {
      final result = await AppState.adminRepository.checkNim(
        widget.requestId,
        nim: nim,
        studentName: _nameController.text.trim(),
        excludeDetailId: widget.detail?.id,
      );

      final valid = result['valid'] == true;
      String message = valid ? 'NIM valid' : 'NIM tidak valid';

      if (result['within_current'] == true) {
        message = 'NIM sudah ada dalam permintaan ini';
      } else if (result['blocking'] is List && (result['blocking'] as List).isNotEmpty) {
        final blocking = (result['blocking'] as List).first as Map<String, dynamic>;
        message = blocking['detail_message']?.toString() ?? message;
      } else if (result['warnings'] is List && (result['warnings'] as List).isNotEmpty) {
        final warning = (result['warnings'] as List).first as Map<String, dynamic>;
        message = 'Peringatan: ${warning['detail_message'] ?? warning['message'] ?? '-'}';
      }

      setState(() {
        _nimValid = valid;
        _nimStatus = message;
      });
    } on ApiException catch (e) {
      setState(() => _nimStatus = e.message);
    } finally {
      if (mounted) setState(() => _checkingNim = false);
    }
  }

  Future<void> _pickDate(bool isBirth) async {
    final picked = await showDatePicker(
      context: context,
      initialDate: DateTime.now(),
      firstDate: DateTime(1990),
      lastDate: DateTime.now().add(const Duration(days: 365)),
    );
    if (picked != null) {
      setState(() {
        if (isBirth) {
          _birthDate = picked;
        } else {
          _paymentDate = picked;
        }
      });
    }
  }

  Future<void> _pickProof() async {
    final picker = ImagePicker();
    final file = await picker.pickImage(source: ImageSource.gallery, imageQuality: 85);
    if (file != null) {
      setState(() => _paymentProofPath = file.path);
    }
  }

  String? _formatDate(DateTime? date) {
    if (date == null) return null;
    return DateFormat('yyyy-MM-dd').format(date);
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    if (!_nimValid) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Periksa NIM terlebih dahulu')),
      );
      return;
    }
    if (_birthDate == null || _paymentDate == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Tanggal lahir dan tanggal bayar wajib diisi')),
      );
      return;
    }
    if (!widget.isEdit && _paymentProofPath == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Bukti pembayaran wajib diupload')),
      );
      return;
    }
    if (widget.isEdit && _paymentProofPath == null && widget.detail?.hasPaymentProof != true) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Bukti pembayaran wajib diupload')),
      );
      return;
    }

    setState(() => _saving = true);
    try {
      if (widget.isEdit) {
        await AppState.adminRepository.updateStudent(
          widget.requestId,
          widget.detail!.id,
          nim: _nimController.text.trim(),
          studentName: _nameController.text.trim(),
          birthDate: _formatDate(_birthDate),
          paymentDate: _formatDate(_paymentDate),
          note: _noteController.text.trim(),
          paymentProofPath: _paymentProofPath,
        );
      } else {
        await AppState.adminRepository.addStudent(
          widget.requestId,
          nim: _nimController.text.trim(),
          studentName: _nameController.text.trim(),
          birthDate: _formatDate(_birthDate),
          paymentDate: _formatDate(_paymentDate),
          note: _noteController.text.trim(),
          paymentProofPath: _paymentProofPath,
        );
      }

      if (!mounted) return;
      context.pop(true);
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(widget.isEdit ? 'Edit Mahasiswa' : 'Tambah Mahasiswa')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              AppCard(
                title: 'Data Mahasiswa',
                child: Column(
                  children: [
                    TextFormField(
                      controller: _nimController,
                      decoration: InputDecoration(
                        labelText: 'NIM',
                        suffixIcon: _checkingNim
                            ? const Padding(
                                padding: EdgeInsets.all(12),
                                child: SizedBox(
                                  width: 16,
                                  height: 16,
                                  child: CircularProgressIndicator(strokeWidth: 2),
                                ),
                              )
                            : IconButton(
                                icon: const Icon(Icons.search),
                                onPressed: _checkNim,
                              ),
                      ),
                      onFieldSubmitted: (_) => _checkNim(),
                      validator: (v) => v == null || v.trim().isEmpty ? 'NIM wajib diisi' : null,
                    ),
                    if (_nimStatus != null) ...[
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          Icon(
                            _nimValid ? Icons.check_circle : Icons.warning_amber,
                            size: 16,
                            color: _nimValid ? AppColors.success : AppColors.warning,
                          ),
                          const SizedBox(width: 6),
                          Expanded(
                            child: Text(
                              _nimStatus!,
                              style: Theme.of(context).textTheme.bodySmall?.copyWith(
                                    color: _nimValid ? AppColors.success : AppColors.warning,
                                  ),
                            ),
                          ),
                        ],
                      ),
                    ],
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _nameController,
                      decoration: const InputDecoration(labelText: 'Nama Mahasiswa'),
                      validator: (v) => v == null || v.trim().isEmpty ? 'Nama wajib diisi' : null,
                      onChanged: (_) {
                        if (_nimController.text.isNotEmpty) _checkNim();
                      },
                    ),
                    const SizedBox(height: 12),
                    ListTile(
                      contentPadding: EdgeInsets.zero,
                      title: const Text('Tanggal Lahir'),
                      subtitle: Text(_birthDate != null ? _formatDate(_birthDate)! : 'Belum dipilih'),
                      trailing: const Icon(Icons.calendar_today_outlined),
                      onTap: () => _pickDate(true),
                    ),
                    ListTile(
                      contentPadding: EdgeInsets.zero,
                      title: const Text('Tanggal Pembayaran'),
                      subtitle: Text(_paymentDate != null ? _formatDate(_paymentDate)! : 'Belum dipilih'),
                      trailing: const Icon(Icons.calendar_today_outlined),
                      onTap: () => _pickDate(false),
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _noteController,
                      decoration: const InputDecoration(labelText: 'Catatan (opsional)'),
                      maxLines: 2,
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 16),
              AppCard(
                title: 'Bukti Pembayaran',
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    if (_paymentProofPath != null)
                      Text(
                        _paymentProofPath!.split(RegExp(r'[/\\]')).last,
                        style: Theme.of(context).textTheme.bodySmall,
                      )
                    else if (widget.detail?.hasPaymentProof == true)
                      Text(
                        'Bukti pembayaran sudah ada',
                        style: Theme.of(context).textTheme.bodySmall?.copyWith(color: AppColors.success),
                      ),
                    const SizedBox(height: 8),
                    OutlinedButton.icon(
                      onPressed: _pickProof,
                      icon: const Icon(Icons.upload_file_outlined),
                      label: Text(_paymentProofPath == null ? 'Pilih File' : 'Ganti File'),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 20),
              FilledButton(
                onPressed: _saving ? null : _save,
                child: _saving
                    ? const SizedBox(
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                      )
                    : Text(widget.isEdit ? 'Simpan Perubahan' : 'Simpan Mahasiswa'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:bonusku_mobile/core/network/api_client.dart';
import 'package:bonusku_mobile/core/widgets/app_widgets.dart';
import 'package:bonusku_mobile/providers/app_providers.dart';

class DraftCreateScreen extends StatefulWidget {
  const DraftCreateScreen({super.key});

  @override
  State<DraftCreateScreen> createState() => _DraftCreateScreenState();
}

class _DraftCreateScreenState extends State<DraftCreateScreen> {
  final _formKey = GlobalKey<FormState>();
  final _adminNoteController = TextEditingController();

  List<Map<String, dynamic>> _pmbPeriods = [];
  List<Map<String, dynamic>> _presenters = [];
  int? _pmbPeriodId;
  int? _presenterId;
  bool _loading = true;
  bool _saving = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadLookups();
  }

  @override
  void dispose() {
    _adminNoteController.dispose();
    super.dispose();
  }

  Future<void> _loadLookups() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final periods = await AppState.lookupRepository.pmbPeriods();
      final presenters = await AppState.lookupRepository.presenters();
      setState(() {
        _pmbPeriods = periods;
        _presenters = presenters;
        _pmbPeriodId = periods.isNotEmpty ? periods.first['id'] as int? : null;
        _presenterId = presenters.isNotEmpty ? presenters.first['id'] as int? : null;
        _loading = false;
      });
    } on ApiException catch (e) {
      setState(() {
        _error = e.message;
        _loading = false;
      });
    }
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    if (_pmbPeriodId == null || _presenterId == null) return;

    setState(() => _saving = true);

    try {
      final draft = await AppState.adminRepository.createDraft(
        pmbPeriodId: _pmbPeriodId!,
        presenterId: _presenterId!,
        adminNote: _adminNoteController.text.trim(),
      );

      if (!mounted) return;
      context.pop(true);
      context.push('/admin/drafts/${draft.id}/edit');
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
      appBar: AppBar(title: const Text('Buat Draft Permintaan')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(child: Text(_error!))
              : SingleChildScrollView(
                  padding: const EdgeInsets.all(16),
                  child: Form(
                    key: _formKey,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        AppCard(
                          title: 'Informasi Permintaan',
                          child: Column(
                            children: [
                              DropdownButtonFormField<int>(
                                value: _pmbPeriodId,
                                decoration: const InputDecoration(labelText: 'Periode PMB'),
                                items: _pmbPeriods
                                    .map(
                                      (p) => DropdownMenuItem(
                                        value: p['id'] as int,
                                        child: Text(p['label']?.toString() ?? p['name']?.toString() ?? '-'),
                                      ),
                                    )
                                    .toList(),
                                onChanged: (v) => setState(() => _pmbPeriodId = v),
                                validator: (v) => v == null ? 'Periode PMB wajib dipilih' : null,
                              ),
                              const SizedBox(height: 12),
                              DropdownButtonFormField<int>(
                                value: _presenterId,
                                decoration: const InputDecoration(labelText: 'Presenter'),
                                items: _presenters
                                    .map(
                                      (p) => DropdownMenuItem(
                                        value: p['id'] as int,
                                        child: Text(p['name']?.toString() ?? '-'),
                                      ),
                                    )
                                    .toList(),
                                onChanged: (v) => setState(() => _presenterId = v),
                                validator: (v) => v == null ? 'Presenter wajib dipilih' : null,
                              ),
                              const SizedBox(height: 12),
                              TextFormField(
                                controller: _adminNoteController,
                                decoration: const InputDecoration(
                                  labelText: 'Catatan Admin (opsional)',
                                  alignLabelWithHint: true,
                                ),
                                maxLines: 3,
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
                              : const Text('Simpan Draft'),
                        ),
                      ],
                    ),
                  ),
                ),
    );
  }
}

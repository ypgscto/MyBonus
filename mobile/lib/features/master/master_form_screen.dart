import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import 'package:bonusku_mobile/core/network/api_client.dart';
import 'package:bonusku_mobile/core/widgets/app_widgets.dart';
import 'package:bonusku_mobile/features/master/master_config.dart';
import 'package:bonusku_mobile/providers/app_providers.dart';

class MasterFormScreen extends StatefulWidget {
  const MasterFormScreen({
    super.key,
    required this.config,
    this.itemId,
    this.initialData,
  });

  final MasterConfig config;
  final int? itemId;
  final Map<String, dynamic>? initialData;

  bool get isEdit => itemId != null;

  @override
  State<MasterFormScreen> createState() => _MasterFormScreenState();
}

class _MasterFormScreenState extends State<MasterFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _controllers = <String, TextEditingController>{};
  final _lookupOptions = <String, List<Map<String, dynamic>>>{};
  final _selectValues = <String, String?>{};
  final _dateValues = <String, DateTime?>{};

  bool _loading = true;
  bool _saving = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    for (final field in widget.config.fields) {
      if (field.type == MasterFieldType.text ||
          field.type == MasterFieldType.number ||
          field.type == MasterFieldType.date) {
        _controllers[field.key] = TextEditingController();
      }
    }
    _load();
  }

  @override
  void dispose() {
    for (final c in _controllers.values) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      for (final field in widget.config.fields) {
        if (field.type == MasterFieldType.lookup && field.lookupResource != null) {
          final result = await AppState.masterRepository.list(field.lookupResource!);
          _lookupOptions[field.key] = result.items;
        }
      }

      Map<String, dynamic>? data = widget.initialData;
      if (widget.isEdit && data == null) {
        data = await AppState.masterRepository.show(widget.config.resource, widget.itemId!);
      }

      if (data != null) _populate(data);

      setState(() => _loading = false);
    } on ApiException catch (e) {
      setState(() {
        _error = e.message;
        _loading = false;
      });
    }
  }

  void _populate(Map<String, dynamic> data) {
    for (final field in widget.config.fields) {
      dynamic value = data[field.key];

      if (field.type == MasterFieldType.lookup) {
        if (value == null) {
          final nestedKey = field.key.replaceAll('_id', '');
          final nested = data[nestedKey];
          if (nested is Map<String, dynamic>) {
            value = nested['id'];
          }
        }
        _selectValues[field.key] = value?.toString();
      } else if (field.type == MasterFieldType.select) {
        _selectValues[field.key] = value?.toString();
      } else if (field.type == MasterFieldType.date) {
        if (value != null) {
          _dateValues[field.key] = DateTime.tryParse(value.toString());
          _controllers[field.key]?.text = value.toString();
        }
      } else {
        _controllers[field.key]?.text = value?.toString() ?? '';
      }
    }
  }

  Future<void> _pickDate(String key) async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _dateValues[key] ?? DateTime.now(),
      firstDate: DateTime(2020),
      lastDate: DateTime(2035),
    );
    if (picked != null) {
      setState(() {
        _dateValues[key] = picked;
        _controllers[key]?.text = DateFormat('yyyy-MM-dd').format(picked);
      });
    }
  }

  Map<String, dynamic> _buildPayload() {
    final payload = <String, dynamic>{};

    for (final field in widget.config.fields) {
      if (field.type == MasterFieldType.lookup || field.type == MasterFieldType.select) {
        final raw = _selectValues[field.key];
        if (raw == null || raw.isEmpty) continue;
        payload[field.key] = field.type == MasterFieldType.lookup ? int.tryParse(raw) ?? raw : raw;
      } else if (field.type == MasterFieldType.number) {
        final raw = _controllers[field.key]?.text.trim() ?? '';
        if (raw.isNotEmpty) payload[field.key] = num.tryParse(raw) ?? raw;
      } else {
        final raw = _controllers[field.key]?.text.trim() ?? '';
        if (raw.isNotEmpty || field.required) payload[field.key] = raw;
      }
    }

    return payload;
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _saving = true);
    try {
      final payload = _buildPayload();
      if (widget.isEdit) {
        await AppState.masterRepository.update(widget.config.resource, widget.itemId!, payload);
      } else {
        await AppState.masterRepository.create(widget.config.resource, payload);
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
      appBar: AppBar(
        title: Text(widget.isEdit ? 'Edit ${widget.config.title}' : 'Tambah ${widget.config.title}'),
      ),
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
                          child: Column(
                            children: widget.config.fields.map(_buildField).toList(),
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
                              : Text(widget.isEdit ? 'Simpan Perubahan' : 'Simpan'),
                        ),
                      ],
                    ),
                  ),
                ),
    );
  }

  Widget _buildField(MasterField field) {
    switch (field.type) {
      case MasterFieldType.select:
      case MasterFieldType.lookup:
        final options = field.type == MasterFieldType.lookup
            ? (_lookupOptions[field.key] ?? [])
                .map(
                  (o) => (
                    value: o[field.lookupValueKey].toString(),
                    label: o[field.lookupLabelKey]?.toString() ?? '-',
                  ),
                )
                .toList()
            : field.options ?? [];

        return Padding(
          padding: const EdgeInsets.only(bottom: 12),
          child: DropdownButtonFormField<String>(
            value: _selectValues[field.key],
            decoration: InputDecoration(labelText: field.label),
            items: options
                .map((o) => DropdownMenuItem(value: o.value, child: Text(o.label)))
                .toList(),
            onChanged: (v) => setState(() => _selectValues[field.key] = v),
            validator: field.required
                ? (v) => v == null || v.isEmpty ? '${field.label} wajib dipilih' : null
                : null,
          ),
        );
      case MasterFieldType.date:
        return Padding(
          padding: const EdgeInsets.only(bottom: 12),
          child: TextFormField(
            controller: _controllers[field.key],
            readOnly: true,
            decoration: InputDecoration(
              labelText: field.label,
              suffixIcon: const Icon(Icons.calendar_today_outlined),
            ),
            onTap: () => _pickDate(field.key),
            validator: field.required
                ? (v) => v == null || v.isEmpty ? '${field.label} wajib diisi' : null
                : null,
          ),
        );
      case MasterFieldType.number:
      case MasterFieldType.text:
        return Padding(
          padding: const EdgeInsets.only(bottom: 12),
          child: TextFormField(
            controller: _controllers[field.key],
            decoration: InputDecoration(labelText: field.label),
            keyboardType: field.type == MasterFieldType.number
                ? const TextInputType.numberWithOptions(decimal: true)
                : TextInputType.text,
            maxLines: field.multiline ? 3 : 1,
            validator: field.required
                ? (v) => v == null || v.trim().isEmpty ? '${field.label} wajib diisi' : null
                : null,
          ),
        );
    }
  }
}

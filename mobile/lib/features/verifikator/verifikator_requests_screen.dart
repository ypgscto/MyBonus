import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:bonusku_mobile/core/network/api_client.dart';
import 'package:bonusku_mobile/core/theme/app_theme.dart';
import 'package:bonusku_mobile/core/utils/formatters.dart';
import 'package:bonusku_mobile/core/widgets/app_widgets.dart';
import 'package:bonusku_mobile/core/widgets/copy_text_button.dart';
import 'package:bonusku_mobile/models/models.dart';
import 'package:bonusku_mobile/providers/app_providers.dart';

enum VerifikatorRequestFilter {
  pending('submitted', 'Perlu Verifikasi'),
  approved('approved_by_verifikator', 'Telah Disetujui'),
  rejected('rejected_by_verifikator', 'Ditolak');

  const VerifikatorRequestFilter(this.status, this.label);

  final String status;
  final String label;
}

class VerifikatorRequestsScreen extends StatefulWidget {
  const VerifikatorRequestsScreen({super.key});

  @override
  State<VerifikatorRequestsScreen> createState() => _VerifikatorRequestsScreenState();
}

class _VerifikatorRequestsScreenState extends State<VerifikatorRequestsScreen> {
  VerifikatorRequestFilter _filter = VerifikatorRequestFilter.pending;
  List<PresenterRequestModel> _items = [];
  bool _loading = true;
  String? _error;

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
      final result = await AppState.verifikatorRepository.list(
        'verifikator/requests',
        query: {'status': _filter.status},
      );

      setState(() {
        _items = result.items;
        _loading = false;
      });
    } on ApiException catch (e) {
      setState(() {
        _error = e.message;
        _loading = false;
      });
    }
  }

  void _onFilterChanged(VerifikatorRequestFilter? value) {
    if (value == null || value == _filter) return;
    setState(() => _filter = value);
    _load();
  }

  String _emptyMessage() {
    return switch (_filter) {
      VerifikatorRequestFilter.pending => 'Tidak ada permintaan yang perlu diverifikasi',
      VerifikatorRequestFilter.approved => 'Tidak ada permintaan yang telah disetujui',
      VerifikatorRequestFilter.rejected => 'Tidak ada permintaan yang ditolak',
    };
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Permintaan'),
      ),
      body: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
            child: DropdownButtonFormField<VerifikatorRequestFilter>(
              value: _filter,
              decoration: const InputDecoration(
                labelText: 'Filter Status',
                contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 10),
              ),
              items: VerifikatorRequestFilter.values
                  .map(
                    (filter) => DropdownMenuItem(
                      value: filter,
                      child: Text(filter.label),
                    ),
                  )
                  .toList(),
              onChanged: _onFilterChanged,
            ),
          ),
          const SizedBox(height: 8),
          Expanded(child: _buildBody()),
        ],
      ),
    );
  }

  Widget _buildBody() {
    if (_loading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_error != null) {
      return Center(child: Text(_error!));
    }

    if (_items.isEmpty) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.inbox_outlined, size: 48, color: AppColors.textSecondary.withValues(alpha: 0.5)),
            const SizedBox(height: 8),
            Text(_emptyMessage()),
          ],
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView.separated(
        padding: const EdgeInsets.all(16),
        itemCount: _items.length,
        separatorBuilder: (_, __) => const SizedBox(height: 10),
        itemBuilder: (context, index) {
          final item = _items[index];
          return _VerifikatorRequestTile(
            item: item,
            onTap: () => context.push('/requests/${item.id}'),
          );
        },
      ),
    );
  }
}

class _VerifikatorRequestTile extends StatelessWidget {
  const _VerifikatorRequestTile({required this.item, required this.onTap});

  final PresenterRequestModel item;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(16),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: AppColors.border),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Row(
                      children: [
                        Flexible(
                          child: Text(
                            item.requestCode,
                            style: Theme.of(context).textTheme.titleSmall?.copyWith(
                                  fontWeight: FontWeight.w800,
                                ),
                          ),
                        ),
                        const SizedBox(width: 6),
                        CopyTextButton(text: item.requestCode, compact: true),
                      ],
                    ),
                  ),
                  StatusBadge(label: item.statusLabel),
                ],
              ),
              const SizedBox(height: 8),
              if (item.presenter != null)
                Text(
                  item.presenter!.name,
                  style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                        color: AppColors.textSecondary,
                      ),
                ),
              const SizedBox(height: 8),
              Row(
                children: [
                  _Meta(icon: Icons.people_outline, text: '${item.totalStudents ?? 0} mhs'),
                  const SizedBox(width: 12),
                  _Meta(
                    icon: Icons.payments_outlined,
                    text: Formatters.currency(item.totalCommission),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _Meta extends StatelessWidget {
  const _Meta({required this.icon, required this.text});

  final IconData icon;
  final String text;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon, size: 14, color: AppColors.textSecondary),
        const SizedBox(width: 4),
        Text(text, style: Theme.of(context).textTheme.labelMedium),
      ],
    );
  }
}

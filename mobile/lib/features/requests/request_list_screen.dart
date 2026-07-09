import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import 'package:bonusku_mobile/core/network/api_client.dart';
import 'package:bonusku_mobile/core/theme/app_theme.dart';
import 'package:bonusku_mobile/core/utils/formatters.dart';
import 'package:bonusku_mobile/core/widgets/app_widgets.dart';
import 'package:bonusku_mobile/core/widgets/copy_text_button.dart';
import 'package:bonusku_mobile/models/models.dart';
import 'package:bonusku_mobile/features/admin/admin_requests_screen.dart';
import 'package:bonusku_mobile/features/verifikator/verifikator_requests_screen.dart';
import 'package:bonusku_mobile/features/keuangan/keuangan_requests_screen.dart';
import 'package:bonusku_mobile/providers/app_providers.dart';

class RequestListScreen extends StatefulWidget {
  const RequestListScreen({super.key});

  @override
  State<RequestListScreen> createState() => _RequestListScreenState();
}

class _RequestListScreenState extends State<RequestListScreen> {
  List<PresenterRequestModel> _items = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final role = context.read<AuthProvider>().user?.role;
      if (role == 'verifikator' || role == 'admin_pmb' || role == 'super_admin' || role == 'keuangan') {
        return;
      }
      _load();
    });
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final role = context.read<AuthProvider>().user!.role;
      PaginatedList<PresenterRequestModel> result;

      switch (role) {
        case 'presenter':
          result = await AppState.requestRepository.list('presenter/requests');
        case 'admin_pmb':
        case 'super_admin':
          result = await AppState.adminRepository.history();
        default:
          result = PaginatedList(items: const []);
      }

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

  String _detailPath(int id) => '/requests/$id';

  @override
  Widget build(BuildContext context) {
    final role = context.watch<AuthProvider>().user?.role;
    if (role == 'admin_pmb' || role == 'super_admin') {
      return const AdminRequestsScreen();
    }
    if (role == 'verifikator') {
      return const VerifikatorRequestsScreen();
    }
    if (role == 'keuangan') {
      return const KeuanganRequestsScreen();
    }

    if (_loading) return const Center(child: CircularProgressIndicator());

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
            const Text('Belum ada permintaan'),
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
          return _RequestTile(
            item: item,
            onTap: () => context.push(_detailPath(item.id)),
          );
        },
      ),
    );
  }
}

class _RequestTile extends StatelessWidget {
  const _RequestTile({required this.item, required this.onTap});

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

import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import 'package:bonusku_mobile/core/network/api_client.dart';
import 'package:bonusku_mobile/core/theme/app_theme.dart';
import 'package:bonusku_mobile/core/utils/formatters.dart';
import 'package:bonusku_mobile/core/widgets/app_widgets.dart';
import 'package:bonusku_mobile/models/models.dart';
import 'package:bonusku_mobile/providers/app_providers.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  Map<String, dynamic>? _data;
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
      final role = context.read<AuthProvider>().user!.role;
      final data = await AppState.dashboardRepository.fetchDashboard(
        rolePrefix: role == 'presenter' ? 'presenter' : null,
      );
      setState(() {
        _data = data;
        _loading = false;
      });
    } on ApiException catch (e) {
      setState(() {
        _error = e.message;
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final user = context.watch<AuthProvider>().user!;

    return PageBackground(
      child: _buildBody(context, user),
    );
  }

  Widget _buildBody(BuildContext context, UserModel user) {
    if (_loading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_error != null) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(_error!),
            const SizedBox(height: 12),
            ElevatedButton(onPressed: _load, child: const Text('Coba Lagi')),
          ],
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _load,
      child: LayoutBuilder(
        builder: (context, constraints) {
          return SingleChildScrollView(
            physics: const AlwaysScrollableScrollPhysics(),
            child: SizedBox(
              height: constraints.maxHeight,
              child: Padding(
                padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    const SizedBox(height: 72),
                    Text(
                      'Halo, ${user.name}',
                      style: Theme.of(context).textTheme.titleLarge?.copyWith(
                            fontWeight: FontWeight.w800,
                            fontSize: 22,
                            color: Colors.white,
                            shadows: const [
                              Shadow(color: Colors.black54, blurRadius: 8, offset: Offset(0, 1)),
                            ],
                          ),
                    ),
                    Text(
                      user.roleLabel,
                      style: Theme.of(context).textTheme.bodySmall?.copyWith(
                            color: Colors.white,
                            fontSize: 13,
                            fontWeight: FontWeight.w600,
                            shadows: const [
                              Shadow(color: Colors.black54, blurRadius: 8, offset: Offset(0, 1)),
                            ],
                          ),
                    ),
                    const Spacer(),
                    ..._buildStats(user.role),
                    const SizedBox(height: 16),
                    _QuickActions(role: user.role),
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
  }

  List<Widget> _buildStats(String role) {
    final data = _data ?? {};

    if (role == 'presenter') {
      return [
        GridView.count(
          crossAxisCount: 2,
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          mainAxisSpacing: 12,
          crossAxisSpacing: 12,
          childAspectRatio: 1.15,
          children: [
            StatCard(
              label: 'Total Mahasiswa',
              value: '${data['total_students'] ?? 0}',
              icon: Icons.school_outlined,
              color: AppColors.secondary,
            ),
            StatCard(
              label: 'Total Komisi',
              value: Formatters.currency((data['total_commission'] as num?)?.toDouble()),
              icon: Icons.payments_outlined,
            ),
            StatCard(
              label: 'Sudah Dibayar',
              value: Formatters.currency((data['paid_commission'] as num?)?.toDouble()),
              icon: Icons.check_circle_outline,
              color: AppColors.success,
            ),
            StatCard(
              label: 'Menunggu',
              value: Formatters.currency((data['pending_commission'] as num?)?.toDouble()),
              icon: Icons.hourglass_empty,
              color: AppColors.warning,
            ),
          ],
        ),
      ];
    }

    if (role == 'verifikator') {
      final counts = data['counts'] as Map<String, dynamic>? ?? {};
      return [
        GridView.count(
          crossAxisCount: 2,
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          mainAxisSpacing: 12,
          crossAxisSpacing: 12,
          childAspectRatio: 1.15,
          children: [
            StatCard(label: 'Menunggu', value: '${counts['pending'] ?? 0}', icon: Icons.pending_actions),
            StatCard(label: 'Disetujui', value: '${counts['approved'] ?? 0}', icon: Icons.verified_outlined, color: AppColors.success),
            StatCard(label: 'Ditolak', value: '${counts['rejected'] ?? 0}', icon: Icons.cancel_outlined, color: AppColors.danger),
            StatCard(label: 'Transfer', value: '${counts['transferred_to_finance'] ?? 0}', icon: Icons.swap_horiz),
          ],
        ),
      ];
    }

    if (role == 'keuangan') {
      final counts = data['counts'] as Map<String, dynamic>? ?? {};
      return [
        GridView.count(
          crossAxisCount: 2,
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          mainAxisSpacing: 12,
          crossAxisSpacing: 12,
          childAspectRatio: 1.15,
          children: [
            StatCard(label: 'Dana Masuk', value: '${counts['awaiting_confirmation'] ?? 0}', icon: Icons.download_outlined),
            StatCard(label: 'Siap Transfer', value: '${counts['awaiting_presenter_transfer'] ?? 0}', icon: Icons.upload_outlined, color: AppColors.secondary),
            StatCard(label: 'Sudah Transfer', value: '${counts['transferred_to_presenter'] ?? 0}', icon: Icons.done_all, color: AppColors.success),
            StatCard(label: 'Closed', value: '${counts['closed'] ?? 0}', icon: Icons.lock_outline),
          ],
        ),
      ];
    }

    return [
      AppCard(
        title: 'Ringkasan',
        child: Text(
          'Gunakan menu di bawah untuk mengelola permintaan dan data master.',
          style: Theme.of(context).textTheme.bodyMedium,
        ),
      ),
    ];
  }
}

class _QuickActions extends StatelessWidget {
  const _QuickActions({required this.role});

  final String role;

  @override
  Widget build(BuildContext context) {
    final actions = <({String label, IconData icon, VoidCallback onTap})>[];

    if (role == 'presenter') {
      actions.add((label: 'Permintaan Saya', icon: Icons.list_alt, onTap: () => context.go('/requests')));
    }
    if (role == 'verifikator') {
      actions.add((label: 'Antrian Verifikasi', icon: Icons.fact_check_outlined, onTap: () => context.go('/requests')));
    }
    if (role == 'keuangan') {
      actions.add((label: 'Antrian Keuangan', icon: Icons.account_balance_outlined, onTap: () => context.go('/requests')));
    }
    if (role == 'admin_pmb' || role == 'super_admin') {
      actions.add((label: 'Permintaan Presenter', icon: Icons.note_add_outlined, onTap: () => context.go('/requests')));
      actions.add((label: 'Master Data', icon: Icons.storage_outlined, onTap: () => context.push('/master')));
    }

    if (actions.isEmpty) return const SizedBox.shrink();

    return AppCard(
      title: 'Aksi Cepat',
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: actions
            .map(
              (a) => Padding(
                padding: const EdgeInsets.only(bottom: 8),
                child: ElevatedButton.icon(
                  onPressed: a.onTap,
                  icon: Icon(a.icon, size: 18),
                  label: Text(a.label),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.primary,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 12),
                  ),
                ),
              ),
            )
            .toList(),
      ),
    );
  }
}

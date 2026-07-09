import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:bonusku_mobile/core/theme/app_theme.dart';
import 'package:bonusku_mobile/core/widgets/app_widgets.dart';

class MasterHubScreen extends StatelessWidget {
  const MasterHubScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final modules = [
      _MasterModule(
        title: 'Kategori Presenter',
        subtitle: 'Kelola kategori komisi presenter',
        icon: Icons.category_outlined,
        route: '/master/presenter-categories',
      ),
      _MasterModule(
        title: 'Presenter',
        subtitle: 'Data presenter dan rekening bank',
        icon: Icons.person_outline,
        route: '/master/presenters',
      ),
      _MasterModule(
        title: 'Periode PMB',
        subtitle: 'Tahun akademik dan gelombang',
        icon: Icons.date_range_outlined,
        route: '/master/pmb-periods',
      ),
      _MasterModule(
        title: 'Skema Komisi',
        subtitle: 'Nominal komisi per kategori',
        icon: Icons.payments_outlined,
        route: '/master/commission-schemes',
      ),
    ];

    return Scaffold(
      appBar: AppBar(title: const Text('Master Data')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          AppCard(
            child: Text(
              'Kelola data referensi yang digunakan saat membuat permintaan presenter.',
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: AppColors.textSecondary),
            ),
          ),
          const SizedBox(height: 16),
          ...modules.map(
            (m) => Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: Material(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                child: InkWell(
                  onTap: () => context.push(m.route),
                  borderRadius: BorderRadius.circular(16),
                  child: Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: AppColors.border),
                    ),
                    child: Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            color: AppColors.primary.withValues(alpha: 0.1),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Icon(m.icon, color: AppColors.primary),
                        ),
                        const SizedBox(width: 14),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                m.title,
                                style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w700),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                m.subtitle,
                                style: Theme.of(context).textTheme.bodySmall?.copyWith(color: AppColors.textSecondary),
                              ),
                            ],
                          ),
                        ),
                        const Icon(Icons.chevron_right, color: AppColors.textSecondary),
                      ],
                    ),
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _MasterModule {
  const _MasterModule({
    required this.title,
    required this.subtitle,
    required this.icon,
    required this.route,
  });

  final String title;
  final String subtitle;
  final IconData icon;
  final String route;
}

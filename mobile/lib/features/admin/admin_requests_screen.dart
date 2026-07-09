import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:bonusku_mobile/core/network/api_client.dart';
import 'package:bonusku_mobile/core/theme/app_theme.dart';
import 'package:bonusku_mobile/core/widgets/app_widgets.dart';
import 'package:bonusku_mobile/core/widgets/commission_preview_card.dart';
import 'package:bonusku_mobile/core/widgets/copy_text_button.dart';
import 'package:bonusku_mobile/models/models.dart';
import 'package:bonusku_mobile/providers/app_providers.dart';

class AdminRequestsScreen extends StatefulWidget {
  const AdminRequestsScreen({super.key});

  @override
  State<AdminRequestsScreen> createState() => _AdminRequestsScreenState();
}

class _AdminRequestsScreenState extends State<AdminRequestsScreen>
    with SingleTickerProviderStateMixin {
  late final TabController _tabController;
  List<PresenterRequestModel> _drafts = [];
  List<PresenterRequestModel> _history = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _load();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final drafts = await AppState.adminRepository.drafts();
      final history = await AppState.adminRepository.history();
      setState(() {
        _drafts = drafts.items;
        _history = history.items;
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
    return Scaffold(
      appBar: AppBar(
        title: const Text('Permintaan Presenter'),
        bottom: TabBar(
          controller: _tabController,
          tabs: [
            Tab(text: 'Draft (${_drafts.length})'),
            const Tab(text: 'Riwayat'),
          ],
        ),
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () async {
          final created = await context.push<bool>('/admin/drafts/create');
          if (created == true) _load();
        },
        icon: const Icon(Icons.add),
        label: const Text('Buat Draft'),
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(child: Text(_error!))
              : TabBarView(
                  controller: _tabController,
                  children: [
                    _RequestList(
                      items: _drafts,
                      emptyMessage: 'Belum ada draft',
                      onRefresh: _load,
                      onTap: (item) async {
                        final updated = await context.push<bool>('/admin/drafts/${item.id}/edit');
                        if (updated == true) _load();
                      },
                    ),
                    _RequestList(
                      items: _history,
                      emptyMessage: 'Belum ada riwayat',
                      onRefresh: _load,
                      onTap: (item) => context.push('/requests/${item.id}'),
                    ),
                  ],
                ),
    );
  }
}

class _RequestList extends StatelessWidget {
  const _RequestList({
    required this.items,
    required this.emptyMessage,
    required this.onRefresh,
    required this.onTap,
  });

  final List<PresenterRequestModel> items;
  final String emptyMessage;
  final Future<void> Function() onRefresh;
  final void Function(PresenterRequestModel item) onTap;

  @override
  Widget build(BuildContext context) {
    if (items.isEmpty) {
      return RefreshIndicator(
        onRefresh: onRefresh,
        child: ListView(
          children: [
            SizedBox(
              height: MediaQuery.of(context).size.height * 0.4,
              child: Center(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(Icons.inbox_outlined, size: 48, color: AppColors.textSecondary.withValues(alpha: 0.5)),
                    const SizedBox(height: 8),
                    Text(emptyMessage),
                  ],
                ),
              ),
            ),
          ],
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: onRefresh,
      child: ListView.separated(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 88),
        itemCount: items.length,
        separatorBuilder: (_, __) => const SizedBox(height: 10),
        itemBuilder: (context, index) {
          final item = items[index];
          return Material(
            color: Colors.white,
            borderRadius: BorderRadius.circular(16),
            child: InkWell(
              onTap: () => onTap(item),
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
                                  style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w800),
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
                        style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: AppColors.textSecondary),
                      ),
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        Icon(Icons.people_outline, size: 14, color: AppColors.textSecondary),
                        const SizedBox(width: 4),
                        Text('${item.studentCount} mhs'),
                        const SizedBox(width: 12),
                        Icon(Icons.payments_outlined, size: 14, color: AppColors.textSecondary),
                        const SizedBox(width: 4),
                        CommissionAmountLabel(
                          amount: item.totalCommission,
                          isPreview: item.commissionIsPreview || item.isDraft,
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}

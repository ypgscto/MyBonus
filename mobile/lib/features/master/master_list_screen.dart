import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:bonusku_mobile/core/network/api_client.dart';
import 'package:bonusku_mobile/core/theme/app_theme.dart';
import 'package:bonusku_mobile/core/widgets/app_widgets.dart';
import 'package:bonusku_mobile/features/master/master_config.dart';
import 'package:bonusku_mobile/providers/app_providers.dart';

class MasterListScreen extends StatefulWidget {
  const MasterListScreen({super.key, required this.config});

  final MasterConfig config;

  @override
  State<MasterListScreen> createState() => _MasterListScreenState();
}

class _MasterListScreenState extends State<MasterListScreen> {
  List<Map<String, dynamic>> _items = [];
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
      final result = await AppState.masterRepository.list(widget.config.resource);
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

  Future<void> _toggleStatus(Map<String, dynamic> item) async {
    try {
      await AppState.masterRepository.toggleStatus(widget.config.resource, item['id'] as int);
      await _load();
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  Future<void> _resendEmail(int presenterId) async {
    try {
      await AppState.masterRepository.resendPresenterEmail(presenterId);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Email akun dikirim ulang')));
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(widget.config.title)),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () async {
          final saved = await context.push<bool>('${widget.config.route}/create');
          if (saved == true) _load();
        },
        icon: const Icon(Icons.add),
        label: const Text('Tambah'),
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(child: Text(_error!))
              : RefreshIndicator(
                  onRefresh: _load,
                  child: _items.isEmpty
                      ? ListView(
                          children: [
                            SizedBox(
                              height: MediaQuery.of(context).size.height * 0.4,
                              child: const Center(child: Text('Belum ada data')),
                            ),
                          ],
                        )
                      : ListView.separated(
                          padding: const EdgeInsets.fromLTRB(16, 16, 16, 88),
                          itemCount: _items.length,
                          separatorBuilder: (_, __) => const SizedBox(height: 10),
                          itemBuilder: (context, index) {
                            final item = _items[index];
                            final status = item['status_label']?.toString() ?? item['status']?.toString() ?? '';
                            final isActive = item['status']?.toString() == 'aktif';

                            return Material(
                              color: Colors.white,
                              borderRadius: BorderRadius.circular(16),
                              child: InkWell(
                                onTap: () async {
                                  final saved = await context.push<bool>(
                                    '${widget.config.route}/${item['id']}/edit',
                                    extra: item,
                                  );
                                  if (saved == true) _load();
                                },
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
                                            child: Text(
                                              widget.config.itemTitle(item),
                                              style: Theme.of(context)
                                                  .textTheme
                                                  .titleSmall
                                                  ?.copyWith(fontWeight: FontWeight.w700),
                                            ),
                                          ),
                                          StatusBadge(
                                            label: status,
                                            color: isActive ? AppColors.success : AppColors.textSecondary,
                                          ),
                                        ],
                                      ),
                                      const SizedBox(height: 6),
                                      Text(
                                        widget.config.itemSubtitle(item),
                                        style: Theme.of(context)
                                            .textTheme
                                            .bodySmall
                                            ?.copyWith(color: AppColors.textSecondary),
                                      ),
                                      const SizedBox(height: 10),
                                      Row(
                                        children: [
                                          TextButton(
                                            onPressed: () => _toggleStatus(item),
                                            child: Text(isActive ? 'Nonaktifkan' : 'Aktifkan'),
                                          ),
                                          if (widget.config.resource == 'presenters') ...[
                                            TextButton(
                                              onPressed: () => _resendEmail(item['id'] as int),
                                              child: const Text('Kirim Email'),
                                            ),
                                          ],
                                        ],
                                      ),
                                    ],
                                  ),
                                ),
                              ),
                            );
                          },
                        ),
                ),
    );
  }
}

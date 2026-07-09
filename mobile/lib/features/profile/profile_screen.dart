import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import 'package:bonusku_mobile/core/theme/app_theme.dart';
import 'package:bonusku_mobile/core/widgets/app_widgets.dart';
import 'package:bonusku_mobile/providers/app_providers.dart';

class ProfileScreen extends StatelessWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final user = context.watch<AuthProvider>().user!;

    return Scaffold(
      appBar: AppBar(title: const Text('Profil')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              gradient: const LinearGradient(colors: [AppColors.primary, AppColors.primaryDark]),
              borderRadius: BorderRadius.circular(20),
            ),
            child: Row(
              children: [
                CircleAvatar(
                  radius: 28,
                  backgroundColor: Colors.white24,
                  child: Text(
                    user.name.isNotEmpty ? user.name[0].toUpperCase() : '?',
                    style: const TextStyle(color: Colors.white, fontSize: 24, fontWeight: FontWeight.w800),
                  ),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(user.name, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 18)),
                      Text(user.roleLabel, style: const TextStyle(color: Colors.white70)),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),
          AppCard(
            title: 'Informasi Akun',
            child: Column(
              children: [
                InfoTile(label: 'Email', value: user.email, copyable: true),
                InfoTile(label: 'Telepon', value: user.phone ?? '-', copyable: user.phone != null),
                InfoTile(label: 'Role', value: user.roleLabel),
              ],
            ),
          ),
          if (user.presenter != null) ...[
            const SizedBox(height: 12),
            AppCard(
              title: 'Data Presenter',
              child: BankAccountPanel(
                title: 'REKENING SAYA',
                bankName: user.presenter!.bankName ?? '-',
                accountNumber: user.presenter!.accountNumber ?? '-',
                accountHolder: user.presenter!.accountHolderName ?? '-',
              ),
            ),
          ],
          if (user.role == 'admin_pmb' || user.role == 'super_admin') ...[
            const SizedBox(height: 12),
            AppCard(
              title: 'Administrasi',
              child: Column(
                children: [
                  ListTile(
                    contentPadding: EdgeInsets.zero,
                    leading: const Icon(Icons.storage_outlined),
                    title: const Text('Master Data'),
                    trailing: const Icon(Icons.chevron_right),
                    onTap: () => context.push('/master'),
                  ),
                  ListTile(
                    contentPadding: EdgeInsets.zero,
                    leading: const Icon(Icons.note_add_outlined),
                    title: const Text('Draft Permintaan'),
                    trailing: const Icon(Icons.chevron_right),
                    onTap: () => context.go('/requests'),
                  ),
                ],
              ),
            ),
          ],
          const SizedBox(height: 20),
          OutlinedButton.icon(
            onPressed: () async {
              await context.read<AuthProvider>().logout();
              if (context.mounted) context.go('/login');
            },
            icon: const Icon(Icons.logout, color: AppColors.danger),
            label: const Text('Keluar', style: TextStyle(color: AppColors.danger)),
          ),
        ],
      ),
    );
  }
}

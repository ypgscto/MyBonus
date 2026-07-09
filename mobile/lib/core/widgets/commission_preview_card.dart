import 'package:flutter/material.dart';
import 'package:bonusku_mobile/core/theme/app_theme.dart';
import 'package:bonusku_mobile/core/utils/formatters.dart';
import 'package:bonusku_mobile/core/widgets/app_widgets.dart';

class CommissionPreviewCard extends StatelessWidget {
  const CommissionPreviewCard({
    super.key,
    required this.totalStudents,
    required this.commissionPerStudent,
    required this.totalCommission,
    this.isPreview = true,
    this.errorMessage,
    this.meta,
    this.loading = false,
  });

  factory CommissionPreviewCard.fromPreviewMap(
    Map<String, dynamic> preview, {
    bool loading = false,
  }) {
    return CommissionPreviewCard(
      totalStudents: preview['total_students'] as int? ?? 0,
      commissionPerStudent: (preview['commission_per_student'] as num?)?.toDouble(),
      totalCommission: (preview['total_commission'] as num?)?.toDouble() ?? 0,
      isPreview: preview['is_preview'] == true,
      errorMessage: preview['available'] == true ? null : preview['message']?.toString(),
      meta: preview['presenter_category'] != null || preview['pmb_period_label'] != null
          ? '${preview['presenter_category'] ?? '-'} · ${preview['pmb_period_label'] ?? '-'}'
          : null,
      loading: loading,
    );
  }

  final int totalStudents;
  final double? commissionPerStudent;
  final double totalCommission;
  final bool isPreview;
  final String? errorMessage;
  final String? meta;
  final bool loading;

  @override
  Widget build(BuildContext context) {
    return AppCard(
      title: isPreview ? 'Estimasi Komisi (Live)' : 'Komisi',
      trailing: isPreview
          ? Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
              decoration: BoxDecoration(
                color: AppColors.warning.withValues(alpha: 0.15),
                borderRadius: BorderRadius.circular(999),
              ),
              child: Text(
                'Preview',
                style: Theme.of(context).textTheme.labelSmall?.copyWith(
                      color: AppColors.warning,
                      fontWeight: FontWeight.w700,
                    ),
              ),
            )
          : null,
      child: loading
          ? const Center(
              child: Padding(
                padding: EdgeInsets.all(12),
                child: SizedBox(
                  width: 22,
                  height: 22,
                  child: CircularProgressIndicator(strokeWidth: 2),
                ),
              ),
            )
          : Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (errorMessage != null) ...[
                  Text(
                    errorMessage!,
                    style: Theme.of(context).textTheme.bodySmall?.copyWith(color: AppColors.danger),
                  ),
                  const SizedBox(height: 8),
                ],
                Row(
                  children: [
                    Expanded(
                      child: _Metric(
                        label: 'Mahasiswa',
                        value: '$totalStudents',
                      ),
                    ),
                    Expanded(
                      child: _Metric(
                        label: 'Per Mhs',
                        value: Formatters.currency(commissionPerStudent),
                      ),
                    ),
                    Expanded(
                      child: _Metric(
                        label: 'Total',
                        value: Formatters.currency(totalCommission),
                        highlight: true,
                      ),
                    ),
                  ],
                ),
                if (meta != null) ...[
                  const SizedBox(height: 8),
                  Text(
                    meta!,
                    style: Theme.of(context).textTheme.bodySmall?.copyWith(color: AppColors.textSecondary),
                  ),
                ],
                if (isPreview) ...[
                  const SizedBox(height: 8),
                  Text(
                    'Dikunci otomatis saat dikirim ke Verifikator',
                    style: Theme.of(context).textTheme.bodySmall?.copyWith(color: AppColors.warning),
                  ),
                ],
              ],
            ),
    );
  }
}

class CommissionAmountLabel extends StatelessWidget {
  const CommissionAmountLabel({
    super.key,
    required this.amount,
    this.isPreview = false,
  });

  final double? amount;
  final bool isPreview;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      mainAxisSize: MainAxisSize.min,
      children: [
        Text(Formatters.currency(amount)),
        if (isPreview)
          Text(
            'estimasi',
            style: Theme.of(context).textTheme.labelSmall?.copyWith(
                  color: AppColors.warning,
                  fontWeight: FontWeight.w600,
                ),
          ),
      ],
    );
  }
}

class _Metric extends StatelessWidget {
  const _Metric({
    required this.label,
    required this.value,
    this.highlight = false,
  });

  final String label;
  final String value;
  final bool highlight;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: Theme.of(context).textTheme.labelSmall?.copyWith(color: AppColors.textSecondary)),
        const SizedBox(height: 2),
        Text(
          value,
          style: Theme.of(context).textTheme.titleSmall?.copyWith(
                fontWeight: FontWeight.w800,
                color: highlight ? AppColors.warning : AppColors.textPrimary,
              ),
        ),
      ],
    );
  }
}

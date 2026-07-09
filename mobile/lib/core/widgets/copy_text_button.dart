import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:bonusku_mobile/core/theme/app_theme.dart';

/// Tombol salin teks — setara `copy-text-button` di web Bonusku.
class CopyTextButton extends StatefulWidget {
  const CopyTextButton({
    super.key,
    required this.text,
    this.label = 'Salin',
    this.compact = false,
  });

  final String text;
  final String label;
  final bool compact;

  @override
  State<CopyTextButton> createState() => _CopyTextButtonState();
}

class _CopyTextButtonState extends State<CopyTextButton> {
  bool _copied = false;

  Future<void> _copy() async {
    final value = widget.text.trim();
    if (value.isEmpty || value == '-') return;

    await Clipboard.setData(ClipboardData(text: value));
    setState(() => _copied = true);
    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: const Text('Tersalin ke clipboard'),
          behavior: SnackBarBehavior.floating,
          duration: const Duration(seconds: 2),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
        ),
      );
    }
    await Future<void>.delayed(const Duration(seconds: 2));
    if (mounted) setState(() => _copied = false);
  }

  @override
  Widget build(BuildContext context) {
    if (widget.compact) {
      return IconButton(
        onPressed: _copy,
        icon: Icon(_copied ? Icons.check_rounded : Icons.copy_rounded, size: 18),
        color: _copied ? AppColors.success : AppColors.textSecondary,
        tooltip: _copied ? 'Tersalin' : 'Salin',
        visualDensity: VisualDensity.compact,
      );
    }

    return OutlinedButton.icon(
      onPressed: _copy,
      icon: Icon(
        _copied ? Icons.check_rounded : Icons.copy_rounded,
        size: 16,
      ),
      label: Text(_copied ? 'Tersalin' : widget.label),
      style: OutlinedButton.styleFrom(
        foregroundColor: _copied ? AppColors.success : AppColors.textSecondary,
        side: BorderSide(
          color: _copied ? AppColors.success.withValues(alpha: 0.4) : AppColors.border,
        ),
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
        minimumSize: Size.zero,
        tapTargetSize: MaterialTapTargetSize.shrinkWrap,
        textStyle: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600),
      ),
    );
  }
}

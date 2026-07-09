import 'package:flutter/material.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'package:bonusku_mobile/app.dart';
import 'package:bonusku_mobile/providers/app_providers.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await initializeDateFormatting('id_ID');
  await AppState.init();
  runApp(const BonuskuApp());
}

import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import 'package:bonusku_mobile/config/env_config.dart';
import 'package:bonusku_mobile/core/theme/app_theme.dart';
import 'package:bonusku_mobile/providers/app_providers.dart';
import 'package:bonusku_mobile/router/app_router.dart';

class BonuskuApp extends StatefulWidget {
  const BonuskuApp({super.key});

  @override
  State<BonuskuApp> createState() => _BonuskuAppState();
}

class _BonuskuAppState extends State<BonuskuApp> {
  late final AuthProvider _authProvider;
  late final GoRouter _router;

  @override
  void initState() {
    super.initState();
    _authProvider = AuthProvider(AppState.authRepository)..bootstrap();
    _router = createRouter(_authProvider);
  }

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider.value(value: _authProvider),
        ChangeNotifierProvider(
          create: (_) => NotificationProvider(AppState.notificationRepository),
        ),
      ],
      child: MaterialApp.router(
        title: EnvConfig.appName,
        theme: AppTheme.light,
        routerConfig: _router,
        debugShowCheckedModeBanner: false,
      ),
    );
  }
}

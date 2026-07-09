import 'package:go_router/go_router.dart';
import 'package:bonusku_mobile/features/admin/add_student_screen.dart';
import 'package:bonusku_mobile/features/admin/draft_create_screen.dart';
import 'package:bonusku_mobile/features/admin/draft_edit_screen.dart';
import 'package:bonusku_mobile/features/auth/change_password_screen.dart';
import 'package:bonusku_mobile/models/models.dart';
import 'package:bonusku_mobile/features/auth/login_screen.dart';
import 'package:bonusku_mobile/features/dashboard/dashboard_screen.dart';
import 'package:bonusku_mobile/features/master/master_config.dart';
import 'package:bonusku_mobile/features/master/master_form_screen.dart';
import 'package:bonusku_mobile/features/master/master_hub_screen.dart';
import 'package:bonusku_mobile/features/master/master_list_screen.dart';
import 'package:bonusku_mobile/features/notifications/notifications_screen.dart';
import 'package:bonusku_mobile/features/profile/profile_screen.dart';
import 'package:bonusku_mobile/features/requests/request_detail_screen.dart';
import 'package:bonusku_mobile/features/requests/request_list_screen.dart';
import 'package:bonusku_mobile/features/shell/main_shell.dart';
import 'package:bonusku_mobile/providers/app_providers.dart';

GoRouter createRouter(AuthProvider auth) {
  return GoRouter(
    initialLocation: '/login',
    refreshListenable: auth,
    redirect: (context, state) {
      final loggedIn = auth.isAuthenticated;
      final loading = auth.loading;
      final loc = state.matchedLocation;

      if (loading) return null;

      final isLogin = loc == '/login';
      final isChangePassword = loc == '/change-password';

      if (!loggedIn && !isLogin) return '/login';
      if (loggedIn && isLogin) {
        if (auth.user?.mustChangePassword == true) return '/change-password';
        return '/home';
      }
      if (loggedIn && auth.user?.mustChangePassword == true && !isChangePassword) {
        return '/change-password';
      }
      return null;
    },
    routes: [
      GoRoute(path: '/login', builder: (_, __) => const LoginScreen()),
      GoRoute(path: '/change-password', builder: (_, __) => const ChangePasswordScreen()),
      GoRoute(
        path: '/requests/:id',
        builder: (_, state) => RequestDetailScreen(id: int.parse(state.pathParameters['id']!)),
      ),
      GoRoute(path: '/admin/drafts/create', builder: (_, __) => const DraftCreateScreen()),
      GoRoute(
        path: '/admin/drafts/:id/edit',
        builder: (_, state) => DraftEditScreen(id: int.parse(state.pathParameters['id']!)),
      ),
      GoRoute(
        path: '/admin/drafts/:id/students/add',
        builder: (_, state) => AddStudentScreen(requestId: int.parse(state.pathParameters['id']!)),
      ),
      GoRoute(
        path: '/admin/drafts/:id/students/:detailId/edit',
        builder: (_, state) => AddStudentScreen(
          requestId: int.parse(state.pathParameters['id']!),
          detail: state.extra is StudentDetailModel ? state.extra as StudentDetailModel : null,
        ),
      ),
      GoRoute(path: '/master', builder: (_, __) => const MasterHubScreen()),
      ..._masterRoutes('/master/presenter-categories', presenterCategoriesConfig),
      ..._masterRoutes('/master/presenters', presentersConfig),
      ..._masterRoutes('/master/pmb-periods', pmbPeriodsConfig),
      ..._masterRoutes('/master/commission-schemes', commissionSchemesConfig),
      StatefulShellRoute.indexedStack(
        builder: (context, state, navigationShell) => MainShell(navigationShell: navigationShell),
        branches: [
          StatefulShellBranch(routes: [
            GoRoute(path: '/home', builder: (_, __) => const DashboardScreen()),
          ]),
          StatefulShellBranch(routes: [
            GoRoute(path: '/requests', builder: (_, __) => const RequestListScreen()),
          ]),
          StatefulShellBranch(routes: [
            GoRoute(path: '/notifications', builder: (_, __) => const NotificationsScreen()),
          ]),
          StatefulShellBranch(routes: [
            GoRoute(path: '/profile', builder: (_, __) => const ProfileScreen()),
          ]),
        ],
      ),
    ],
  );
}

List<RouteBase> _masterRoutes(String basePath, MasterConfig config) {
  return [
    GoRoute(
      path: basePath,
      builder: (_, __) => MasterListScreen(config: config),
      routes: [
        GoRoute(
          path: 'create',
          builder: (_, __) => MasterFormScreen(config: config),
        ),
        GoRoute(
          path: ':id/edit',
          builder: (_, state) => MasterFormScreen(
            config: config,
            itemId: int.parse(state.pathParameters['id']!),
            initialData: state.extra is Map<String, dynamic> ? state.extra as Map<String, dynamic> : null,
          ),
        ),
      ],
    ),
  ];
}

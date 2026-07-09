import 'package:flutter/foundation.dart';
import 'package:bonusku_mobile/core/network/api_client.dart';
import 'package:bonusku_mobile/core/storage/token_storage.dart';
import 'package:bonusku_mobile/models/models.dart';
import 'package:bonusku_mobile/repositories/repositories.dart';

class AuthProvider extends ChangeNotifier {
  AuthProvider(this._authRepo);

  final AuthRepository _authRepo;

  UserModel? _user;
  bool _loading = false;
  String? _error;

  UserModel? get user => _user;
  bool get isAuthenticated => _user != null;
  bool get loading => _loading;
  String? get error => _error;

  Future<void> bootstrap() async {
    _loading = true;
    notifyListeners();
    _user = await _authRepo.restoreSession();
    _loading = false;
    notifyListeners();
  }

  Future<bool> login(String email, String password) async {
    _loading = true;
    _error = null;
    notifyListeners();

    try {
      _user = await _authRepo.login(email, password);
      _loading = false;
      notifyListeners();
      return true;
    } on ApiException catch (e) {
      _error = e.message;
      _loading = false;
      notifyListeners();
      return false;
    }
  }

  Future<void> logout() async {
    await _authRepo.logout();
    _user = null;
    notifyListeners();
  }

  Future<bool> changePassword({
    required String currentPassword,
    required String password,
    required String confirmation,
  }) async {
    _loading = true;
    _error = null;
    notifyListeners();

    try {
      _user = await _authRepo.changePassword(
        currentPassword: currentPassword,
        password: password,
        passwordConfirmation: confirmation,
      );
      _loading = false;
      notifyListeners();
      return true;
    } on ApiException catch (e) {
      _error = e.message;
      _loading = false;
      notifyListeners();
      return false;
    }
  }

  void clearError() {
    _error = null;
    notifyListeners();
  }
}

class NotificationProvider extends ChangeNotifier {
  NotificationProvider(this._repo);

  final NotificationRepository _repo;

  int _unreadCount = 0;
  List<AppNotificationModel> _items = const [];
  bool _loading = false;

  int get unreadCount => _unreadCount;
  List<AppNotificationModel> get items => _items;
  bool get loading => _loading;

  Future<void> refresh() async {
    _loading = true;
    notifyListeners();

    try {
      _unreadCount = await _repo.unreadCount();
      _items = await _repo.fetchNotifications();
    } catch (_) {
      // silent for badge
    }

    _loading = false;
    notifyListeners();
  }

  Future<void> markAsRead(int id) async {
    await _repo.markAsRead(id);
    await refresh();
  }

  Future<void> markAllAsRead() async {
    await _repo.markAllAsRead();
    await refresh();
  }
}

class AppState {
  AppState._();

  static late final ApiClient apiClient;
  static late final AuthRepository authRepository;
  static late final DashboardRepository dashboardRepository;
  static late final NotificationRepository notificationRepository;
  static late final RequestRepository requestRepository;
  static late final VerifikatorRepository verifikatorRepository;
  static late final KeuanganRepository keuanganRepository;
  static late final AdminRepository adminRepository;
  static late final LookupRepository lookupRepository;
  static late final MasterRepository masterRepository;

  static Future<void> init() async {
    final storage = TokenStorage();
    apiClient = ApiClient(storage);
    authRepository = AuthRepository(apiClient, storage);
    dashboardRepository = DashboardRepository(apiClient);
    notificationRepository = NotificationRepository(apiClient);
    requestRepository = RequestRepository(apiClient);
    verifikatorRepository = VerifikatorRepository(apiClient);
    keuanganRepository = KeuanganRepository(apiClient);
    adminRepository = AdminRepository(apiClient);
    lookupRepository = LookupRepository(apiClient);
    masterRepository = MasterRepository(apiClient);
  }
}

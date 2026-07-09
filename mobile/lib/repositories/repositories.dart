import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:bonusku_mobile/core/network/api_client.dart';
import 'package:bonusku_mobile/core/storage/token_storage.dart';
import 'package:bonusku_mobile/models/models.dart';

export 'master_repository.dart';

class AuthRepository {
  AuthRepository(this._api, this._storage);

  final ApiClient _api;
  final TokenStorage _storage;

  Future<UserModel> login(String email, String password) async {
    final response = await _api.post('/auth/login', data: {
      'email': email,
      'password': password,
      'device_name': 'bonusku-mobile',
    });

    final data = response['data'] as Map<String, dynamic>;
    final token = data['token']?.toString() ?? '';
    final user = UserModel.fromJson(data['user'] as Map<String, dynamic>);

    await _storage.saveToken(token);
    await _storage.saveUserJson(user.toJsonString());

    return user;
  }

  Future<UserModel?> restoreSession() async {
    final token = await _storage.getToken();
    final userJson = await _storage.getUserJson();
    if (token == null || userJson == null) return null;

    try {
      final response = await _api.get('/auth/me');
      return UserModel.fromJson(response['data'] as Map<String, dynamic>);
    } catch (_) {
      await _storage.clear();
      return null;
    }
  }

  Future<void> logout() async {
    try {
      await _api.post('/auth/logout');
    } finally {
      await _storage.clear();
    }
  }

  Future<UserModel> changePassword({
    required String currentPassword,
    required String password,
    required String passwordConfirmation,
  }) async {
    final response = await _api.post('/auth/change-password', data: {
      'current_password': currentPassword,
      'password': password,
      'password_confirmation': passwordConfirmation,
    });

    final user = UserModel.fromJson(response['data'] as Map<String, dynamic>);
    await _storage.saveUserJson(user.toJsonString());
    return user;
  }
}

class DashboardRepository {
  DashboardRepository(this._api);

  final ApiClient _api;

  Future<Map<String, dynamic>> fetchDashboard({String? rolePrefix}) async {
    if (rolePrefix == 'presenter') {
      final response = await _api.get('/presenter/dashboard');
      return response['data'] as Map<String, dynamic>;
    }

    final response = await _api.get('/dashboard');
    return response['data'] as Map<String, dynamic>;
  }
}

class NotificationRepository {
  NotificationRepository(this._api);

  final ApiClient _api;

  Future<int> unreadCount() async {
    final response = await _api.get('/notifications/unread-count');
    return (response['data'] as Map<String, dynamic>)['unread_count'] as int? ?? 0;
  }

  Future<List<AppNotificationModel>> fetchNotifications({bool unreadOnly = false}) async {
    final response = await _api.get('/notifications', query: {
      if (unreadOnly) 'unread_only': '1',
      'per_page': '30',
    });

    final paginated = response['data'] as Map<String, dynamic>;
    final items = paginated['data'] as List<dynamic>? ?? [];

    return items
        .whereType<Map<String, dynamic>>()
        .map(AppNotificationModel.fromJson)
        .toList();
  }

  Future<void> markAsRead(int id) async {
    await _api.post('/notifications/$id/read');
  }

  Future<void> markAllAsRead() async {
    await _api.post('/notifications/read-all');
  }

  Future<void> registerDeviceToken(String token, String platform) async {
    await _api.post('/notifications/device-token', data: {
      'token': token,
      'platform': platform,
    });
  }
}

class RequestRepository {
  RequestRepository(this.api);

  final ApiClient api;

  Future<PaginatedList<PresenterRequestModel>> list(String prefix, {Map<String, String>? query}) async {
    final response = await api.get('/$prefix', query: query);
    return _parsePaginated(response['data']);
  }

  Future<PresenterRequestModel> show(String prefix, int id) async {
    final response = await api.get('/$prefix/$id');
    final data = response['data'];

    if (data is Map<String, dynamic> && data.containsKey('request')) {
      return PresenterRequestModel.fromJson(data['request'] as Map<String, dynamic>);
    }

    return PresenterRequestModel.fromJson(data as Map<String, dynamic>);
  }

  Future<String> bankTransferNote(String prefix, int id) async {
    final response = await api.get('/$prefix/$id/bank-transfer-note');
    final data = response['data'] as Map<String, dynamic>;
    return data['bank_transfer_note']?.toString() ?? '';
  }

  Future<DownloadedFile> downloadPaymentProof(int detailId) {
    return api.download('/presenter-request-details/$detailId/payment-proof');
  }

  Future<DownloadedFile> downloadVerifikatorProof(int requestId) {
    return api.download('/keuangan/requests/$requestId/verifikator-proof');
  }

  Future<DownloadedFile> downloadPresenterTransferProof(int requestId) {
    return api.download('/presenter/payouts/$requestId/proof');
  }

  PaginatedList<PresenterRequestModel> _parsePaginated(dynamic data) {
    if (data is! Map<String, dynamic>) {
      return PaginatedList(items: const []);
    }

    final items = (data['data'] as List<dynamic>? ?? [])
        .whereType<Map<String, dynamic>>()
        .map(PresenterRequestModel.fromJson)
        .toList();

    return PaginatedList(
      items: items,
      currentPage: data['current_page'] as int? ?? 1,
      lastPage: data['last_page'] as int? ?? 1,
      total: data['total'] as int? ?? items.length,
    );
  }
}

class VerifikatorRepository extends RequestRepository {
  VerifikatorRepository(super.api);

  Future<List<Map<String, dynamic>>> financeUsers() async {
    final response = await api.get('/verifikator/finance-users');
    final data = response['data'];
    if (data is List) {
      return data.whereType<Map<String, dynamic>>().toList();
    }
    return const [];
  }

  Future<void> approve(int id, {String? note}) async {
    await api.post('/verifikator/requests/$id/approve', data: {
      if (note != null && note.isNotEmpty) 'verifikator_note': note,
    });
  }

  Future<void> reject(int id, String reason) async {
    await api.post('/verifikator/requests/$id/reject', data: {
      'rejection_reason': reason,
    });
  }
}

class KeuanganRepository extends RequestRepository {
  KeuanganRepository(super.api);

  Future<void> confirmReceived(int id) async {
    await api.post('/keuangan/requests/$id/confirm-received');
  }

  Future<void> closeRequest(int id) async {
    await api.post('/keuangan/requests/$id/close');
  }
}

class AdminRepository extends RequestRepository {
  AdminRepository(super.api);

  Future<PaginatedList<PresenterRequestModel>> drafts({String? search}) {
    return list('admin/presenter-requests/drafts', query: {
      if (search != null && search.isNotEmpty) 'search': search,
    });
  }

  Future<PaginatedList<PresenterRequestModel>> history({String? search, String? status}) {
    return list('admin/presenter-requests/history', query: {
      if (search != null && search.isNotEmpty) 'search': search,
      if (status != null && status.isNotEmpty) 'status': status,
    });
  }

  @override
  Future<PresenterRequestModel> show(String prefix, int id) {
    return super.show('admin/presenter-requests', id);
  }

  Future<PresenterRequestModel> createDraft({
    required int pmbPeriodId,
    required int presenterId,
    String? adminNote,
  }) async {
    final response = await api.post('/admin/presenter-requests', data: {
      'action': 'draft',
      'pmb_period_id': pmbPeriodId,
      'presenter_id': presenterId,
      if (adminNote != null && adminNote.isNotEmpty) 'admin_note': adminNote,
    });

    return PresenterRequestModel.fromJson(response['data'] as Map<String, dynamic>);
  }

  Future<PresenterRequestModel> updateDraftHeader(
    int id, {
    required int pmbPeriodId,
    required int presenterId,
    String? adminNote,
  }) async {
    final response = await api.put('/admin/presenter-requests/$id', data: {
      'pmb_period_id': pmbPeriodId,
      'presenter_id': presenterId,
      if (adminNote != null) 'admin_note': adminNote,
    });

    return PresenterRequestModel.fromJson(response['data'] as Map<String, dynamic>);
  }

  Future<StudentDetailModel> addStudent(
    int requestId, {
    required String nim,
    required String studentName,
    String? birthDate,
    String? paymentDate,
    String? note,
    String? paymentProofPath,
  }) async {
    final formData = FormData.fromMap({
      'nim': nim,
      'student_name': studentName,
      if (birthDate != null) 'birth_date': birthDate,
      if (paymentDate != null) 'payment_date': paymentDate,
      if (note != null && note.isNotEmpty) 'note': note,
      if (paymentProofPath != null)
        'payment_proof': await MultipartFile.fromFile(
          paymentProofPath,
          filename: paymentProofPath.split(RegExp(r'[/\\]')).last,
        ),
    });

    final response = await api.post(
      '/admin/presenter-requests/$requestId/details',
      formData: formData,
    );

    return StudentDetailModel.fromJson(response['data'] as Map<String, dynamic>);
  }

  Future<StudentDetailModel> updateStudent(
    int requestId,
    int detailId, {
    required String nim,
    required String studentName,
    String? birthDate,
    String? paymentDate,
    String? note,
    String? paymentProofPath,
  }) async {
    final payload = <String, dynamic>{
      'nim': nim,
      'student_name': studentName,
      if (birthDate != null) 'birth_date': birthDate,
      if (paymentDate != null) 'payment_date': paymentDate,
      if (note != null && note.isNotEmpty) 'note': note,
    };

    final Map<String, dynamic> response;

    if (paymentProofPath != null) {
      // Laravel cannot parse multipart fields on PUT — use POST + method spoofing.
      final formData = FormData.fromMap({
        ...payload,
        '_method': 'PUT',
        'payment_proof': await MultipartFile.fromFile(
          paymentProofPath,
          filename: paymentProofPath.split(RegExp(r'[/\\]')).last,
        ),
      });

      response = await api.post(
        '/admin/presenter-requests/$requestId/details/$detailId',
        formData: formData,
      );
    } else {
      response = await api.put(
        '/admin/presenter-requests/$requestId/details/$detailId',
        data: payload,
      );
    }

    return StudentDetailModel.fromJson(response['data'] as Map<String, dynamic>);
  }

  Future<void> deleteStudent(int requestId, int detailId) async {
    await api.delete('/admin/presenter-requests/$requestId/details/$detailId');
  }

  Future<PresenterRequestModel> submitDraft(int id) async {
    final response = await api.post('/admin/presenter-requests/$id/submit');
    final data = response['data'] as Map<String, dynamic>;
    return PresenterRequestModel.fromJson(data['request'] as Map<String, dynamic>);
  }

  Future<Map<String, dynamic>> checkNim(
    int requestId, {
    required String nim,
    String? studentName,
    int? excludeDetailId,
  }) async {
    final response = await api.get('/admin/presenter-requests/$requestId/check-nim', query: {
      'nim': nim,
      if (studentName != null) 'student_name': studentName,
      if (excludeDetailId != null) 'exclude_detail_id': '$excludeDetailId',
    });

    return response['data'] as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> commissionPreview(
    int requestId, {
    int? presenterId,
    int? pmbPeriodId,
  }) async {
    final response = await api.get('/admin/presenter-requests/$requestId/commission-preview', query: {
      if (presenterId != null) 'presenter_id': '$presenterId',
      if (pmbPeriodId != null) 'pmb_period_id': '$pmbPeriodId',
    });

    return response['data'] as Map<String, dynamic>;
  }
}

extension UserModelCache on UserModel {
  static UserModel? fromStored(String? json) {
    if (json == null) return null;
    try {
      return UserModel.fromJson(jsonDecode(json) as Map<String, dynamic>);
    } catch (_) {
      return null;
    }
  }
}

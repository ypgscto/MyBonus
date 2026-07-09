import 'package:bonusku_mobile/core/network/api_client.dart';
import 'package:bonusku_mobile/models/models.dart';

class LookupRepository {
  LookupRepository(this._api);

  final ApiClient _api;

  Future<List<Map<String, dynamic>>> pmbPeriods() async {
    final response = await _api.get('/lookups/pmb-periods');
    final data = response['data'];
    if (data is List) return data.whereType<Map<String, dynamic>>().toList();
    return const [];
  }

  Future<List<Map<String, dynamic>>> presenters() async {
    final response = await _api.get('/lookups/presenters');
    final data = response['data'];
    if (data is List) return data.whereType<Map<String, dynamic>>().toList();
    return const [];
  }

  Future<List<Map<String, dynamic>>> requestStatuses() async {
    final response = await _api.get('/lookups/request-statuses');
    final data = response['data'];
    if (data is List) return data.whereType<Map<String, dynamic>>().toList();
    return const [];
  }
}

class MasterRepository {
  MasterRepository(this._api);

  final ApiClient _api;

  Future<PaginatedList<Map<String, dynamic>>> list(
    String resource, {
    Map<String, String>? query,
  }) async {
    final response = await _api.get('/master/$resource', query: query);
    return _parsePaginated(response['data']);
  }

  Future<Map<String, dynamic>> show(String resource, int id) async {
    final response = await _api.get('/master/$resource/$id');
    return response['data'] as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> create(String resource, Map<String, dynamic> data) async {
    final response = await _api.post('/master/$resource', data: data);
    return response['data'] as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> update(String resource, int id, Map<String, dynamic> data) async {
    final response = await _api.put('/master/$resource/$id', data: data);
    return response['data'] as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> toggleStatus(String resource, int id) async {
    final response = await _api.post('/master/$resource/$id/toggle-status');
    return response['data'] as Map<String, dynamic>;
  }

  Future<void> resendPresenterEmail(int presenterId) async {
    await _api.post('/master/presenters/$presenterId/resend-account-email');
  }

  PaginatedList<Map<String, dynamic>> _parsePaginated(dynamic data) {
    if (data is! Map<String, dynamic>) {
      return PaginatedList(items: const []);
    }

    final items = (data['data'] as List<dynamic>? ?? [])
        .whereType<Map<String, dynamic>>()
        .toList();

    return PaginatedList(
      items: items,
      currentPage: data['current_page'] as int? ?? 1,
      lastPage: data['last_page'] as int? ?? 1,
      total: data['total'] as int? ?? items.length,
    );
  }
}

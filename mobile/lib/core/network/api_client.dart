import 'package:dio/dio.dart';
import 'dart:convert';
import 'package:bonusku_mobile/config/env_config.dart';
import 'package:bonusku_mobile/core/storage/token_storage.dart';

class ApiException implements Exception {
  ApiException(this.message, {this.statusCode, this.errors});

  final String message;
  final int? statusCode;
  final Map<String, dynamic>? errors;

  @override
  String toString() => message;
}

class DownloadedFile {
  DownloadedFile({required this.bytes, required this.filename});

  final List<int> bytes;
  final String filename;
}

class ApiClient {
  ApiClient(this._tokenStorage) {
    _dio = Dio(
      BaseOptions(
        baseUrl: EnvConfig.apiUrl,
        connectTimeout: const Duration(seconds: 30),
        receiveTimeout: const Duration(seconds: 30),
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      ),
    );

    _dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          final token = await _tokenStorage.getToken();
          if (token != null && token.isNotEmpty) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          handler.next(options);
        },
        onError: (error, handler) {
          handler.next(error);
        },
      ),
    );
  }

  final TokenStorage _tokenStorage;
  late final Dio _dio;

  Dio get dio => _dio;

  Future<Map<String, dynamic>> get(String path, {Map<String, dynamic>? query}) async {
    try {
      final response = await _dio.get(path, queryParameters: query);
      return _unwrap(response.data);
    } on DioException catch (e) {
      throw _mapError(e);
    }
  }

  Future<Map<String, dynamic>> post(
    String path, {
    Map<String, dynamic>? data,
    FormData? formData,
  }) async {
    try {
      final response = await _dio.post(path, data: formData ?? data);
      return _unwrap(response.data);
    } on DioException catch (e) {
      throw _mapError(e);
    }
  }

  Future<Map<String, dynamic>> put(String path, {Map<String, dynamic>? data, FormData? formData}) async {
    try {
      final response = await _dio.put(path, data: formData ?? data);
      return _unwrap(response.data);
    } on DioException catch (e) {
      throw _mapError(e);
    }
  }

  Future<Map<String, dynamic>> delete(String path, {Map<String, dynamic>? data}) async {
    try {
      final response = await _dio.delete(path, data: data);
      return _unwrap(response.data);
    } on DioException catch (e) {
      throw _mapError(e);
    }
  }

  String fileUrl(String path) {
    if (path.startsWith('http')) return path;
    return '${EnvConfig.apiUrl}$path';
  }

  Future<DownloadedFile> download(String path) async {
    try {
      final response = await _dio.get<List<int>>(
        path,
        options: Options(responseType: ResponseType.bytes),
      );

      final data = response.data;
      if (data == null || data.isEmpty) {
        throw ApiException('File tidak ditemukan.', statusCode: response.statusCode);
      }

      if (response.headers.value('content-type')?.contains('application/json') == true) {
        throw _mapJsonBytesError(data, response.statusCode);
      }

      return DownloadedFile(
        bytes: data,
        filename: _filenameFromDisposition(response.headers.value('content-disposition')) ?? 'download.bin',
      );
    } on DioException catch (e) {
      throw _mapError(e);
    }
  }

  ApiException _mapJsonBytesError(List<int> bytes, int? statusCode) {
    try {
      final text = String.fromCharCodes(bytes);
      final decoded = jsonDecode(text);
      if (decoded is Map<String, dynamic>) {
        return ApiException(
          decoded['message']?.toString() ?? 'File tidak ditemukan.',
          statusCode: statusCode,
        );
      }
    } catch (_) {
      // fall through
    }

    return ApiException('File tidak ditemukan.', statusCode: statusCode);
  }

  String? _filenameFromDisposition(String? disposition) {
    if (disposition == null || disposition.isEmpty) return null;

    final utfMatch = RegExp(r"filename\*=UTF-8''([^;]+)", caseSensitive: false).firstMatch(disposition);
    if (utfMatch != null) {
      return Uri.decodeComponent(utfMatch.group(1)!);
    }

    final match = RegExp(r'filename="?([^";]+)"?', caseSensitive: false).firstMatch(disposition);
    return match?.group(1);
  }

  Map<String, dynamic> _unwrap(dynamic data) {
    if (data is Map<String, dynamic>) {
      if (data['success'] == false) {
        throw ApiException(
          data['message']?.toString() ?? 'Request gagal',
          errors: data['errors'] is Map<String, dynamic> ? data['errors'] as Map<String, dynamic> : null,
        );
      }
      return data;
    }
    return {'data': data};
  }

  ApiException _mapError(DioException error) {
    final response = error.response;
    final data = response?.data;

    if (data is Map<String, dynamic>) {
      return ApiException(
        data['message']?.toString() ?? 'Terjadi kesalahan',
        statusCode: response?.statusCode,
        errors: data['errors'] is Map<String, dynamic> ? data['errors'] as Map<String, dynamic> : null,
      );
    }

    if (error.type == DioExceptionType.connectionError) {
      return ApiException(
        'Tidak dapat terhubung ke server. Periksa API_BASE_URL dan koneksi internet.',
        statusCode: response?.statusCode,
      );
    }

    return ApiException(
      error.message ?? 'Terjadi kesalahan jaringan',
      statusCode: response?.statusCode,
    );
  }
}

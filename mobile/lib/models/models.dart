import 'dart:convert';

class UserModel {
  UserModel({
    required this.id,
    required this.name,
    required this.email,
    required this.role,
    required this.roleLabel,
    required this.mustChangePassword,
    this.phone,
    this.presenter,
  });

  factory UserModel.fromJson(Map<String, dynamic> json) {
    return UserModel(
      id: json['id'] as int,
      name: json['name']?.toString() ?? '',
      email: json['email']?.toString() ?? '',
      role: json['role']?.toString() ?? '',
      roleLabel: json['role_label']?.toString() ?? '',
      mustChangePassword: json['must_change_password'] == true,
      phone: json['phone']?.toString(),
      presenter: json['presenter'] is Map<String, dynamic>
          ? PresenterSummary.fromJson(json['presenter'] as Map<String, dynamic>)
          : null,
    );
  }

  final int id;
  final String name;
  final String email;
  final String role;
  final String roleLabel;
  final bool mustChangePassword;
  final String? phone;
  final PresenterSummary? presenter;

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'email': email,
        'role': role,
        'role_label': roleLabel,
        'must_change_password': mustChangePassword,
        'phone': phone,
        'presenter': presenter?.toJson(),
      };

  String toJsonString() => jsonEncode(toJson());
}

class PresenterSummary {
  PresenterSummary({
    required this.id,
    required this.name,
    this.bankName,
    this.accountNumber,
    this.accountHolderName,
    this.phone,
    this.email,
  });

  factory PresenterSummary.fromJson(Map<String, dynamic> json) {
    return PresenterSummary(
      id: json['id'] as int,
      name: json['name']?.toString() ?? '',
      bankName: json['bank_name']?.toString(),
      accountNumber: json['account_number']?.toString(),
      accountHolderName: json['account_holder_name']?.toString(),
      phone: json['phone']?.toString(),
      email: json['email']?.toString(),
    );
  }

  final int id;
  final String name;
  final String? bankName;
  final String? accountNumber;
  final String? accountHolderName;
  final String? phone;
  final String? email;

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'bank_name': bankName,
        'account_number': accountNumber,
        'account_holder_name': accountHolderName,
        'phone': phone,
        'email': email,
      };
}

class PresenterRequestModel {
  PresenterRequestModel({
    required this.id,
    required this.requestCode,
    required this.status,
    required this.statusLabel,
    this.presenterStatusLabel,
    this.requestDate,
    this.submittedAt,
    this.totalStudents,
    this.commissionPerStudent,
    this.totalCommission,
    this.commissionIsPreview = false,
    this.isEditable = false,
    this.rejectionReason,
    this.adminNote,
    this.verifikatorNote,
    this.financeNote,
    this.bankTransferNote,
    this.presenter,
    this.pmbPeriod,
    this.details = const [],
    this.verifikatorTransfer,
    this.presenterTransfer,
  });

  factory PresenterRequestModel.fromJson(Map<String, dynamic> json) {
    return PresenterRequestModel(
      id: json['id'] as int,
      requestCode: json['request_code']?.toString() ?? '',
      status: json['status']?.toString() ?? '',
      statusLabel: json['status_label']?.toString() ?? '',
      presenterStatusLabel: json['presenter_status_label']?.toString(),
      requestDate: json['request_date']?.toString(),
      submittedAt: json['submitted_at']?.toString(),
      totalStudents: json['total_students'] as int?,
      commissionPerStudent: _toDouble(json['commission_per_student']),
      totalCommission: _toDouble(json['total_commission']),
      commissionIsPreview: json['commission_is_preview'] == true,
      isEditable: json['is_editable'] == true || json['status']?.toString() == 'draft',
      rejectionReason: json['rejection_reason']?.toString(),
      adminNote: json['admin_note']?.toString(),
      verifikatorNote: json['verifikator_note']?.toString(),
      financeNote: json['finance_note']?.toString(),
      bankTransferNote: json['bank_transfer_note']?.toString(),
      presenter: json['presenter'] is Map<String, dynamic>
          ? PresenterSummary.fromJson(json['presenter'] as Map<String, dynamic>)
          : null,
      pmbPeriod: json['pmb_period'] is Map<String, dynamic>
          ? json['pmb_period'] as Map<String, dynamic>
          : null,
      details: (json['details'] as List<dynamic>? ?? [])
          .whereType<Map<String, dynamic>>()
          .map(StudentDetailModel.fromJson)
          .toList(),
      verifikatorTransfer: json['verifikator_transfer'] is Map<String, dynamic>
          ? json['verifikator_transfer'] as Map<String, dynamic>
          : null,
      presenterTransfer: json['presenter_transfer'] is Map<String, dynamic>
          ? json['presenter_transfer'] as Map<String, dynamic>
          : null,
    );
  }

  final int id;
  final String requestCode;
  final String status;
  final String statusLabel;
  final String? presenterStatusLabel;
  final String? requestDate;
  final String? submittedAt;
  final int? totalStudents;
  final double? commissionPerStudent;
  final double? totalCommission;
  final bool commissionIsPreview;
  final bool isEditable;
  final String? rejectionReason;
  final String? adminNote;
  final String? verifikatorNote;
  final String? financeNote;
  final String? bankTransferNote;
  final PresenterSummary? presenter;
  final Map<String, dynamic>? pmbPeriod;
  final List<StudentDetailModel> details;
  final Map<String, dynamic>? verifikatorTransfer;
  final Map<String, dynamic>? presenterTransfer;

  bool get isDraft => status == 'draft';

  int get studentCount => totalStudents ?? details.length;

  bool get isTransferredToFinanceOrBeyond => const {
        'transferred_to_finance',
        'received_by_finance',
        'transferred_to_presenter',
        'closed',
      }.contains(status);

  bool get canVerifikatorVerify => status == 'submitted';

  bool get canVerifikatorTransfer => status == 'approved_by_verifikator';

  List<StudentDetailModel> get paymentProofDetails =>
      details.where((detail) => detail.hasPaymentProof).toList();

  StudentDetailModel? get firstPaymentProofDetail {
    for (final detail in details) {
      if (detail.hasPaymentProof) return detail;
    }

    return null;
  }

  String get bankTransferNoteOrComputed {
    if (bankTransferNote != null && bankTransferNote!.isNotEmpty) {
      return bankTransferNote!;
    }

    final nims = details.map((d) => d.nim).where((nim) => nim.isNotEmpty).join(', ');

    return '$requestCode : $nims';
  }

  String? get paymentDate {
    for (final detail in details) {
      final date = detail.paymentDate;
      if (date != null && date.isNotEmpty) {
        return date;
      }
    }

    return null;
  }

  static double? _toDouble(dynamic value) {
    if (value == null) return null;
    if (value is num) return value.toDouble();
    return double.tryParse(value.toString());
  }
}

class StudentDetailModel {
  StudentDetailModel({
    required this.id,
    required this.nim,
    required this.studentName,
    this.birthDate,
    this.paymentDate,
    this.hasPaymentProof = false,
  });

  factory StudentDetailModel.fromJson(Map<String, dynamic> json) {
    return StudentDetailModel(
      id: json['id'] as int,
      nim: json['nim']?.toString() ?? '',
      studentName: json['student_name']?.toString() ?? '',
      birthDate: json['birth_date']?.toString(),
      paymentDate: json['payment_date']?.toString(),
      hasPaymentProof: json['has_payment_proof'] == true,
    );
  }

  final int id;
  final String nim;
  final String studentName;
  final String? birthDate;
  final String? paymentDate;
  final bool hasPaymentProof;

  bool get isReadyForSubmit =>
      nim.isNotEmpty &&
      studentName.isNotEmpty &&
      birthDate != null &&
      birthDate!.isNotEmpty &&
      paymentDate != null &&
      paymentDate!.isNotEmpty &&
      hasPaymentProof;

  List<String> get missingSubmitFields {
    final missing = <String>[];
    if (birthDate == null || birthDate!.isEmpty) missing.add('Tanggal lahir');
    if (paymentDate == null || paymentDate!.isEmpty) missing.add('Tanggal bayar');
    if (!hasPaymentProof) missing.add('Bukti pembayaran');
    return missing;
  }
}

class AppNotificationModel {
  AppNotificationModel({
    required this.id,
    required this.title,
    required this.body,
    required this.type,
    required this.isRead,
    this.presenterRequestId,
    this.requestCode,
    this.data,
    this.createdAt,
  });

  factory AppNotificationModel.fromJson(Map<String, dynamic> json) {
    return AppNotificationModel(
      id: json['id'] as int,
      title: json['title']?.toString() ?? '',
      body: json['body']?.toString() ?? '',
      type: json['type']?.toString() ?? '',
      isRead: json['is_read'] == true,
      presenterRequestId: json['presenter_request_id'] as int?,
      requestCode: json['request_code']?.toString(),
      data: json['data'] is Map<String, dynamic> ? json['data'] as Map<String, dynamic> : null,
      createdAt: json['created_at']?.toString(),
    );
  }

  final int id;
  final String title;
  final String body;
  final String type;
  final bool isRead;
  final int? presenterRequestId;
  final String? requestCode;
  final Map<String, dynamic>? data;
  final String? createdAt;
}

class PaginatedList<T> {
  PaginatedList({required this.items, this.currentPage = 1, this.lastPage = 1, this.total = 0});

  final List<T> items;
  final int currentPage;
  final int lastPage;
  final int total;

  bool get hasMore => currentPage < lastPage;
}

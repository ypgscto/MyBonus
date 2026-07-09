class MasterConfig {
  const MasterConfig({
    required this.resource,
    required this.title,
    required this.route,
    required this.itemTitle,
    required this.itemSubtitle,
    required this.fields,
  });

  final String resource;
  final String title;
  final String route;
  final String Function(Map<String, dynamic> item) itemTitle;
  final String Function(Map<String, dynamic> item) itemSubtitle;
  final List<MasterField> fields;
}

class MasterField {
  const MasterField({
    required this.key,
    required this.label,
    this.type = MasterFieldType.text,
    this.required = true,
    this.options,
    this.lookupResource,
    this.lookupLabelKey = 'name',
    this.lookupValueKey = 'id',
    this.multiline = false,
  });

  final String key;
  final String label;
  final MasterFieldType type;
  final bool required;
  final List<({String value, String label})>? options;
  final String? lookupResource;
  final String lookupLabelKey;
  final String lookupValueKey;
  final bool multiline;
}

enum MasterFieldType { text, number, date, select, lookup }

const presenterCategoriesConfig = MasterConfig(
  resource: 'presenter-categories',
  title: 'Kategori Presenter',
  route: '/master/presenter-categories',
  itemTitle: _categoryTitle,
  itemSubtitle: _categorySubtitle,
  fields: [
    MasterField(key: 'name', label: 'Nama Kategori'),
    MasterField(key: 'description', label: 'Deskripsi', required: false, multiline: true),
    MasterField(
      key: 'status',
      label: 'Status',
      type: MasterFieldType.select,
      options: [
        (value: 'aktif', label: 'Aktif'),
        (value: 'nonaktif', label: 'Nonaktif'),
      ],
    ),
  ],
);

const presentersConfig = MasterConfig(
  resource: 'presenters',
  title: 'Presenter',
  route: '/master/presenters',
  itemTitle: _presenterTitle,
  itemSubtitle: _presenterSubtitle,
  fields: [
    MasterField(
      key: 'presenter_category_id',
      label: 'Kategori',
      type: MasterFieldType.lookup,
      lookupResource: 'presenter-categories',
      lookupLabelKey: 'name',
    ),
    MasterField(key: 'name', label: 'Nama Presenter'),
    MasterField(key: 'email', label: 'Email'),
    MasterField(key: 'phone', label: 'Nomor HP'),
    MasterField(key: 'bank_name', label: 'Nama Bank'),
    MasterField(key: 'account_number', label: 'Nomor Rekening'),
    MasterField(key: 'account_holder_name', label: 'Atas Nama Rekening'),
    MasterField(key: 'address', label: 'Alamat', required: false, multiline: true),
    MasterField(key: 'note', label: 'Catatan', required: false, multiline: true),
    MasterField(
      key: 'status',
      label: 'Status',
      type: MasterFieldType.select,
      options: [
        (value: 'aktif', label: 'Aktif'),
        (value: 'nonaktif', label: 'Nonaktif'),
      ],
    ),
  ],
);

const pmbPeriodsConfig = MasterConfig(
  resource: 'pmb-periods',
  title: 'Periode PMB',
  route: '/master/pmb-periods',
  itemTitle: _pmbTitle,
  itemSubtitle: _pmbSubtitle,
  fields: [
    MasterField(key: 'academic_year', label: 'Tahun Akademik'),
    MasterField(key: 'wave', label: 'Gelombang'),
    MasterField(key: 'start_date', label: 'Tanggal Mulai', type: MasterFieldType.date),
    MasterField(key: 'end_date', label: 'Tanggal Selesai', type: MasterFieldType.date),
    MasterField(
      key: 'status',
      label: 'Status',
      type: MasterFieldType.select,
      options: [
        (value: 'aktif', label: 'Aktif'),
        (value: 'nonaktif', label: 'Nonaktif'),
      ],
    ),
  ],
);

const commissionSchemesConfig = MasterConfig(
  resource: 'commission-schemes',
  title: 'Skema Komisi',
  route: '/master/commission-schemes',
  itemTitle: _schemeTitle,
  itemSubtitle: _schemeSubtitle,
  fields: [
    MasterField(
      key: 'pmb_period_id',
      label: 'Periode PMB',
      type: MasterFieldType.lookup,
      lookupResource: 'pmb-periods',
      lookupLabelKey: 'label',
    ),
    MasterField(
      key: 'presenter_category_id',
      label: 'Kategori Presenter',
      type: MasterFieldType.lookup,
      lookupResource: 'presenter-categories',
      lookupLabelKey: 'name',
    ),
    MasterField(
      key: 'commission_amount_per_student',
      label: 'Komisi per Mahasiswa',
      type: MasterFieldType.number,
    ),
    MasterField(
      key: 'status',
      label: 'Status',
      type: MasterFieldType.select,
      options: [
        (value: 'aktif', label: 'Aktif'),
        (value: 'nonaktif', label: 'Nonaktif'),
      ],
    ),
  ],
);

String _categoryTitle(Map<String, dynamic> item) => item['name']?.toString() ?? '-';
String _categorySubtitle(Map<String, dynamic> item) => item['description']?.toString() ?? '-';

String _presenterTitle(Map<String, dynamic> item) => item['name']?.toString() ?? '-';
String _presenterSubtitle(Map<String, dynamic> item) {
  final category = item['category'] is Map ? (item['category'] as Map)['name'] : null;
  return '${category ?? '-'} • ${item['email'] ?? '-'}';
}

String _pmbTitle(Map<String, dynamic> item) => item['label']?.toString() ?? '${item['academic_year']} ${item['wave']}';
String _pmbSubtitle(Map<String, dynamic> item) => '${item['start_date']} s/d ${item['end_date']}';

String _schemeTitle(Map<String, dynamic> item) {
  final category = item['presenter_category'] is Map ? (item['presenter_category'] as Map)['name'] : null;
  return category?.toString() ?? 'Skema Komisi';
}

String _schemeSubtitle(Map<String, dynamic> item) {
  final period = item['pmb_period'] is Map ? (item['pmb_period'] as Map)['label'] : null;
  final amount = item['commission_amount_per_student'];
  return '${period ?? '-'} • Rp $amount / mhs';
}

MasterConfig masterConfigForRoute(String route) {
  return switch (route) {
    '/master/presenter-categories' => presenterCategoriesConfig,
    '/master/presenters' => presentersConfig,
    '/master/pmb-periods' => pmbPeriodsConfig,
    '/master/commission-schemes' => commissionSchemesConfig,
    _ => presenterCategoriesConfig,
  };
}

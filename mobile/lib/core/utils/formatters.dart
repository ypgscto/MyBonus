import 'package:intl/intl.dart';

class Formatters {
  static final _currency = NumberFormat.currency(
    locale: 'id_ID',
    symbol: 'Rp ',
    decimalDigits: 0,
  );

  static final _date = DateFormat('d MMM yyyy', 'id_ID');
  static final _dateTime = DateFormat('d MMM yyyy, HH:mm', 'id_ID');

  static String currency(num? value) {
    if (value == null) return '-';
    return _currency.format(value);
  }

  static String date(String? iso) {
    if (iso == null || iso.isEmpty) return '-';
    try {
      return _date.format(DateTime.parse(iso));
    } catch (_) {
      return iso;
    }
  }

  static String dateTime(String? iso) {
    if (iso == null || iso.isEmpty) return '-';
    try {
      return _dateTime.format(DateTime.parse(iso));
    } catch (_) {
      return iso;
    }
  }
}

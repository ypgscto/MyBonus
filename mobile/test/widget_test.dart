import 'package:flutter_test/flutter_test.dart';
import 'package:bonusku_mobile/app.dart';
import 'package:bonusku_mobile/providers/app_providers.dart';

void main() {
  testWidgets('Bonusku app smoke test', (tester) async {
    await AppState.init();
    await tester.pumpWidget(const BonuskuApp());
    await tester.pump();
    expect(find.text('Masuk ke akun Anda'), findsOneWidget);
  });
}

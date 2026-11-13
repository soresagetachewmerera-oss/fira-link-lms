import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:eduex_app/screens/document_scanner/fira_document_scanner_screen.dart';

void main() {
  testWidgets('scanner starts with one real-document action and no demo data',
      (tester) async {
    await tester.binding.setSurfaceSize(const Size(430, 932));
    await tester.pumpWidget(
      const MaterialApp(home: FiraDocumentScannerScreen()),
    );

    expect(find.text('Document Scanner'), findsOneWidget);
    expect(find.text('Scan Document / Upload Document'), findsOneWidget);
    expect(find.textContaining('Fira AI extracts the real content'), findsOneWidget);
    expect(find.textContaining('Yirgacheffe'), findsNothing);
    expect(find.textContaining('Sample'), findsNothing);
    expect(find.textContaining('Demo'), findsNothing);
  });

  testWidgets('single action offers camera scan and document upload',
      (tester) async {
    await tester.pumpWidget(
      const MaterialApp(home: FiraDocumentScannerScreen()),
    );
    await tester.tap(find.text('Scan Document / Upload Document'));
    await tester.pumpAndSettle();

    expect(find.text('Scan with camera'), findsOneWidget);
    expect(find.text('Upload document'), findsOneWidget);
  });
}

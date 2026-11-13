import 'dart:typed_data';

import 'package:eduex_app/l10n/app_localizations.dart';
import 'package:eduex_app/l10n/lang_am.dart';
import 'package:eduex_app/l10n/lang_en.dart';
import 'package:eduex_app/services/api_exception.dart';
import 'package:eduex_app/services/upload_file.dart';
import 'package:eduex_app/theme/app_theme.dart';
import 'package:eduex_app/widgets/fira_error_state.dart';
import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;

/// Slice 0: no fake success, real error states, real files.
void main() {
  const slice0Keys = [
    'actionFailed',
    'bagsCount',
    'cameraOnlyEvidence',
    'connectionProblemBody',
    'connectionProblemTitle',
    'fileRejectedEmpty',
    'fileRejectedPlaceholder',
    'fileRejectedTooLarge',
    'fileRejectedTooSmall',
    'fileRejectedType',
    'fileRejectedUnreadable',
    'loadFailedBody',
    'loadFailedTitle',
    'milestoneConfirmationRequired',
    'noQuotesYet',
    'notAllowed',
    'proofSubmittedPendingBank',
    'proofSubmittedPendingReview',
    'sessionExpired',
  ];

  group('Localization', () {
    test('every slice 0 key exists in English and Amharic, and Amharic is translated', () {
      for (final key in slice0Keys) {
        expect(kLangEn[key], isNotNull, reason: 'missing English key $key');
        expect(kLangAm[key], isNotNull, reason: 'missing Amharic key $key');
        expect(kLangAm[key], isNot(equals(kLangEn[key])), reason: 'Amharic not translated for $key');
      }
    });
  });

  group('ApiException', () {
    test('parses code and reason from a FILE_REJECTED response', () {
      final res = http.Response(
        '{"success":false,"code":"FILE_REJECTED","reason":"placeholder","message":"x"}',
        422,
      );
      final e = ApiException.fromResponse(res);
      expect(e.statusCode, 422);
      expect(e.isFileRejected, isTrue);
      expect(e.reason, 'placeholder');
      expect(e.isNetwork, isFalse);
    });

    test('survives a non-JSON error body', () {
      final e = ApiException.fromResponse(http.Response('<html>502</html>', 502));
      expect(e.statusCode, 502);
      expect(e.code, isNull);
    });

    test('guardedRequest turns a transport failure into a network error, never a response', () async {
      expect(
        () => guardedRequest(() async => throw http.ClientException('offline')),
        throwsA(isA<ApiException>().having((e) => e.isNetwork, 'isNetwork', isTrue)),
      );
    });

    test('requireSuccess passes 2xx and throws on errors', () {
      expect(requireSuccess(http.Response('{}', 200)).statusCode, 200);
      expect(() => requireSuccess(http.Response('{}', 500)), throwsA(isA<ApiException>()));
      expect(() => requireSuccess(http.Response('{}', 403)), throwsA(isA<ApiException>()));
    });
  });

  group('UploadFile', () {
    test('rejects empty and oversized files before upload', () {
      expect(UploadFile(name: 'a.pdf', bytes: Uint8List(0)).localRejection, 'empty');
      expect(UploadFile(name: 'a.pdf', bytes: Uint8List(UploadFile.maxBytes + 1)).localRejection, 'too_large');
      expect(UploadFile(name: 'a.pdf', bytes: Uint8List(2048)).localRejection, isNull);
    });

    test('builds a multipart part from bytes, not from a file path', () {
      final part = UploadFile(name: 'slip.png', bytes: Uint8List.fromList([1, 2, 3])).toMultipart('document');
      expect(part.field, 'document');
      expect(part.filename, 'slip.png');
      expect(part.length, 3);
    });
  });

  group('FiraErrorState', () {
    Widget host(Widget child, {Locale locale = const Locale('en')}) => MaterialApp(
          theme: AppTheme.theme,
          locale: locale,
          supportedLocales: AppLocalizations.supportedLocales,
          localizationsDelegates: const [
            AppLocalizations.delegate,
            GlobalMaterialLocalizations.delegate,
            GlobalWidgetsLocalizations.delegate,
            GlobalCupertinoLocalizations.delegate,
          ],
          home: Scaffold(body: child),
        );

    testWidgets('network error shows connection message and retry works', (tester) async {
      var retried = 0;
      await tester.pumpWidget(host(FiraErrorState(
        error: const ApiException.network(),
        onRetry: () => retried++,
      )));
      await tester.pumpAndSettle();

      expect(find.text(kLangEn['connectionProblemTitle']!), findsOneWidget);
      expect(find.text(kLangEn['connectionProblemBody']!), findsOneWidget);

      await tester.tap(find.text(kLangEn['retry']!));
      expect(retried, 1);
    });

    testWidgets('server error shows generic load failure in Amharic', (tester) async {
      await tester.pumpWidget(host(
        FiraErrorState(error: const ApiException(statusCode: 500), onRetry: () {}),
        locale: const Locale('am'),
      ));
      await tester.pumpAndSettle();

      expect(find.text(kLangAm['loadFailedTitle']!), findsOneWidget);
      expect(find.text(kLangAm['loadFailedBody']!), findsOneWidget);
    });

    testWidgets('file rejection and confirmation messages are localized', (tester) async {
      late BuildContext ctx;
      await tester.pumpWidget(host(Builder(builder: (c) {
        ctx = c;
        return const SizedBox();
      })));
      await tester.pumpAndSettle();

      expect(
        FiraErrorText.message(ctx, const ApiException(statusCode: 422, code: 'FILE_REJECTED', reason: 'placeholder'), isAction: true),
        kLangEn['fileRejectedPlaceholder'],
      );
      expect(
        FiraErrorText.message(ctx, const ApiException(statusCode: 403, code: 'CONFIRMATION_REQUIRED'), isAction: true),
        kLangEn['milestoneConfirmationRequired'],
      );
      expect(FiraErrorText.message(ctx, Exception('boom'), isAction: true), kLangEn['actionFailed']);
    });
  });
}

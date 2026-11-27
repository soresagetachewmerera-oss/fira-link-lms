import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:eduex_app/models/fira_voice_models.dart';
import 'package:eduex_app/services/fira_voice_service.dart';
import 'package:eduex_app/widgets/fira_voice_orb.dart';
import 'package:eduex_app/theme/fira_colors.dart';

void main() {
  setUp(() {
    SharedPreferences.setMockInitialValues({});
  });
  group('FiraVoiceModels Tests', () {
    test('VoiceSessionCredentials correctly parses JSON', () {
      final json = {
        'session_id': 'vses_sample123',
        'token': 'ephemeral_token_abc',
        'model': 'models/gemini-2.0-flash',
        'language': 'am',
        'limits': {
          'max_session_seconds': 180,
          'remaining_today_seconds': 250,
        },
      };

      final creds = VoiceSessionCredentials.fromJson(json);

      expect(creds.sessionId, 'vses_sample123');
      expect(creds.token, 'ephemeral_token_abc');
      expect(creds.model, 'models/gemini-2.0-flash');
      expect(creds.language, 'am');
      expect(creds.maxSessionSeconds, 180);
      expect(creds.remainingTodaySeconds, 250);
    });

    test('VoiceDisplayCard correctly parses JSON', () {
      final json = {
        'type': 'market_snapshot',
        'title': 'Ethiopian Coffee Benchmark',
        'data': {
          'as_of': '2026-11-28T00:00:00Z',
          'rows': [
            {'symbol': 'ICO_COMPOSITE', 'value': 285.50, 'unit': 'US cents/lb'},
          ],
        },
      };

      final card = VoiceDisplayCard.fromJson(json);

      expect(card.type, 'market_snapshot');
      expect(card.title, 'Ethiopian Coffee Benchmark');
      expect(card.data['rows'], isNotEmpty);
    });

    test('FiraVoiceQuota correctly parses JSON', () {
      final json = {
        'seconds_used': 45,
        'seconds_limit': 300,
        'remaining_today_seconds': 255,
        'sessions_count': 2,
        'allowed_languages': ['en', 'am'],
      };

      final quota = FiraVoiceQuota.fromJson(json);

      expect(quota.secondsUsed, 45);
      expect(quota.secondsLimit, 300);
      expect(quota.remainingTodaySeconds, 255);
      expect(quota.sessionsCount, 2);
      expect(quota.allowedLanguages, contains('am'));
      expect(quota.allowedLanguages, isNot(contains('om')));
    });
  });

  group('FiraVoiceService Tests', () {
    test('startSession immediately triggers Afan Oromo fallback if language is om', () async {
      final service = FiraVoiceService();
      final result = await service.startSession(language: 'om');

      expect(result['success'], false);
      expect(result['error_code'], 'afan_oromo_fallback');
    });
  });

  group('FiraVoiceOrb Widget Tests', () {
    testWidgets('FiraVoiceOrb renders properly in listening state', (tester) async {
      await tester.pumpWidget(
        MaterialApp(
          theme: ThemeData(
            extensions: const [FiraColors.light],
          ),
          home: const Scaffold(
            body: Center(
              child: FiraVoiceOrb(
                state: VoiceSessionState.listening,
                volume: 0.5,
              ),
            ),
          ),
        ),
      );

      expect(find.byType(FiraVoiceOrb), findsOneWidget);
      expect(find.byIcon(Icons.mic), findsOneWidget);
    });

    testWidgets('FiraVoiceOrb renders properly in speaking state', (tester) async {
      await tester.pumpWidget(
        MaterialApp(
          theme: ThemeData(
            extensions: const [FiraColors.light],
          ),
          home: const Scaffold(
            body: Center(
              child: FiraVoiceOrb(
                state: VoiceSessionState.speaking,
                volume: 0.8,
              ),
            ),
          ),
        ),
      );

      expect(find.byType(FiraVoiceOrb), findsOneWidget);
      expect(find.byIcon(Icons.volume_up), findsOneWidget);
    });
  });
}

import 'dart:convert';
import 'dart:io';

import 'package:eduex_app/l10n/app_localizations.dart';
import 'package:eduex_app/l10n/lang_en.dart';
import 'package:eduex_app/providers/auth_provider.dart';
import 'package:eduex_app/providers/settings_provider.dart';
import 'package:eduex_app/providers/workspace_provider.dart';
import 'package:eduex_app/router/app_router.dart';
import 'package:eduex_app/screens/profile/profile_screen.dart';
import 'package:eduex_app/screens/profile/settings_screen.dart';
import 'package:eduex_app/services/api_exception.dart';
import 'package:eduex_app/services/auth_service.dart';
import 'package:eduex_app/services/auth_session.dart';
import 'package:eduex_app/services/fira_http_client.dart';
import 'package:eduex_app/services/payment_service.dart';
import 'package:eduex_app/theme/app_theme.dart';
import 'package:eduex_app/widgets/auth/auth_gate.dart';
import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Auth gating 2026-11-20, Phase 2: one auth state, a default-deny route guard, guest Settings and
/// Profile, sign-in before protected actions, 401 wipes the session, no token for guests.

const _api = 'https://api.test/api';

/// An AuthProvider pinned to one state (no network).
class _FixedAuth extends AuthProvider {
  _FixedAuth(this._fixed);

  final AuthStatus _fixed;

  @override
  AuthStatus get status => _fixed;

  @override
  bool get isAuthenticated => _fixed == AuthStatus.signedIn;

  @override
  Future<void> loadUser() async {}
}

Widget _app(Widget home, AuthProvider auth) => MultiProvider(
      providers: [
        ChangeNotifierProvider<AuthProvider>.value(value: auth),
        ChangeNotifierProvider<SettingsProvider>(create: (_) => SettingsProvider()),
        ChangeNotifierProvider<WorkspaceProvider>(create: (_) => WorkspaceProvider()),
      ],
      child: MaterialApp(
        theme: AppTheme.theme,
        supportedLocales: AppLocalizations.supportedLocales,
        localizationsDelegates: const [
          AppLocalizations.delegate,
          GlobalMaterialLocalizations.delegate,
          GlobalWidgetsLocalizations.delegate,
          GlobalCupertinoLocalizations.delegate,
        ],
        home: home,
      ),
    );

Future<void> _settle(WidgetTester tester) async {
  for (var i = 0; i < 4; i++) {
    await tester.runAsync(() => Future<void>.delayed(const Duration(milliseconds: 20)));
    await tester.pump();
  }
}

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  setUp(() {
    SharedPreferences.setMockInitialValues({});
    FlutterSecureStorage.setMockInitialValues({});
    AuthSession.setStatus(AuthStatus.unknown);
    AuthSession.onTokenRejected = null;
  });

  group('AuthSession and FiraHttpClient', () {
    test('a token goes out only for a signed-in session; before that only the session check and sign-out', () {
      final user = Uri.parse('$_api/user');
      final logout = Uri.parse('$_api/logout');
      final profile = Uri.parse('$_api/user/profile');

      AuthSession.setStatus(AuthStatus.guest);
      expect([user, logout, profile].map(AuthSession.mayUseToken), [false, false, false]);

      AuthSession.setStatus(AuthStatus.unknown);
      expect([user, logout, profile].map(AuthSession.mayUseToken), [true, true, false]);

      AuthSession.setStatus(AuthStatus.offline);
      expect(AuthSession.mayUseToken(profile), isFalse);

      AuthSession.setStatus(AuthStatus.signedIn);
      expect(AuthSession.mayUseToken(profile), isTrue);
    });

    test('a guest never sends a leftover token (the server cannot change anything for them)', () async {
      final seen = <String?>[];
      final client = FiraHttpClient(MockClient((r) async {
        seen.add(r.headers['Authorization']);
        return http.Response('{"success":false,"message":"Unauthenticated."}', 401);
      }), apiBaseUrl: _api);
      var rejected = 0;
      AuthSession.onTokenRejected = () => rejected++;
      AuthSession.setStatus(AuthStatus.guest);

      await client.post(Uri.parse('$_api/user/profile'), headers: {'Authorization': 'Bearer old-token'}, body: '{}');
      await client.post(Uri.parse('$_api/user/password'), headers: {'authorization': 'Bearer old-token'}, body: '{}');

      expect(seen, [null, null]);
      expect(rejected, 0, reason: 'A guest call without a token is not a rejected session.');
    });

    test('a 401 to a request that carried the token ends the session; other hosts are untouched', () async {
      final client = FiraHttpClient(MockClient((r) async => http.Response('', 401)), apiBaseUrl: _api);
      var rejected = 0;
      AuthSession.onTokenRejected = () => rejected++;
      AuthSession.setStatus(AuthStatus.signedIn);

      await client.get(Uri.parse('$_api/user/organizations'), headers: {'Authorization': 'Bearer t'});
      expect(rejected, 1);

      await client.get(Uri.parse('https://chapa.example/checkout'), headers: {'Authorization': 'Bearer other'});
      expect(rejected, 1);
    });
  });

  group('AuthProvider', () {
    Future<AuthProvider> loadWith(int status, {String? body}) async {
      FlutterSecureStorage.setMockInitialValues({'auth_token': '1|secret'});
      SharedPreferences.setMockInitialValues({'wishlist_items': '[]', 'recent_searches': <String>['guji'], 'language_code': 'am'});
      late AuthProvider auth;
      await http.runWithClient(() async {
        auth = AuthProvider();
        await auth.loadUser();
      }, () => FiraHttpClient(MockClient((r) async {
            if (r.url.path.endsWith('/api/user')) {
              return http.Response(body ?? jsonEncode({'id': 7, 'name': 'Abebe', 'email': 'a@example.test', 'role': 'student'}), status);
            }
            return http.Response('{}', 200);
          })));
      return auth;
    }

    test('no stored token: guest', () async {
      final auth = AuthProvider();
      await auth.loadUser();
      expect(auth.status, AuthStatus.guest);
      expect(auth.isAuthenticated, isFalse);
    });

    test('server accepts the token: signed in', () async {
      final auth = await loadWith(200);
      expect(auth.status, AuthStatus.signedIn);
      expect(auth.user?.name, 'Abebe');
    });

    test('server rejects the token: expired, token and personal data wiped, language kept', () async {
      final auth = await loadWith(401, body: '{"success":false,"message":"Unauthenticated."}');
      expect(auth.status, AuthStatus.expired);
      expect(auth.isGuest, isTrue);
      expect(await const FlutterSecureStorage().read(key: 'auth_token'), isNull);
      final prefs = await SharedPreferences.getInstance();
      expect(prefs.getString('wishlist_items'), isNull);
      expect(prefs.getStringList('recent_searches'), isNull);
      expect(prefs.getString('language_code'), 'am');
    });

    test('server unreachable or failing: the session is kept but never shown as a guest', () async {
      for (final code in [500, 429, 403]) {
        final auth = await loadWith(code, body: '{}');
        expect(auth.status, AuthStatus.offline, reason: '$code');
        expect(auth.isGuest, isFalse);
        expect(auth.isAuthenticated, isFalse);
        expect(await const FlutterSecureStorage().read(key: 'auth_token'), '1|secret', reason: '$code');
      }
    });

    test('a 401 later in the session (another device changed the password) signs out and wipes', () async {
      final auth = await loadWith(200);
      final epoch = auth.sessionEpoch;
      expect(auth.status, AuthStatus.signedIn);

      auth.handleTokenRejected();
      await Future<void>.delayed(Duration.zero);
      await Future<void>.delayed(Duration.zero);

      expect(auth.status, AuthStatus.expired);
      expect(auth.user, isNull);
      expect(auth.sessionEpoch, greaterThan(epoch), reason: 'Providers are told to forget the previous person.');
      expect(await const FlutterSecureStorage().read(key: 'auth_token'), isNull);
    });

    test('a plaintext token from an old version moves to secure storage and leaves SharedPreferences', () async {
      SharedPreferences.setMockInitialValues({'auth_token': 'legacy|token'});
      expect(await AuthService().getToken(), 'legacy|token');
      final prefs = await SharedPreferences.getInstance();
      expect(prefs.getString('auth_token'), isNull);
      expect(await const FlutterSecureStorage().read(key: 'auth_token'), 'legacy|token');
    });
  });

  group('route guard (default deny)', () {
    test('every named route is either on the public list or wrapped in RequireSignIn', () {
      final source = File('lib/router/app_router.dart').readAsStringSync();
      final names = RegExp(r"static const String \w+ = '([^']+)';").allMatches(source).map((m) => m.group(1)!).toList();
      expect(names.length, greaterThan(40));

      final protected = names.where((n) => !AppRouter.isPublic(n)).toList();
      for (final n in ['/settings', '/home', '/marketplace', '/verify-seller', '/onboarding', '/auth']) {
        expect(AppRouter.isPublic(n), isTrue, reason: n);
      }
      for (final n in ['/edit-profile', '/change-password', '/notification-settings', '/marketplace/seller', '/marketplace/verification',
        '/marketplace/rfqs', '/marketplace/rfqs/create', '/deal-room', '/marketplace/samples/tracking', '/messages', '/payments',
        '/profile/workspaces/create', '/search', '/onboarding/intent', '/study-timer']) {
        expect(protected, contains(n), reason: '$n must need sign-in');
      }
    });

    testWidgets('a guest opening a protected route (deep link, notification) sees sign-in, never the screen', (tester) async {
      Widget? built;
      await tester.pumpWidget(_app(Builder(builder: (context) {
        final route = AppRouter.onGenerateRoute(const RouteSettings(name: AppRouter.editProfile)) as MaterialPageRoute;
        built = route.builder(context);
        return built!;
      }), _FixedAuth(AuthStatus.guest)));
      await tester.pump();

      expect(built, isA<RequireSignIn>());
      expect(find.byType(SignInRequiredView), findsOneWidget);
      expect(find.text(kLangEn['signInToContinueTitle']!), findsOneWidget);
      expect(find.byKey(const Key('edit-profile-email')), findsNothing);
    });

    testWidgets('a stored session that could not be checked offers Retry, not guest mode', (tester) async {
      await tester.pumpWidget(_app(const RequireSignIn(child: Text('secret screen')), _FixedAuth(AuthStatus.offline)));
      await tester.pump();
      expect(find.byKey(const Key('session-check-retry')), findsOneWidget);
      expect(find.text('secret screen'), findsNothing);
    });

    testWidgets('signed in: the screen itself', (tester) async {
      await tester.pumpWidget(_app(const RequireSignIn(child: Text('secret screen')), _FixedAuth(AuthStatus.signedIn)));
      await tester.pump();
      expect(find.text('secret screen'), findsOneWidget);
    });

    test('notification ids are parsed; a missing or bad id never opens deal room 1', () {
      final source = File('lib/router/app_router.dart').readAsStringSync();
      expect(source.contains('arguments is int ? arguments : 1'), isFalse);
      final push = File('lib/services/push_notification_service.dart').readAsStringSync();
      expect(push.contains("pushNamed(AppRouter.sampleTracking);"), isFalse);
    });

    testWidgets('a deal-room link without a valid id shows "not available"', (tester) async {
      await tester.pumpWidget(_app(Builder(builder: (context) {
        final route = AppRouter.onGenerateRoute(const RouteSettings(name: AppRouter.dealRoom, arguments: 'abc')) as MaterialPageRoute;
        return route.builder(context);
      }), _FixedAuth(AuthStatus.signedIn)));
      await tester.pump();
      expect(find.byType(UnavailableScreen), findsOneWidget);
    });
  });

  group('guest screens', () {
    testWidgets('guest Settings: language, about, terms and sign in only; no account section', (tester) async {
      await http.runWithClient(() async {
        await tester.pumpWidget(_app(const SettingsScreen(), _FixedAuth(AuthStatus.guest)));
        await _settle(tester);
      }, () => MockClient((r) async => http.Response('{"success":true,"data":[]}', 200)));

      expect(find.byKey(const Key('settings-guest-sign-in')), findsOneWidget);
      expect(find.text(kLangEn['language']!), findsWidgets);
      expect(find.text(kLangEn['editProfile']!), findsNothing);
      expect(find.text(kLangEn['changePassword']!), findsNothing);
      expect(find.text(kLangEn['deleteAccount']!), findsNothing);
    });

    testWidgets('signed-in Settings keeps the account section', (tester) async {
      await http.runWithClient(() async {
        await tester.pumpWidget(_app(const SettingsScreen(), _FixedAuth(AuthStatus.signedIn)));
        await _settle(tester);
      }, () => MockClient((r) async => http.Response('{"success":true,"data":[]}', 200)));

      expect(find.byKey(const Key('settings-guest-sign-in')), findsNothing);
      expect(find.text(kLangEn['editProfile']!), findsOneWidget);
    });

    testWidgets('guest Profile tab: sign in and settings, no account, business or activity rows', (tester) async {
      await tester.pumpWidget(_app(const ProfileScreen(), _FixedAuth(AuthStatus.guest)));
      await tester.pump();
      expect(find.byKey(const Key('profile-guest-view')), findsOneWidget);
      expect(find.byKey(const Key('profile-sign-in')), findsOneWidget);
      expect(find.text('Guest User'), findsNothing);
      expect(find.text(kLangEn['dealsTitle']!), findsNothing);
      expect(find.text(kLangEn['payments']!), findsNothing);
    });

    testWidgets('a protected action asks for sign-in BEFORE any request; "Not now" sends nothing', (tester) async {
      final requests = <http.BaseRequest>[];
      bool? result;
      await http.runWithClient(() async {
        await tester.pumpWidget(_app(Builder(builder: (context) {
          return Scaffold(
            body: Center(
              child: FilledButton(
                key: const Key('save-lot'),
                onPressed: () async {
                  result = await ensureSignedIn(context);
                  if (result == true) await http.post(Uri.parse('$_api/marketplace/listings/1/save'));
                },
                child: const Text('Save'),
              ),
            ),
          );
        }), _FixedAuth(AuthStatus.guest)));
        await tester.pumpAndSettle();
        await tester.tap(find.byKey(const Key('save-lot')));
        await tester.pumpAndSettle();
        expect(find.byType(SignInPromptSheet), findsOneWidget);
        await tester.tap(find.byKey(const Key('signin-prompt-not-now')));
        await tester.pumpAndSettle();
      }, () => MockClient((r) async {
            requests.add(r);
            return http.Response('{}', 200);
          }));

      expect(result, isFalse);
      expect(requests, isEmpty);
    });

    testWidgets('"Sign in" opens phone sign-in, which returns to the screen that asked', (tester) async {
      await tester.pumpWidget(_app(Builder(builder: (context) {
        return Scaffold(body: FilledButton(key: const Key('go'), onPressed: () => ensureSignedIn(context), child: const Text('Go')));
      }), _FixedAuth(AuthStatus.guest)));
      await tester.pumpAndSettle();
      await tester.tap(find.byKey(const Key('go')));
      await tester.pumpAndSettle();
      await tester.tap(find.byKey(const Key('signin-prompt-continue')));
      await tester.pumpAndSettle();
      expect(SignInResume.pending, isTrue);
      // Going back without signing in finishes the gate with "not signed in".
      await tester.pageBack();
      await tester.pumpAndSettle();
      expect(SignInResume.pending, isFalse);
      expect(find.byKey(const Key('go')), findsOneWidget);
    });
  });

  group('no fake success', () {
    test('payment history fails visibly instead of looking empty', () async {
      FlutterSecureStorage.setMockInitialValues({'auth_token': '1|t'});
      AuthSession.setStatus(AuthStatus.signedIn);
      await http.runWithClient(() async {
        await expectLater(PaymentService().getMyPayments(), throwsA(isA<ApiException>()));
      }, () => MockClient((r) async => http.Response('{"message":"Server Error"}', 500)));
    });

    test('no service invents a success answer', () {
      final offenders = <String>[];
      for (final f in Directory('lib/services').listSync().whereType<File>()) {
        final s = f.readAsStringSync();
        if (s.contains('_mockResponse') || RegExp(r'http\.Response\(\s*[\x27"]\{\s*[\x27"]success[\x27"]\s*:\s*true').hasMatch(s)) {
          offenders.add(f.path);
        }
      }
      expect(offenders, isEmpty);
    });
  });

  group('verification badges', () {
    test('the app keeps no legacy verified flags', () {
      for (final path in ['lib/models/organization_model.dart', 'lib/models/user_model.dart', 'lib/providers/workspace_provider.dart']) {
        final s = File(path).readAsStringSync();
        expect(s.contains('is_verified'), isFalse, reason: path);
        expect(s.contains('verified_roles'), isFalse, reason: path);
        expect(s.contains('isVerifiedExporter'), isFalse, reason: path);
      }
    });

    test('the lot seller card shows the verified tick only for a server tier', () {
      final s = File('lib/screens/marketplace/coffee_listing_detail_screen.dart').readAsStringSync();
      final i = s.indexOf("Key('detail-verified-icon')");
      expect(i, greaterThan(0));
      expect(s.substring(i - 200, i).contains('if (tier != null && tier > 0)'), isTrue);
      expect(s.contains("Text('ECTA License: \$license'"), isFalse);
    });

    test('a paid plan never looks like verification', () {
      final s = File('lib/screens/marketplace/export_docs_studio_screen.dart').readAsStringSync();
      expect(s.contains('Studio Pro Active'), isFalse);
      final i = s.indexOf("Key('export-docs-pro-banner')");
      expect(s.substring(i, i + 900).contains('Icons.verified'), isFalse);
    });
  });
}

import 'package:eduex_app/l10n/app_localizations.dart';
import 'package:eduex_app/l10n/lang_en.dart';
import 'package:eduex_app/models/organization_model.dart';
import 'package:eduex_app/providers/workspace_provider.dart';
import 'package:eduex_app/screens/onboarding/business_workspace_setup_screen.dart';
import 'package:eduex_app/screens/onboarding/intent_selection_screen.dart';
import 'package:eduex_app/screens/profile/team_seats_screen.dart';
import 'package:eduex_app/services/api_exception.dart';
import 'package:eduex_app/services/organization_service.dart';
import 'package:eduex_app/theme/app_theme.dart';
import 'package:eduex_app/theme/fira_colors.dart';
import 'package:eduex_app/widgets/workspace_status_pill.dart';
import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

const _options = WorkspaceOptions(
  entityTypes: [
    EntityTypeOption(value: 'private_limited_company', label: 'Private limited company (PLC)', needsManualReview: false),
    EntityTypeOption(value: 'other', label: 'Other', needsManualReview: true),
  ],
  businessRoles: ['exporter', 'supplier', 'broker', 'buyer'],
  countries: ['Ethiopia', 'Kenya'],
  defaultCountry: 'Ethiopia',
  maxOwnedBusinesses: 3,
);

class _FakeOrgService extends OrganizationService {
  _FakeOrgService({this.roster, this.needsBusiness = false, this.createError});

  final TeamRoster? roster;
  final bool needsBusiness;
  final ApiException? createError;
  final List<String> calls = [];

  @override
  Future<WorkspaceOptions> getOptions() async => _options;

  @override
  Future<WorkspaceSnapshot> getUserOrganizations() async => const WorkspaceSnapshot(
        current: null,
        organizations: [],
        intents: [],
        maxOwnedBusinesses: 3,
        ownedBusinesses: 0,
      );

  @override
  Future<bool> saveOnboardingIntents(List<String> intents) async {
    calls.add('intents:${intents.join(',')}');
    return needsBusiness;
  }

  @override
  Future<OrganizationModel> createOrganization({
    required String name,
    required String businessRole,
    required String entityType,
    required String country,
    String? tinNumber,
    String? billingEmail,
  }) async {
    calls.add('create:$name:$businessRole:$entityType:$country:${tinNumber ?? ''}');
    if (createError != null) throw createError!;
    return OrganizationModel(id: 9, name: name, slug: 'x', businessType: businessRole);
  }

  @override
  Future<TeamRoster> getMembers(int organizationId) async => roster!;

  @override
  Future<void> removeMember(int organizationId, int userId) async => calls.add('remove:$userId');
}

Widget _host(Widget child, _FakeOrgService service) => ChangeNotifierProvider(
      create: (_) => WorkspaceProvider(service: service),
      child: MaterialApp(
        theme: AppTheme.theme,
        supportedLocales: AppLocalizations.supportedLocales,
        localizationsDelegates: const [
          AppLocalizations.delegate,
          GlobalMaterialLocalizations.delegate,
          GlobalWidgetsLocalizations.delegate,
          GlobalCupertinoLocalizations.delegate,
        ],
        home: child,
      ),
    );

void _tall(WidgetTester tester) {
  tester.view.physicalSize = const Size(1170, 5000);
  tester.view.devicePixelRatio = 3;
  addTearDown(tester.view.reset);
}

void main() {
  setUp(() => SharedPreferences.setMockInitialValues({}));

  group('model', () {
    test('workspace status and membership come from the server answer', () {
      final org = OrganizationModel.fromJson({
        'id': 4,
        'name': 'Abebe Coffee',
        'slug': 'abebe',
        'business_role': 'supplier',
        'entity_type': 'cooperative',
        'membership_role': 'staff',
        'status': 'in_review',
        'visible_to_buyers': false,
        'needs_manual_review': true,
      });
      expect(org.status, WorkspaceStatus.inReview);
      expect(org.businessType, 'supplier');
      expect(org.role, 'staff');
      expect(org.isOwner, isFalse);
      expect(org.visibleToBuyers, isFalse);
      expect(org.needsManualReview, isTrue);
      expect(WorkspaceStatus.fromApi('unknown'), WorkspaceStatus.notStarted);
    });

    test('member rows only allow removal when the server says so', () {
      final roster = TeamRoster.fromJson({
        'viewer_is_owner': false,
        'can_add_members': false,
        'members': [
          {'id': 1, 'user_id': 1, 'name': 'Owner', 'role': 'owner', 'can_remove': false},
          {'id': 2, 'user_id': 2, 'name': 'Staff', 'role': 'staff', 'phone_masked': '+2519*****12'},
        ],
      });
      expect(roster.members.every((m) => !m.canRemove), isTrue);
      expect(roster.members[1].phone, '+2519*****12');
    });
  });

  testWidgets('in review pill is purple on lavender', (tester) async {
    await tester.pumpWidget(_host(const Scaffold(body: WorkspaceStatusPill(status: WorkspaceStatus.inReview)), _FakeOrgService()));
    await tester.pumpAndSettle();

    final context = tester.element(find.byType(WorkspaceStatusPill));
    final box = tester.widget<Container>(
      find.descendant(of: find.byType(WorkspaceStatusPill), matching: find.byType(Container)),
    );
    expect((box.decoration as BoxDecoration).color, context.fira.brandTint);
    expect(tester.widget<Text>(find.text(kLangEn['workspaceStatusInReview']!)).style?.color, context.fira.pending);
  });

  testWidgets('intent is multi-select; sell or buy opens business setup', (tester) async {
    _tall(tester);
    final service = _FakeOrgService(needsBusiness: true);
    await tester.pumpWidget(_host(const IntentSelectionScreen(), service));
    await tester.pumpAndSettle();

    final continueButton = find.widgetWithText(ElevatedButton, kLangEn['intentContinue']!);
    expect(tester.widget<ElevatedButton>(continueButton).onPressed, isNull);

    await tester.tap(find.text(kLangEn['intentLearnTitle']!));
    await tester.tap(find.text(kLangEn['intentSellTitle']!));
    await tester.pump();
    await tester.tap(continueButton);
    await tester.pumpAndSettle();

    expect(service.calls, ['intents:export,learn']);
    expect(find.text(kLangEn['businessSetupTitle']!), findsOneWidget);
  });

  testWidgets('business setup checks the Ethiopian TIN and flags Other for review', (tester) async {
    _tall(tester);
    final service = _FakeOrgService();
    await tester.pumpWidget(_host(BusinessWorkspaceSetupScreen(afterCreate: (_) => const Text('verification-next')), service));
    await tester.pumpAndSettle();

    await tester.enterText(find.byType(TextFormField).at(0), 'Abebe Coffee');
    await tester.tap(find.byType(DropdownButtonFormField<String>).first);
    await tester.pumpAndSettle();
    await tester.tap(find.text(kLangEn['entityOther']!).last);
    await tester.pumpAndSettle();
    expect(find.text(kLangEn['entityOtherNotice']!), findsOneWidget);

    await tester.enterText(find.byType(TextFormField).at(1), '12345');
    await tester.tap(find.text(kLangEn['businessCreate']!));
    await tester.pumpAndSettle();
    expect(find.text(kLangEn['businessTinInvalidEthiopia']!), findsOneWidget);
    expect(service.calls, isEmpty);

    await tester.enterText(find.byType(TextFormField).at(1), '0012345678');
    await tester.tap(find.text(kLangEn['businessRoleSupplier']!));
    await tester.tap(find.text(kLangEn['businessCreate']!));
    await tester.pumpAndSettle();
    expect(service.calls, ['create:Abebe Coffee:supplier:other:Ethiopia:0012345678']);
    // Created from the profile: verification of the new business opens next.
    expect(find.text('verification-next'), findsOneWidget);
  });

  testWidgets('the business limit shows "ask support" and disables create', (tester) async {
    _tall(tester);
    final service = _FakeOrgService(
      createError: const ApiException(statusCode: 422, code: 'WORKSPACE_LIMIT'),
    );
    await tester.pumpWidget(_host(const BusinessWorkspaceSetupScreen(), service));
    await tester.pumpAndSettle();

    await tester.enterText(find.byType(TextFormField).at(0), 'Fourth Business');
    await tester.tap(find.byType(DropdownButtonFormField<String>).first);
    await tester.pumpAndSettle();
    await tester.tap(find.text(kLangEn['entityPrivateLimitedCompany']!).last);
    await tester.pumpAndSettle();
    await tester.tap(find.text(kLangEn['businessCreate']!));
    await tester.pumpAndSettle();

    expect(find.text(kLangEn['businessLimitReached']!.replaceAll('{max}', '3')), findsOneWidget);
    expect(tester.widget<ElevatedButton>(find.widgetWithText(ElevatedButton, kLangEn['businessCreate']!)).onPressed, isNull);
  });

  final org = OrganizationModel(id: 3, name: 'Abebe Coffee', slug: 'a', businessType: 'exporter', role: 'staff');

  testWidgets('team is read-only for members', (tester) async {
    final service = _FakeOrgService(
      roster: TeamRoster.fromJson({
        'viewer_is_owner': false,
        'can_add_members': false,
        'members': [
          {'id': 1, 'user_id': 1, 'name': 'Owner Person', 'role': 'owner', 'can_remove': false},
          {'id': 2, 'user_id': 2, 'name': 'Staff Person', 'role': 'staff', 'can_remove': false},
        ],
      }),
    );
    await tester.pumpWidget(_host(TeamSeatsScreen(organization: org), service));
    await tester.pumpAndSettle();

    expect(find.text('Staff Person'), findsOneWidget);
    expect(find.text(kLangEn['teamReadOnlyNotice']!), findsOneWidget);
    expect(find.byIcon(Icons.person_remove_outlined), findsNothing);
    expect(find.byIcon(Icons.person_add_outlined), findsNothing);
  });

  testWidgets('owner can remove a member after confirming', (tester) async {
    final service = _FakeOrgService(
      roster: TeamRoster.fromJson({
        'viewer_is_owner': true,
        'can_add_members': false,
        'members': [
          {'id': 1, 'user_id': 1, 'name': 'Owner Person', 'role': 'owner', 'can_remove': false},
          {'id': 2, 'user_id': 22, 'name': 'Staff Person', 'role': 'staff', 'can_remove': true},
        ],
      }),
    );
    await tester.pumpWidget(_host(TeamSeatsScreen(organization: org), service));
    await tester.pumpAndSettle();

    expect(find.byIcon(Icons.person_remove_outlined), findsOneWidget);
    expect(find.byIcon(Icons.person_add_outlined), findsNothing);
    await tester.tap(find.byIcon(Icons.person_remove_outlined));
    await tester.pumpAndSettle();
    await tester.tap(find.text(kLangEn['remove']!).last);
    await tester.pumpAndSettle();

    expect(service.calls, ['remove:22']);
  });
}

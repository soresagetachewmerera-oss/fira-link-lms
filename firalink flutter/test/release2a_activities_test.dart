import 'package:eduex_app/l10n/app_localizations.dart';
import 'package:eduex_app/l10n/lang_en.dart';
import 'package:eduex_app/models/organization_model.dart';
import 'package:eduex_app/providers/workspace_provider.dart';
import 'package:eduex_app/screens/onboarding/business_workspace_setup_screen.dart';
import 'package:eduex_app/screens/profile/business_activities_screen.dart';
import 'package:eduex_app/services/organization_service.dart';
import 'package:eduex_app/theme/app_theme.dart';
import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

const _farmer = BusinessCategoryOption(
  code: 'farmer',
  labelEn: 'Farmer (Alami)',
  labelAm: 'አልሚ (አርሶ አደር)',
  descriptionEn: 'Grows coffee and sells it to local buyers.',
  businessRole: 'supplier',
  capabilities: ['produce', 'sell_domestic'],
);
const _exporter = BusinessCategoryOption(
  code: 'general_exporter',
  labelEn: 'Coffee exporter',
  labelAm: 'ቡና ላኪ',
  businessRole: 'exporter',
  capabilities: ['sell_export', 'buy_domestic'],
);

const _options = WorkspaceOptions(
  entityTypes: [EntityTypeOption(value: 'private_limited_company', label: 'PLC', needsManualReview: false)],
  businessRoles: ['exporter', 'supplier', 'broker', 'buyer'],
  businessCategories: [_farmer, _exporter],
  countries: ['Ethiopia'],
  defaultCountry: 'Ethiopia',
  maxOwnedBusinesses: 3,
);

class _FakeOrgService extends OrganizationService {
  _FakeOrgService({this.canEdit = true});

  final bool canEdit;
  final List<String> calls = [];
  List<String> declared = ['sell_export', 'buy_domestic'];

  BusinessActivities _activities() => BusinessActivities(
        category: _exporter,
        canChangeCategory: true,
        canEdit: canEdit,
        capabilities: [
          BusinessCapability(code: 'sell_export', declared: declared.contains('sell_export'),
              status: declared.contains('sell_export') ? 'needs_documents' : 'off', missingDocuments: const ['ecta_exporter_certificate']),
          BusinessCapability(code: 'buy_domestic', declared: declared.contains('buy_domestic'),
              status: declared.contains('buy_domestic') ? 'active' : 'off'),
        ],
      );

  @override
  Future<WorkspaceOptions> getOptions() async => _options;

  @override
  Future<WorkspaceSnapshot> getUserOrganizations() async => const WorkspaceSnapshot(
      current: null, organizations: [], intents: [], maxOwnedBusinesses: 3, ownedBusinesses: 0);

  @override
  Future<OrganizationModel> createOrganization({
    required String name,
    required String businessRole,
    String? businessCategory,
    required String entityType,
    required String country,
    String? tinNumber,
    String? billingEmail,
  }) async {
    calls.add('create:$businessRole:$businessCategory');
    return OrganizationModel(id: 9, name: name, slug: 'x', businessType: businessRole);
  }

  @override
  Future<BusinessActivities> getActivities(int organizationId) async => _activities();

  @override
  Future<BusinessActivities> updateActivities(int organizationId, List<String> capabilities) async {
    calls.add('activities:${capabilities.join(',')}');
    declared = capabilities;
    return _activities();
  }
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

  test('categories and activities are read from the server answer', () {
    final options = WorkspaceOptions.fromJson({
      'business_categories': [
        {'code': 'farmer', 'label_en': 'Farmer (Alami)', 'label_am': 'አልሚ', 'business_role': 'supplier', 'capabilities': ['produce']},
      ],
    });
    expect(options.businessCategories.single.label('am'), 'አልሚ');
    expect(options.businessCategories.single.label('en'), 'Farmer (Alami)');
    expect(options.businessCategories.single.businessRole, 'supplier');

    final org = OrganizationModel.fromJson({
      'id': 1, 'name': 'A', 'slug': 'a', 'business_role': 'exporter',
      'business_category': {'code': 'general_exporter', 'label_en': 'Coffee exporter', 'label_am': 'ቡና ላኪ'},
      'active_capabilities': ['sell_export'],
    });
    expect(org.businessCategory?.code, 'general_exporter');
    expect(org.activeCapabilities, ['sell_export']);
    expect(OrganizationModel.fromJson({'id': 1}).businessCategory, isNull);
  });

  testWidgets('business setup sends the chosen category and its role', (tester) async {
    _tall(tester);
    final service = _FakeOrgService();
    await tester.pumpWidget(_host(BusinessWorkspaceSetupScreen(afterCreate: (_) => const Text('verification-next')), service));
    await tester.pumpAndSettle();

    expect(find.text(kLangEn['businessCategoryLabel']!), findsOneWidget);
    expect(find.text(kLangEn['businessRoleLabel']!), findsNothing);

    await tester.enterText(find.byType(TextFormField).first, 'Sidama Farm');
    await tester.tap(find.text(kLangEn['businessCreate']!));
    await tester.pumpAndSettle();
    expect(find.text(kLangEn['businessCategoryRequired']!), findsOneWidget);
    expect(service.calls, isEmpty);

    await tester.tap(find.byKey(const Key('business-category')));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Farmer (Alami)').last);
    await tester.pumpAndSettle();
    expect(find.text('Grows coffee and sells it to local buyers.'), findsOneWidget);

    await tester.tap(find.text(kLangEn['entityTypeHint']!));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Private limited company (PLC)').last);
    await tester.pumpAndSettle();
    await tester.tap(find.text(kLangEn['businessCreate']!));
    await tester.pumpAndSettle();
    expect(service.calls, ['create:supplier:farmer']);
  });

  testWidgets('activities show status in words and save only what changed', (tester) async {
    _tall(tester);
    final service = _FakeOrgService();
    await tester.pumpWidget(_host(BusinessActivitiesScreen(organizationId: 9, service: service), service));
    await tester.pumpAndSettle();

    expect(find.text('Coffee exporter'), findsOneWidget);
    expect(find.text(kLangEn['capabilitySellExport']!), findsOneWidget);
    expect(find.text(kLangEn['activityStatusNeedsDocuments']!), findsOneWidget);
    expect(find.text(kLangEn['activityStatusActive']!), findsOneWidget);
    expect(find.text(kLangEn['activitiesAddDocument']!), findsOneWidget);
    expect(find.byKey(const Key('activities-save')), findsNothing);

    await tester.tap(find.byKey(const Key('activity-buy_domestic')));
    await tester.pumpAndSettle();
    await tester.tap(find.byKey(const Key('activities-save')));
    await tester.pumpAndSettle();

    expect(service.calls, ['activities:sell_export']);
    expect(find.text(kLangEn['activitiesSaved']!), findsOneWidget);
    expect(find.text(kLangEn['activityStatusOff']!), findsOneWidget);
  });

  testWidgets('members who are not the owner see the activities but cannot change them', (tester) async {
    _tall(tester);
    final service = _FakeOrgService(canEdit: false);
    await tester.pumpWidget(_host(BusinessActivitiesScreen(organizationId: 9, service: service), service));
    await tester.pumpAndSettle();

    expect(find.text(kLangEn['activitiesOwnerOnly']!), findsOneWidget);
    expect(find.byKey(const Key('activities-change-category')), findsNothing);
    final toggle = tester.widget<SwitchListTile>(find.byKey(const Key('activity-sell_export')));
    expect(toggle.onChanged, isNull);
  });
}

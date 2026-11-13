import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../providers/auth_provider.dart';
import '../screens/auth/auth_screen.dart';
import '../screens/auth/phone_sign_in_screen.dart';
import '../screens/dashboard/dashboard_screen.dart';
import '../screens/onboarding/onboarding_screen.dart';
import '../screens/home/home2_screen.dart';
import '../screens/shell/main_shell_screen.dart';
import '../widgets/bottom_nav_bar.dart';
import '../config/config.dart';
import '../screens/dashboard/my_courses_screen.dart';
import '../screens/dashboard/my_assignments_screen.dart';
import '../screens/dashboard/my_quiz_attempts_screen.dart';
import '../screens/dashboard/certificates_screen.dart';
import '../screens/dashboard/certificate_verification_screen.dart';
import '../screens/dashboard/my_ticket_bookings_screen.dart';
import '../screens/dashboard/payments_screen.dart';
import '../screens/study_timer/pomodoro_timer_screen.dart';
import '../screens/study_timer/focus_progress_screen.dart';
import '../screens/habit_tracker/weekly_review_screen.dart';
import '../screens/ai_chat/ai_chat_screen.dart';
import '../screens/ai_suggestions/ai_suggestions_screen.dart';
import '../screens/profile/settings_screen.dart';
import '../screens/profile/notification_settings_screen.dart';
import '../screens/profile/edit_profile_screen.dart';
import '../screens/profile/change_password_screen.dart';
import '../screens/profile/payment_history_screen.dart';
import '../screens/notifications/notifications_screen.dart';
import '../screens/wishlist/wishlist_screen.dart';
import '../screens/community/qa_room_screen.dart';
import '../screens/ai_learning_path/ai_learning_path_screen.dart';
import '../screens/chat/conversations_screen.dart';
import '../screens/chat/chat_screen.dart';
import '../screens/coffee_toolkit/coffee_toolkit_hub_screen.dart';
import '../screens/coffee_toolkit/coffee_profit_calculator_screen.dart';
import '../screens/coffee_toolkit/coffee_offer_generator_screen.dart';
import '../screens/coffee_toolkit/coffee_readiness_screen.dart';
import '../screens/coffee_toolkit/coffee_market_screen.dart';
import '../screens/marketplace/coffee_listing_detail_screen.dart';
import '../screens/marketplace/marketplace_rfqs_screen.dart';
import '../screens/marketplace/create_rfq_screen.dart';
import '../screens/marketplace/seller_portal_screen.dart';
import '../screens/marketplace/create_listing_screen.dart';
import '../screens/marketplace/marketplace_pricing_screen.dart';
import '../screens/marketplace/organization_verification_screen.dart';
import '../screens/marketplace/export_docs_studio_screen.dart';
import '../screens/auth/language_choice_screen.dart';
import '../screens/onboarding/intent_selection_screen.dart';
import '../screens/deal_room/deal_room_hub_screen.dart';
import '../screens/marketplace/sample_tracking_screen.dart';
import '../screens/marketplace/public_seller_verification_screen.dart';
import '../screens/profile/create_workspace_screen.dart';

class AppRouter {
  static final GlobalKey<NavigatorState> navigatorKey = GlobalKey<NavigatorState>();
  static const String onboarding = '/onboarding';
  static const String auth = '/auth';
  static const String emailSignIn = '/auth/email';
  static const String dashboard = '/dashboard';
  static const String home = '/home';
  static const String profile = '/profile';
  static const String aiChat = '/ai-chat';
  static const String coffeeToolkit = '/coffee-toolkit';
  static const String coffeeCalculator = '/coffee-toolkit/calculator';
  static const String coffeeOfferGenerator = '/coffee-toolkit/offer-generator';
  static const String coffeeReadiness = '/coffee-toolkit/readiness';
  static const String coffeeMarket = '/coffee-toolkit/market';
  static const String marketplace = '/marketplace';
  static const String coffeeListingDetail = '/marketplace/lot';
  static const String marketplaceRfqs = '/marketplace/rfqs';
  static const String marketplaceCreateRfq = '/marketplace/rfqs/create';
  static const String marketplaceSellerPortal = '/marketplace/seller';
  static const String marketplaceCreateListing = '/marketplace/seller/add-lot';
  static const String marketplacePricing = '/marketplace/pricing';
  static const String marketplaceVerification = '/marketplace/verification';
  static const String exportDocsStudio = '/export-docs';
  static const String myCourses = '/my-courses';
  static const String myAssignments = '/my-assignments';
  static const String myQuizAttempts = '/my-quizzes';
  static const String certificates = '/certificates';
  static const String verifyCertificate = '/verify-certificate';
  static const String myTicketBookings = '/bookings';
  static const String payments = '/payments';
  static const String editProfile = '/edit-profile';
  static const String changePassword = '/change-password';
  static const String paymentHistory = '/payment-history';
  static const String notifications = '/notifications';
  static const String wishlist = '/wishlist';
  static const String qaRoom = '/qa-room';
  static const String aiLearningPath = '/ai-learning-path';
  static const String studyTimer = '/study-timer';
  static const String focusProgress = '/focus-progress';
  static const String weeklyReview = '/weekly-review';
  static const String aiSuggestions = '/ai-suggestions';
  static const String settings = '/settings';
  static const String notificationSettings = '/notification-settings';
  static const String messages = '/messages';
  static const String chatScreen = '/messages/chat';
  static const String languageChoice = '/language-choice';
  static const String intentSelection = '/onboarding/intent';
  static const String dealRoom = '/deal-room';
  static const String sampleTracking = '/marketplace/samples/tracking';
  static const String publicVerification = '/verify-seller';
  static const String createWorkspace = '/profile/workspaces/create';

  // List of routes that require authentication
  static final List<String> _protectedRoutes = [
    dashboard,
    profile,
    dealRoom,
    sampleTracking,
    createWorkspace,
    myCourses,
    myAssignments,
    myQuizAttempts,
    certificates,
    myTicketBookings,
    payments,
    editProfile,
    changePassword,
    paymentHistory,
    notifications,
    wishlist,
    qaRoom,
    aiLearningPath,
    messages,
    chatScreen,
  ];

  static Route<dynamic> onGenerateRoute(RouteSettings settings) {
    final String? routeName = settings.name;

    // Check if the route is protected
    if (_protectedRoutes.contains(routeName)) {
      return MaterialPageRoute(
        builder: (context) =>
            AuthGuard(child: _buildScreen(routeName, settings.arguments)),
      );
    }

    return MaterialPageRoute(
      builder: (context) => _buildScreen(routeName, settings.arguments),
    );
  }

  static Widget _buildScreen(String? routeName, dynamic arguments) {
    switch (routeName) {
      case onboarding:
        return const OnboardingScreen();
      case auth:
        // Phone sign-in is the main way in (new and returning users); email is linked from it.
        return const PhoneSignInScreen();
      case emailSignIn:
        return const AuthScreen();
      case dashboard:
        return const DashboardScreen();
      case profile:
        return const MainShellScreen(initialTab: BottomNavTab.profile);
      case aiChat:
        return const AiChatScreen();
      case home:
        return AppConfig.getHomePage();
      case '/home2':
        return const Home2Screen();
      case myCourses:
        return const MyCoursesScreen();
      case myAssignments:
        return const MyAssignmentsScreen();
      case myQuizAttempts:
        return const MyQuizAttemptsScreen();
      case certificates:
        return const CertificatesScreen();
      case verifyCertificate:
        return const CertificateVerificationScreen();
      case myTicketBookings:
        return const MyTicketBookingsScreen();
      case payments:
        return const PaymentsScreen();
      case editProfile:
        return const EditProfileScreen();
      case changePassword:
        return const ChangePasswordScreen();
      case paymentHistory:
        return const PaymentHistoryScreen();
      case notifications:
        return const NotificationsScreen();
      case wishlist:
        return const WishlistScreen();
      case qaRoom:
        return const QaRoomScreen();
      case aiLearningPath:
        return const AiLearningPathScreen();
      case studyTimer:
        return const PomodoroTimerScreen();
      case focusProgress:
        return const FocusProgressScreen();
      case weeklyReview:
        return const WeeklyReviewScreen();
      case aiSuggestions:
        return const AiSuggestionsScreen();
      case settings:
        return const SettingsScreen();
      case messages:
        return const ConversationsScreen();
      case chatScreen:
        final args = arguments as Map<String, dynamic>;
        return ChatScreen(
          conversationId: args['conversationId'] as int?,
          instructorId: args['instructorId'] as int?,
          instructorName: args['instructorName'] as String?,
        );
      case notificationSettings:
        return const NotificationSettingsScreen();
      case coffeeToolkit:
        return const CoffeeToolkitHubScreen();
      case coffeeCalculator:
        return const CoffeeProfitCalculatorScreen();
      case coffeeOfferGenerator:
        return const CoffeeOfferGeneratorScreen();
      case coffeeReadiness:
        return const CoffeeReadinessScreen();
      case coffeeMarket:
        return const CoffeeMarketScreen();
      case marketplace:
        return const MainShellScreen(initialTab: BottomNavTab.b2b);
      case coffeeListingDetail:
        return CoffeeListingDetailScreen(idOrSlug: arguments);
      case marketplaceRfqs:
        return const MarketplaceRfqsScreen();
      case marketplaceCreateRfq:
        return const CreateRfqScreen();
      case marketplaceSellerPortal:
        return const SellerPortalScreen();
      case marketplaceCreateListing:
        return const CreateListingScreen();
      case marketplacePricing:
        return const MarketplacePricingScreen();
      case marketplaceVerification:
        final role = arguments is String ? arguments : null;
        return OrganizationVerificationScreen(initialRole: role);
      case exportDocsStudio:
        return const ExportDocsStudioScreen();
      case languageChoice:
        return const LanguageChoiceScreen();
      case intentSelection:
        return const IntentSelectionScreen();
      case dealRoom:
        final id = arguments is int ? arguments : 1;
        return DealRoomHubScreen(dealRoomId: id);
      case sampleTracking:
        final id = arguments is int ? arguments : 1;
        return SampleTrackingScreen(sampleId: id);
      case publicVerification:
        final query = arguments is String ? arguments : '';
        return PublicSellerVerificationScreen(query: query);
      case createWorkspace:
        return const CreateWorkspaceScreen();
      default:
        // Default to onboarding or an error screen
        return const OnboardingScreen();
    }
  }
}

class AuthGuard extends StatelessWidget {
  final Widget child;

  const AuthGuard({super.key, required this.child});

  @override
  Widget build(BuildContext context) {
    return Consumer<AuthProvider>(
      builder: (context, auth, _) {
        if (auth.isAuthenticated) {
          return child;
        } else {
          // Redirect to sign-in if not authenticated
          return const PhoneSignInScreen();
        }
      },
    );
  }
}

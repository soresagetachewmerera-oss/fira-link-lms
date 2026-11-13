/// Where the app goes after the splash screen.
enum StartupRoute { home, languagePicker, onboarding }

/// Saved preference keys for first-launch state.
class StartupPrefs {
  const StartupPrefs._();

  /// Set when the user confirms a language on the first-launch picker.
  static const String languageChosen = 'language_chosen';

  /// Set when onboarding is finished, skipped or left via "Explore as guest".
  static const String seenOnboarding = 'has_seen_onboarding';
}

/// The language picker shows only on first launch: never for a signed-in user, and never for
/// someone who already finished onboarding (people upgrading from an older version).
StartupRoute decideStartupRoute({
  required bool isAuthenticated,
  required bool languageChosen,
  required bool seenOnboarding,
}) {
  if (isAuthenticated) return StartupRoute.home;
  if (seenOnboarding) return StartupRoute.home;
  if (!languageChosen) return StartupRoute.languagePicker;
  return StartupRoute.onboarding;
}

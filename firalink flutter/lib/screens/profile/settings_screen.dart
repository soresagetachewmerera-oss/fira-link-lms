import 'package:flutter/material.dart';
import 'package:hugeicons/hugeicons.dart';

import '../../theme/app_theme.dart';
import '../../l10n/app_localizations.dart';
import '../../services/custom_page_service.dart';
import '../../models/custom_page.dart';
import 'edit_profile_screen.dart';
import 'change_password_screen.dart';
import 'privacy_terms_screen.dart';
import 'rate_app_sheet.dart';
import 'share_app_sheet.dart';
import 'package:provider/provider.dart';
import '../../providers/auth_provider.dart';
import '../../providers/settings_provider.dart';
import '../../services/profile_service.dart';
import '../../config/config.dart';
import '../common/webview_screen.dart';

class SettingsScreen extends StatefulWidget {
  const SettingsScreen({super.key});

  @override
  State<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends State<SettingsScreen> {
  final CustomPageService _customPageService = CustomPageService();
  List<CustomPage> _customPages = [];
  bool _isLoadingPages = true;

  @override
  void initState() {
    super.initState();
    _loadCustomPages();
  }

  Future<void> _loadCustomPages() async {
    try {
      final pages = await _customPageService.getPages();
      if (mounted) {
        setState(() {
          _customPages = pages;
          _isLoadingPages = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _isLoadingPages = false;
        });
      }
      debugPrint('Error loading pages: $e');
    }
  }

  void _showLanguageSheet(BuildContext context) {
    final currentLocale = Localizations.localeOf(context);
    final languages = [
      {'code': 'en', 'name': 'English', 'icon': 'assets/img/icons/en.png'},
      {'code': 'am', 'name': 'አማርኛ', 'icon': 'assets/img/icons/am.png'},
    ];

    showModalBottomSheet<void>(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (BuildContext ctx) {
        return Container(
          decoration: BoxDecoration(
            color: AppTheme.getCardColor(ctx),
            borderRadius: const BorderRadius.only(
              topLeft: Radius.circular(20),
              topRight: Radius.circular(20),
            ),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: <Widget>[
              Container(
                margin: const EdgeInsets.only(top: 12),
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: AppTheme.getTextColor(ctx).withValues(alpha: 0.2),
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
              Padding(
                padding: const EdgeInsets.all(20),
                child: Text(
                  context.l10n.selectLanguage,
                  style: TextStyle(
                    color: AppTheme.getTextColor(ctx),
                    fontSize: 20,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
              ...languages.map((lang) {
                final isSelected = lang['code'] == currentLocale.languageCode;
                return GestureDetector(
                  onTap: () {
                    Navigator.of(ctx).pop();
                    Provider.of<SettingsProvider>(context, listen: false)
                        .setLocale(Locale(lang['code']!));
                  },
                  child: Container(
                    margin: const EdgeInsets.symmetric(horizontal: 20, vertical: 4),
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: isSelected
                          ? AppTheme.getMint100(ctx)
                          : AppTheme.getSoftGray150(ctx),
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: Row(
                      children: <Widget>[
                        Container(
                          width: 40,
                          height: 40,
                          decoration: BoxDecoration(
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(
                              color: isSelected
                                  ? AppTheme.getTextColor(ctx)
                                  : Colors.transparent,
                              width: 2,
                            ),
                          ),
                          child: ClipRRect(
                            borderRadius: BorderRadius.circular(10),
                            child: Image.asset(
                              lang['icon']!,
                              width: 40,
                              height: 40,
                              fit: BoxFit.cover,
                              errorBuilder: (context, error, stackTrace) => Container(
                                color: AppTheme.getMint100(ctx),
                                child: Center(
                                  child: HugeIcon(
                                    icon: HugeIcons.strokeRoundedLanguageCircle,
                                    size: 20,
                                    color: AppTheme.getTextColor(ctx),
                                  ),
                                ),
                              ),
                            ),
                          ),
                        ),
                        const SizedBox(width: 16),
                        Expanded(
                          child: Text(
                            lang['name']!,
                            style: TextStyle(
                              color: AppTheme.getTextColor(ctx),
                              fontSize: 16,
                              fontWeight: isSelected ? FontWeight.w600 : FontWeight.w500,
                            ),
                          ),
                        ),
                        if (isSelected)
                          HugeIcon(
                            icon: HugeIcons.strokeRoundedCheckmarkCircle02,
                            size: 24,
                            color: AppTheme.getTextColor(ctx),
                          ),
                      ],
                    ),
                  ),
                );
              }),
              const SizedBox(height: 20),
            ],
          ),
        );
      },
    );
  }

  Future<void> _showDeleteAccountDialog() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(context.l10n.deleteAccount),
        content: Text(
          context.l10n.deleteAccountConfirm,
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: Text(
              context.l10n.cancel,
              style: TextStyle(color: AppTheme.getTextColor(context)),
            ),
          ),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
            child: Text(context.l10n.delete, style: const TextStyle(color: Colors.red)),
          ),
        ],
      ),
    );

    if (confirmed == true && mounted) {
      // Show loading indicator
      showDialog(
        context: context,
        barrierDismissible: false,
        builder: (context) => const Center(child: CircularProgressIndicator()),
      );

      try {
        await ProfileService().submitDeleteRequest();
        if (mounted) {
          Navigator.pop(context); // Close loading
          // Refresh status
          await Provider.of<AuthProvider>(
            context,
            listen: false,
          ).checkDeletionRequestStatus();

          if (mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: Text(
                  context.l10n.deletionAwaitingApprovalMessage,
                  style: TextStyle(color: AppTheme.getTextColor(context)),
                ),
                backgroundColor: AppTheme.getCardColor(context),
                behavior: SnackBarBehavior.floating,
              ),
            );
          }
        }
      } catch (e) {
        if (mounted) {
          Navigator.pop(context); // Close loading
          ScaffoldMessenger.of(
            context,
          ).showSnackBar(SnackBar(content: Text('Error: $e')));
        }
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.getBackgroundColor(context),
      body: SafeArea(
        bottom: false,
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: <Widget>[
              // Header with back button
              Padding(
                padding: const EdgeInsets.fromLTRB(0, 8, 0, 16),
                child: Row(
                  children: <Widget>[
                    IconButton(
                        tooltip: MaterialLocalizations.of(context).backButtonTooltip,
                        icon: HugeIcon(
                          icon: HugeIcons.strokeRoundedArrowLeft01,
                        size: 20,
                        color: AppTheme.getTextColor(context),
                      ),
                      onPressed: () => Navigator.of(context).pop(),
                      padding: EdgeInsets.zero,
                    ),
                    Expanded(
                      child: Text(
                        context.l10n.settings,
                        textAlign: TextAlign.center,
                        style: TextStyle(
                          color: AppTheme.getTextColor(context),
                          fontSize: 20,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ),
                    const SizedBox(width: 40),
                  ],
                ),
              ),

              // Preferences / Language Section
              Padding(
                padding: const EdgeInsets.only(bottom: 8),
                child: Text(
                  context.l10n.preferences,
                  style: TextStyle(
                    color: AppTheme.getTextColor(
                      context,
                    ).withValues(alpha: 0.7),
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: AppTheme.getCardColor(context),
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Column(
                  children: <Widget>[
                    _MenuItemWithCustomIcon(
                      icon: Icons.language,
                      title: context.l10n.language,
                      subtitle: Localizations.localeOf(context).languageCode == 'am'
                          ? 'አማርኛ'
                          : 'English',
                      onTap: () => _showLanguageSheet(context),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 24),

              // Account Settings Section
              Padding(
                padding: const EdgeInsets.only(bottom: 8),
                child: Text(
                  context.l10n.accountSettings,
                  style: TextStyle(
                    color: AppTheme.getTextColor(
                      context,
                    ).withValues(alpha: 0.7),
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: AppTheme.getCardColor(context),
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Column(
                  children: <Widget>[
                    _MenuItemWithCustomIcon(
                      icon: Icons.person_outline,
                      title: context.l10n.editProfile,
                      subtitle: context.l10n.editProfileSubtitle,
                      onTap: () {
                        Navigator.of(context).push(
                          MaterialPageRoute(
                            builder: (context) => const EditProfileScreen(),
                          ),
                        );
                      },
                    ),
                    _MenuItemWithCustomIcon(
                      icon: Icons.lock_outline,
                      title: context.l10n.changePassword,
                      subtitle: context.l10n.changePasswordSubtitle,
                      onTap: () {
                        Navigator.of(context).push(
                          MaterialPageRoute(
                            builder: (context) => const ChangePasswordScreen(),
                          ),
                        );
                      },
                    ),
                    Builder(
                      builder: (context) {
                        final deletionStatus = Provider.of<AuthProvider>(
                          context,
                        ).deletionRequestStatus;
                        final isPending =
                            deletionStatus != null &&
                            deletionStatus['status'] == 'pending';

                        return _MenuItemWithCustomIcon(
                          icon: isPending
                              ? Icons.access_time_rounded
                              : Icons.delete_outline,
                          title: isPending
                              ? context.l10n.deletionPending
                              : context.l10n.deleteAccount,
                          subtitle: isPending
                              ? context.l10n.awaitingAdminApproval
                              : context.l10n.permanentlyRemoveAccount,
                          isDestructive: !isPending,
                          onTap: isPending
                              ? () {
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    SnackBar(
                                      content: Text(
                                        context.l10n.deletionAwaitingApprovalMessage,
                                      ),
                                    ),
                                  );
                                }
                              : () => _showDeleteAccountDialog(),
                        );
                      },
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 24),

              // Privacy & Security Section
              Padding(
                padding: const EdgeInsets.only(bottom: 8),
                child: Text(
                  context.l10n.privacyAndSecurity,
                  style: TextStyle(
                    color: AppTheme.getTextColor(
                      context,
                    ).withValues(alpha: 0.7),
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: AppTheme.getCardColor(context),
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Column(
                  children: <Widget>[
                    if (_isLoadingPages)
                      const Padding(
                        padding: EdgeInsets.all(20),
                        child: Center(child: CircularProgressIndicator()),
                      )
                    else if (_customPages.isEmpty)
                      Padding(
                        padding: const EdgeInsets.all(20),
                        child: Text(
                          context.l10n.noDataAvailable,
                          style: TextStyle(
                            color: AppTheme.getTextColor(
                              context,
                            ).withValues(alpha: 0.6),
                          ),
                        ),
                      )
                    else
                      ..._customPages.map((page) {
                        return _MenuItem(
                          icon: HugeIcons.strokeRoundedFile01,
                          title: page.title,
                          subtitle: '${context.l10n.view} ${page.title}',
                          onTap: () {
                            Navigator.of(context).push(
                              MaterialPageRoute(
                                builder: (context) => PrivacyTermsScreen(
                                  title: page.title,
                                  slug: page.slug,
                                ),
                              ),
                            );
                          },
                        );
                      }),
                  ],
                ),
              ),
              const SizedBox(height: 24),

              // Support & Info Section
              Padding(
                padding: const EdgeInsets.only(bottom: 8),
                child: Text(
                  context.l10n.supportInfo,
                  style: TextStyle(
                    color: AppTheme.getTextColor(
                      context,
                    ).withValues(alpha: 0.7),
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: AppTheme.getCardColor(context),
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Column(
                  children: <Widget>[
                    _MenuItemWithCustomIcon(
                      icon: Icons.info_outline,
                      title: context.l10n.about,
                      subtitle: context.l10n.aboutSubtitle,
                      onTap: () {
                        Navigator.of(context).push(
                          MaterialPageRoute(
                            builder: (context) => WebViewScreen(
                              url: '${AppConfig.baseUrl}/about',
                              title: context.l10n.about,
                            ),
                          ),
                        );
                      },
                    ),
                    _MenuItemWithCustomIcon(
                      icon: Icons.help_outline,
                      title: context.l10n.helpSupport,
                      subtitle: context.l10n.helpSupportSubtitle,
                      onTap: () {
                        Navigator.of(context).push(
                          MaterialPageRoute(
                            builder: (context) => WebViewScreen(
                              url: '${AppConfig.baseUrl}/contact',
                              title: context.l10n.helpSupport,
                            ),
                          ),
                        );
                      },
                    ),
                    _MenuItem(
                      icon: HugeIcons.strokeRoundedStar,
                      title: context.l10n.rateApp,
                      subtitle: context.l10n.rateAppSubtitle,
                      onTap: () {
                        showRateAppSheet(context);
                      },
                    ),
                    _MenuItem(
                      icon: HugeIcons.strokeRoundedShare01,
                      title: context.l10n.shareApp,
                      subtitle: context.l10n.shareAppSubtitle,
                      onTap: () {
                        showShareAppSheet(context);
                      },
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 40),
            ],
          ),
        ),
      ),
    );
  }
}

class _MenuItem extends StatelessWidget {
  const _MenuItem({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.onTap,
  });

  final dynamic icon;
  final String title;
  final String subtitle;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 12),
        child: Row(
          children: <Widget>[
            Container(
              width: 40,
              height: 40,
              decoration: BoxDecoration(
                color: AppTheme.getMint100(context),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Center(
                child: HugeIcon(
                  icon: icon,
                  size: 20,
                  color: AppTheme.getTextColor(context),
                ),
              ),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: <Widget>[
                  Text(
                    title,
                    style: TextStyle(
                      color: AppTheme.getTextColor(context),
                      fontSize: 16,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    subtitle,
                    style: TextStyle(
                      color: AppTheme.getTextColor(
                        context,
                      ).withValues(alpha: 0.6),
                      fontSize: 12,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ],
              ),
            ),
            HugeIcon(
              icon: HugeIcons.strokeRoundedArrowRight01,
              size: 20,
              color: AppTheme.getTextColor(context).withValues(alpha: 0.4),
            ),
          ],
        ),
      ),
    );
  }
}

class _MenuItemWithCustomIcon extends StatelessWidget {
  const _MenuItemWithCustomIcon({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.onTap,
    this.isDestructive = false,
  });

  final IconData icon;
  final String title;
  final String subtitle;
  final VoidCallback onTap;
  final bool isDestructive;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 12),
        child: Row(
          children: <Widget>[
            Container(
              width: 40,
              height: 40,
              decoration: BoxDecoration(
                color: AppTheme.getMint100(context),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Center(
                child: Icon(
                  icon,
                  size: 20,
                  color: isDestructive
                      ? Colors.red
                      : AppTheme.getTextColor(context),
                ),
              ),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: <Widget>[
                  Text(
                    title,
                    style: TextStyle(
                      color: isDestructive
                          ? Colors.red
                          : AppTheme.getTextColor(context),
                      fontSize: 16,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    subtitle,
                    style: TextStyle(
                      color: AppTheme.getTextColor(
                        context,
                      ).withValues(alpha: 0.6),
                      fontSize: 12,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ],
              ),
            ),
            HugeIcon(
              icon: HugeIcons.strokeRoundedArrowRight01,
              size: 20,
              color: AppTheme.getTextColor(context).withValues(alpha: 0.4),
            ),
          ],
        ),
      ),
    );
  }
}

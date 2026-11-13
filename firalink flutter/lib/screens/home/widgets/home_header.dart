import 'package:flutter/material.dart';
import 'package:hugeicons/hugeicons.dart';
import 'package:provider/provider.dart';

import '../../../providers/auth_provider.dart';
import '../../../providers/chat_provider.dart';
import '../../../providers/workspace_provider.dart';
import '../../../l10n/app_localizations.dart';
import '../../../theme/fira_colors.dart';
import '../../profile/workspace_switcher_sheet.dart';
import '../../../router/app_router.dart';
import '../../notifications/notifications_screen.dart';
import '../../dashboard/dashboard_screen.dart';
import '../../auth/auth_screen.dart';
import 'home_design_constants.dart';

class HomeHeader extends StatelessWidget {
  const HomeHeader({super.key});

  String _getTimeBasedGreeting() {
    final hour = DateTime.now().hour;
    if (hour < 12) {
      return 'Good morning';
    } else if (hour < 17) {
      return 'Good afternoon';
    } else {
      return 'Good evening';
    }
  }

  String _getFirstName(String fullName) {
    final trimmed = fullName.trim();
    if (trimmed.isEmpty) return 'Trader';
    return trimmed.split(RegExp(r'\s+')).first;
  }

  @override
  Widget build(BuildContext context) {
    return Consumer<AuthProvider>(
      builder: (context, auth, _) {
        final user = auth.user;
        final bool isAuthenticated = auth.isAuthenticated;
        final String displayName = isAuthenticated && user != null
            ? _getFirstName(user.name)
            : 'Trader';

        final Color primaryColor = HomeDesignConstants.brandPrimary(context);
        final Color textColor = HomeDesignConstants.text(context);
        final Color mutedColor = HomeDesignConstants.mutedText(context);
        final Color tintColor = HomeDesignConstants.brandTint(context);

        return Padding(
          padding: const EdgeInsets.symmetric(
            horizontal: HomeDesignConstants.horizontalPadding,
          ),
          child: Row(
            children: <Widget>[
              // Avatar
              Semantics(
                label: 'User profile',
                button: true,
                child: InkWell(
                  onTap: () {
                    if (isAuthenticated) {
                      Navigator.of(context).push(
                        MaterialPageRoute(
                          builder: (context) => const DashboardScreen(),
                        ),
                      );
                    } else {
                      Navigator.of(context).push(
                        MaterialPageRoute(
                          builder: (context) => const AuthScreen(),
                        ),
                      );
                    }
                  },
                  borderRadius: BorderRadius.circular(24),
                  child: Container(
                    width: 44,
                    height: 44,
                    decoration: BoxDecoration(
                      color: tintColor,
                      shape: BoxShape.circle,
                      border: Border.all(
                        color: primaryColor.withValues(alpha: 0.18),
                        width: 1.5,
                      ),
                    ),
                    clipBehavior: Clip.antiAlias,
                    child: (isAuthenticated &&
                            user?.profilePhotoUrl != null &&
                            user!.profilePhotoUrl!.isNotEmpty)
                        ? Image.network(
                            user.profilePhotoUrl!,
                            fit: BoxFit.cover,
                            errorBuilder: (_, _, _) => _buildFallbackAvatar(displayName, primaryColor),
                          )
                        : _buildFallbackAvatar(displayName, primaryColor),
                  ),
                ),
              ),
              const SizedBox(width: 12),

              // Greeting + First name
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisSize: MainAxisSize.min,
                  children: <Widget>[
                    Text(
                      _getTimeBasedGreeting(),
                      style: TextStyle(
                        color: mutedColor,
                        fontSize: 12.5,
                        fontWeight: FontWeight.w500,
                        letterSpacing: 0.1,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      displayName,
                      style: TextStyle(
                        color: textColor,
                        fontSize: 18,
                        fontWeight: FontWeight.w800,
                        letterSpacing: -0.2,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    if (isAuthenticated) const _WorkspaceChip(),
                  ],
                ),
              ),

              // Right circular tint buttons: Messages and Notifications
              Row(
                mainAxisSize: MainAxisSize.min,
                children: <Widget>[
                  // Messages Button
                  Semantics(
                    label: 'Messages',
                    button: true,
                    child: Consumer<ChatProvider>(
                      builder: (context, chatProvider, _) {
                        final int unreadCount = chatProvider.unreadCount;
                        return Stack(
                          clipBehavior: Clip.none,
                          children: [
                            InkWell(
                              onTap: () {
                                Navigator.of(context).pushNamed(AppRouter.messages);
                              },
                              borderRadius: BorderRadius.circular(24),
                              child: Container(
                                width: 44,
                                height: 44,
                                decoration: BoxDecoration(
                                  color: tintColor,
                                  shape: BoxShape.circle,
                                ),
                                child: Center(
                                  child: HugeIcon(
                                    icon: HugeIcons.strokeRoundedBubbleChat,
                                    size: 20,
                                    color: primaryColor,
                                  ),
                                ),
                              ),
                            ),
                            if (unreadCount > 0)
                              Positioned(
                                right: 2,
                                top: 2,
                                child: Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 2),
                                  decoration: BoxDecoration(
                                    color: const Color(0xFFDC2626),
                                    shape: BoxShape.circle,
                                    border: Border.all(
                                      color: HomeDesignConstants.surface(context),
                                      width: 1.5,
                                    ),
                                  ),
                                  constraints: const BoxConstraints(minWidth: 14, minHeight: 14),
                                  child: Center(
                                    child: Text(
                                      unreadCount > 9 ? '9+' : '$unreadCount',
                                      style: const TextStyle(
                                        color: Colors.white,
                                        fontSize: 8.5,
                                        fontWeight: FontWeight.bold,
                                      ),
                                    ),
                                  ),
                                ),
                              ),
                          ],
                        );
                      },
                    ),
                  ),

                  const SizedBox(width: 10),

                  // Notifications Button
                  Semantics(
                    label: 'Notifications',
                    button: true,
                    child: InkWell(
                      onTap: () {
                        Navigator.of(context).push(
                          MaterialPageRoute(
                            builder: (context) => const NotificationsScreen(),
                          ),
                        );
                      },
                      borderRadius: BorderRadius.circular(24),
                      child: Stack(
                        clipBehavior: Clip.none,
                        alignment: Alignment.center,
                        children: [
                          Container(
                            width: 44,
                            height: 44,
                            decoration: BoxDecoration(
                              color: tintColor,
                              shape: BoxShape.circle,
                            ),
                            child: Center(
                              child: HugeIcon(
                                icon: HugeIcons.strokeRoundedNotification01,
                                size: 20,
                                color: primaryColor,
                              ),
                            ),
                          ),
                          // Subtle red indicator dot
                          Positioned(
                            right: 10,
                            top: 10,
                            child: Container(
                              width: 8,
                              height: 8,
                              decoration: BoxDecoration(
                                color: const Color(0xFFDC2626),
                                shape: BoxShape.circle,
                                border: Border.all(
                                  color: HomeDesignConstants.surface(context),
                                  width: 1.5,
                                ),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
        );
      },
    );
  }

  Widget _buildFallbackAvatar(String name, Color primaryColor) {
    return _fallbackAvatar(name, primaryColor);
  }
}

/// The active workspace (Personal or a business) under the greeting; tap opens the switcher.
class _WorkspaceChip extends StatelessWidget {
  const _WorkspaceChip();

  @override
  Widget build(BuildContext context) {
    final fira = context.fira;
    final org = context.watch<WorkspaceProvider>().currentOrganization;
    final label = org?.name ?? context.l10n.workspacePersonal;

    return Padding(
      padding: const EdgeInsets.only(top: 4),
      child: Semantics(
        button: true,
        label: context.l10n.workspaceSwitchTitle,
        child: InkWell(
          onTap: () => WorkspaceSwitcherSheet.show(context),
          borderRadius: BorderRadius.circular(12),
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
            decoration: BoxDecoration(color: fira.chipSelectedBg, borderRadius: BorderRadius.circular(12)),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(org == null ? Icons.person_outline : Icons.business_outlined, size: 13, color: fira.brandPrimary),
                const SizedBox(width: 4),
                Flexible(
                  child: Text(
                    label,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.w600, color: fira.brandPrimary),
                  ),
                ),
                Icon(Icons.expand_more, size: 14, color: fira.brandPrimary),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

Widget _fallbackAvatar(String name, Color primaryColor) {
  final initial = name.isNotEmpty ? name[0].toUpperCase() : 'F';
  return Center(
    child: Text(
      initial,
      style: TextStyle(
        color: primaryColor,
        fontSize: 18,
        fontWeight: FontWeight.w800,
      ),
    ),
  );
}

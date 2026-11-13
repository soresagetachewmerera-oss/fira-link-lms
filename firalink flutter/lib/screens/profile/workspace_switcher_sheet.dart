import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../l10n/app_localizations.dart';
import '../../models/organization_model.dart';
import '../../providers/auth_provider.dart';
import '../../providers/workspace_provider.dart';
import '../../theme/fira_colors.dart';
import '../../widgets/fira_error_state.dart';
import '../../widgets/workspace_status_pill.dart';
import 'create_workspace_screen.dart';
import 'team_seats_screen.dart';

/// Workspace switcher: Personal plus every business the user is an active member of.
/// Status pills come from the server; nothing here is decided by a plan.
class WorkspaceSwitcherSheet extends StatelessWidget {
  const WorkspaceSwitcherSheet({super.key});

  static Future<void> show(BuildContext context) {
    context.read<WorkspaceProvider>().loadWorkspaces();
    return showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => const WorkspaceSwitcherSheet(),
    );
  }

  Future<void> _switchTo(BuildContext context, WorkspaceProvider ws, int? organizationId) async {
    final navigator = Navigator.of(context);
    try {
      await ws.switchWorkspace(organizationId);
      navigator.pop();
    } catch (e) {
      if (context.mounted) showFiraActionError(context, e);
    }
  }

  @override
  Widget build(BuildContext context) {
    final fira = context.fira;
    final l10n = context.l10n;
    final user = context.watch<AuthProvider>().user;

    return Consumer<WorkspaceProvider>(
      builder: (context, ws, _) {
        final current = ws.currentOrganization;

        return Container(
          constraints: BoxConstraints(maxHeight: MediaQuery.of(context).size.height * 0.85),
          decoration: BoxDecoration(
            color: fira.card,
            borderRadius: const BorderRadius.vertical(top: Radius.circular(28)),
          ),
          padding: const EdgeInsets.fromLTRB(20, 12, 20, 12),
          child: SafeArea(
            child: SingleChildScrollView(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Center(
                    child: Container(
                      width: 40,
                      height: 4,
                      decoration: BoxDecoration(
                        color: fira.mutedText.withValues(alpha: 0.3),
                        borderRadius: BorderRadius.circular(2),
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  if (user != null) ...[
                    Text(
                      user.name,
                      style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: fira.onSurface),
                      overflow: TextOverflow.ellipsis,
                    ),
                    if ((user.phone?.isNotEmpty == true ? user.phone! : user.email).isNotEmpty)
                      Text(
                        user.phone?.isNotEmpty == true ? user.phone! : user.email,
                        style: TextStyle(fontSize: 12, color: fira.mutedText),
                      ),
                    const SizedBox(height: 16),
                  ],
                  Text(
                    l10n.workspaceSwitchTitle,
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: fira.onSurface),
                  ),
                  const SizedBox(height: 12),

                  if (ws.error != null && ws.organizations.isEmpty)
                    SizedBox(height: 220, child: FiraErrorState(error: ws.error, onRetry: ws.loadWorkspaces))
                  else ...[
                    _WorkspaceTile(
                      initial: null,
                      title: l10n.workspacePersonal,
                      subtitle: l10n.workspacePersonalSubtitle,
                      isCurrent: current == null,
                      onTap: current == null || ws.isLoading ? null : () => _switchTo(context, ws, null),
                    ),
                    for (final org in ws.organizations) ...[
                      const SizedBox(height: 10),
                      _WorkspaceTile(
                        initial: org.name.isNotEmpty ? org.name[0].toUpperCase() : 'B',
                        title: org.name,
                        subtitle:
                            '${WorkspaceLabels.businessRole(l10n, org.businessType)} · ${WorkspaceLabels.memberRole(l10n, org.role)}',
                        status: org.status,
                        isCurrent: current?.id == org.id,
                        onTap: current?.id == org.id || ws.isLoading ? null : () => _switchTo(context, ws, org.id),
                      ),
                    ],
                  ],
                  const SizedBox(height: 14),

                  _ActionRow(
                    icon: Icons.add_business_outlined,
                    title: l10n.workspaceAddBusiness,
                    subtitle: ws.reachedBusinessLimit
                        ? l10n.translate('businessLimitReached', {'max': ws.maxOwnedBusinesses})
                        : null,
                    onTap: ws.reachedBusinessLimit
                        ? null
                        : () {
                            Navigator.pop(context);
                            Navigator.push(context, MaterialPageRoute(builder: (_) => const CreateWorkspaceScreen()));
                          },
                  ),
                  if (current != null)
                    _ActionRow(
                      icon: Icons.group_outlined,
                      title: l10n.workspaceTeamAndRoles,
                      subtitle: l10n.workspaceTeamAndRolesSubtitle,
                      onTap: () {
                        Navigator.pop(context);
                        Navigator.push(
                          context,
                          MaterialPageRoute(builder: (_) => TeamSeatsScreen(organization: current)),
                        );
                      },
                    ),
                  const SizedBox(height: 12),
                  Text(
                    l10n.workspaceSwitcherFootnote,
                    style: TextStyle(fontSize: 12, color: fira.mutedText, height: 1.4),
                  ),
                  const SizedBox(height: 8),
                ],
              ),
            ),
          ),
        );
      },
    );
  }
}

class _WorkspaceTile extends StatelessWidget {
  const _WorkspaceTile({
    required this.initial,
    required this.title,
    required this.subtitle,
    required this.isCurrent,
    required this.onTap,
    this.status,
  });

  /// Null draws the Personal icon.
  final String? initial;
  final String title;
  final String subtitle;
  final bool isCurrent;
  final VoidCallback? onTap;
  final WorkspaceStatus? status;

  @override
  Widget build(BuildContext context) {
    final fira = context.fira;
    final divider = Theme.of(context).dividerColor;

    return Semantics(
      selected: isCurrent,
      button: true,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: isCurrent ? fira.brandPrimary : divider, width: isCurrent ? 2 : 1),
            color: isCurrent ? fira.brandTint : fira.card,
          ),
          child: Row(
            children: [
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  color: isCurrent ? fira.brandPrimary.withValues(alpha: 0.12) : fira.chipUnselectedBg,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Center(
                  child: initial == null
                      ? Icon(Icons.person_outline, color: isCurrent ? fira.brandPrimary : fira.mutedText)
                      : Text(
                          initial!,
                          style: TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 18,
                            color: isCurrent ? fira.brandPrimary : fira.mutedText,
                          ),
                        ),
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: fira.onSurface),
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 2),
                    Text(
                      subtitle,
                      style: TextStyle(fontSize: 12, color: fira.mutedText),
                      overflow: TextOverflow.ellipsis,
                    ),
                  ],
                ),
              ),
              if (status != null) ...[
                const SizedBox(width: 8),
                WorkspaceStatusPill(status: status!),
              ],
              if (isCurrent) ...[
                const SizedBox(width: 8),
                Icon(Icons.check_circle, color: fira.brandPrimary, size: 22),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

class _ActionRow extends StatelessWidget {
  const _ActionRow({required this.icon, required this.title, this.subtitle, required this.onTap});

  final IconData icon;
  final String title;
  final String? subtitle;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final fira = context.fira;
    final enabled = onTap != null;
    return ListTile(
      contentPadding: EdgeInsets.zero,
      enabled: enabled,
      leading: Icon(icon, color: enabled ? fira.brandPrimary : fira.mutedText),
      title: Text(
        title,
        style: TextStyle(fontWeight: FontWeight.w600, color: enabled ? fira.brandPrimary : fira.mutedText),
      ),
      subtitle: subtitle == null ? null : Text(subtitle!, style: TextStyle(fontSize: 12, color: fira.mutedText)),
      trailing: enabled ? Icon(Icons.chevron_right, color: fira.mutedText) : null,
      onTap: onTap,
    );
  }
}

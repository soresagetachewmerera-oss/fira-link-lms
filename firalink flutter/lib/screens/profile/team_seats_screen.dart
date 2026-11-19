import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../l10n/app_localizations.dart';
import '../../models/organization_model.dart';
import '../../providers/workspace_provider.dart';
import '../../theme/fira_colors.dart';
import '../../widgets/fira_error_state.dart';
import '../../widgets/workspace_status_pill.dart';

/// Team and roles: read-only for members. Only the owner can remove a member (the server says
/// which rows can be removed). Adding members stays hidden until seats exist.
class TeamSeatsScreen extends StatefulWidget {
  final OrganizationModel organization;

  const TeamSeatsScreen({super.key, required this.organization});

  @override
  State<TeamSeatsScreen> createState() => _TeamSeatsScreenState();
}

class _TeamSeatsScreenState extends State<TeamSeatsScreen> {
  late Future<TeamRoster> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<TeamRoster> _load() => context.read<WorkspaceProvider>().loadTeam(widget.organization.id);

  void _reload() => setState(() => _future = _load());

  Future<void> _confirmRemove(OrganizationMemberModel member) async {
    final l10n = context.l10n;
    final fira = context.fira;
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(28)),
        title: Text(l10n.teamRemoveTitle),
        content: Text(l10n.translate('teamRemoveBody', {'name': member.name, 'business': widget.organization.name})),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: Text(l10n.cancel)),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: Text(l10n.remove, style: TextStyle(color: fira.danger)),
          ),
        ],
      ),
    );
    if (confirmed != true || !mounted) return;

    try {
      await context.read<WorkspaceProvider>().removeMember(widget.organization.id, member.userId);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(l10n.teamRemoved)));
      _reload();
    } catch (e) {
      if (mounted) showFiraActionError(context, e);
    }
  }

  @override
  Widget build(BuildContext context) {
    final fira = context.fira;
    final l10n = context.l10n;
    final divider = Theme.of(context).dividerColor;

    return Scaffold(
      backgroundColor: fira.surface,
      appBar: AppBar(
        title: Text(
          l10n.workspaceTeamAndRoles,
          style: TextStyle(color: fira.onSurface, fontWeight: FontWeight.bold, fontSize: 16),
        ),
        backgroundColor: fira.surface,
        elevation: 0,
        leading: IconButton(
            tooltip: MaterialLocalizations.of(context).backButtonTooltip,
            icon: Icon(Icons.arrow_back, color: fira.onSurface),
          onPressed: () => Navigator.pop(context),
        ),
      ),
      body: FutureBuilder<TeamRoster>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.hasError) {
            return FiraErrorState(error: snapshot.error, onRetry: _reload);
          }
          if (!snapshot.hasData) {
            return Center(child: CircularProgressIndicator(color: fira.brandPrimary));
          }
          final roster = snapshot.data!;
          final others = roster.members.where((m) => m.role != 'owner').length;

          return RefreshIndicator(
            onRefresh: () async => _reload(),
            child: ListView(
              padding: const EdgeInsets.all(20),
              children: [
                Text(
                  widget.organization.name,
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: fira.onSurface),
                ),
                const SizedBox(height: 4),
                if (!roster.viewerIsOwner)
                  Text(l10n.teamReadOnlyNotice, style: TextStyle(fontSize: 12, color: fira.mutedText)),
                const SizedBox(height: 16),
                for (final member in roster.members) ...[
                  Container(
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: fira.card,
                      border: Border.all(color: divider),
                      borderRadius: BorderRadius.circular(22),
                    ),
                    child: Row(
                      children: [
                        CircleAvatar(
                          backgroundColor: fira.brandTint,
                          child: Text(
                            member.name.isNotEmpty ? member.name[0].toUpperCase() : 'U',
                            style: TextStyle(color: fira.brandPrimary, fontWeight: FontWeight.bold),
                          ),
                        ),
                        const SizedBox(width: 14),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                member.name,
                                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: fira.onSurface),
                                overflow: TextOverflow.ellipsis,
                              ),
                              const SizedBox(height: 2),
                              Text(
                                member.email.isNotEmpty ? member.email : (member.phone ?? ''),
                                style: TextStyle(fontSize: 12, color: fira.mutedText),
                                overflow: TextOverflow.ellipsis,
                              ),
                            ],
                          ),
                        ),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                          decoration: BoxDecoration(
                            color: member.role == 'owner' ? fira.brandTint : fira.chipUnselectedBg,
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Text(
                            WorkspaceLabels.memberRole(l10n, member.role),
                            style: TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.bold,
                              color: member.role == 'owner' ? fira.brandPrimary : fira.mutedText,
                            ),
                          ),
                        ),
                        if (member.canRemove) ...[
                          const SizedBox(width: 4),
                          IconButton(
                            tooltip: l10n.remove,
                            icon: Icon(Icons.person_remove_outlined, color: fira.danger, size: 20),
                            onPressed: () => _confirmRemove(member),
                          ),
                        ],
                      ],
                    ),
                  ),
                  const SizedBox(height: 12),
                ],
                if (others == 0)
                  Padding(
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    child: Center(
                      child: Text(l10n.teamEmpty, style: TextStyle(color: fira.mutedText)),
                    ),
                  ),
              ],
            ),
          );
        },
      ),
    );
  }
}

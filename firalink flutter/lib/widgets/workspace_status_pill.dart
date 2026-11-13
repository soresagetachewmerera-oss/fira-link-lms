import 'package:flutter/material.dart';

import '../l10n/app_localizations.dart';
import '../models/organization_model.dart';
import '../theme/fira_colors.dart';

/// Localized labels for workspace values that come from the server.
class WorkspaceLabels {
  const WorkspaceLabels._();

  static String status(AppLocalizations l10n, WorkspaceStatus status) {
    switch (status) {
      case WorkspaceStatus.inReview:
        return l10n.workspaceStatusInReview;
      case WorkspaceStatus.verified:
        return l10n.workspaceStatusVerified;
      case WorkspaceStatus.needsChanges:
        return l10n.workspaceStatusNeedsChanges;
      case WorkspaceStatus.expired:
        return l10n.workspaceStatusExpired;
      case WorkspaceStatus.suspended:
        return l10n.workspaceStatusSuspended;
      case WorkspaceStatus.notStarted:
        return l10n.workspaceStatusNotStarted;
    }
  }

  static String businessRole(AppLocalizations l10n, String role) {
    switch (role) {
      case 'exporter':
        return l10n.businessRoleExporter;
      case 'supplier':
        return l10n.businessRoleSupplier;
      case 'broker':
        return l10n.businessRoleBroker;
      case 'buyer':
        return l10n.businessRoleBuyer;
      default:
        return role;
    }
  }

  static String memberRole(AppLocalizations l10n, String? role) {
    switch (role) {
      case 'owner':
        return l10n.memberRoleOwner;
      case 'manager':
        return l10n.memberRoleManager;
      default:
        return l10n.memberRoleStaff;
    }
  }

  /// The value list comes from the server; unknown values fall back to the server label.
  static String entityType(AppLocalizations l10n, String value, {String? fallback}) {
    switch (value) {
      case 'sole_proprietorship':
        return l10n.entitySoleProprietorship;
      case 'private_limited_company':
        return l10n.entityPrivateLimitedCompany;
      case 'share_company':
        return l10n.entityShareCompany;
      case 'cooperative':
        return l10n.entityCooperative;
      case 'cooperative_union':
        return l10n.entityCooperativeUnion;
      case 'partnership':
        return l10n.entityPartnership;
      case 'state_owned_enterprise':
        return l10n.entityStateOwnedEnterprise;
      case 'foreign_company_branch':
        return l10n.entityForeignCompanyBranch;
      case 'association':
        return l10n.entityAssociation;
      case 'other':
        return l10n.entityOther;
      default:
        return fallback ?? value;
    }
  }
}

/// Workspace status from the server, drawn only from theme tokens.
/// In review (pending) is purple on lavender, never amber or green.
class WorkspaceStatusPill extends StatelessWidget {
  const WorkspaceStatusPill({super.key, required this.status});

  final WorkspaceStatus status;

  @override
  Widget build(BuildContext context) {
    final fira = context.fira;
    final (Color fg, Color bg) = switch (status) {
      WorkspaceStatus.inReview => (fira.pending, fira.brandTint),
      WorkspaceStatus.verified => (fira.success, fira.success.withValues(alpha: 0.12)),
      WorkspaceStatus.needsChanges || WorkspaceStatus.suspended => (fira.danger, fira.danger.withValues(alpha: 0.10)),
      WorkspaceStatus.expired || WorkspaceStatus.notStarted => (fira.mutedText, fira.chipUnselectedBg),
    };

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(12)),
      child: Text(
        WorkspaceLabels.status(context.l10n, status),
        style: TextStyle(color: fg, fontSize: 11, fontWeight: FontWeight.w700),
      ),
    );
  }
}

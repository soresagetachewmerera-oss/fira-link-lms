import 'package:flutter/material.dart';

import '../../l10n/app_localizations.dart';
import '../../models/verification_model.dart';
import '../../services/verification_service.dart';
import '../../theme/fira_colors.dart';
import '../../widgets/workspace_status_pill.dart';
import '../marketplace/organization_verification_screen.dart';

/// Profile "Verification" row for the active business (flow S-Profile): "Tier 1 · 2 documents left"
/// with the status, opening the verification documents. Shown only in a business workspace.
class BusinessVerificationTile extends StatefulWidget {
  const BusinessVerificationTile({super.key, required this.organizationId, this.service});

  final int organizationId;
  final VerificationService? service;

  @override
  State<BusinessVerificationTile> createState() => _BusinessVerificationTileState();
}

class _BusinessVerificationTileState extends State<BusinessVerificationTile> {
  late final VerificationService _service = widget.service ?? VerificationService();
  VerificationDossier? _dossier;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void didUpdateWidget(covariant BusinessVerificationTile old) {
    super.didUpdateWidget(old);
    if (old.organizationId != widget.organizationId) {
      setState(() => _dossier = null);
      _load();
    }
  }

  Future<void> _load() async {
    try {
      final d = await _service.getDossier();
      if (mounted) setState(() => _dossier = d);
    } catch (_) {
      // The row still opens the verification screen, which shows the error with a retry.
    }
  }

  /// The first tier that is not complete, and how many of its documents are not approved yet.
  (int, int)? _progress(VerificationDossier d) {
    for (final t in d.tiers) {
      final left = t.documents.where((doc) => doc.state != 'approved').length;
      if (t.documents.isNotEmpty && left > 0) return (t.tier, left);
    }
    return null;
  }

  Future<void> _open() async {
    await Navigator.of(context).push(MaterialPageRoute(builder: (_) => OrganizationVerificationScreen(service: _service)));
    if (mounted) _load();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final fira = context.fira;
    final d = _dossier;
    final progress = d == null ? null : _progress(d);
    final subtitle = d == null
        ? l10n.verifyOpenDocuments
        : progress == null
            ? l10n.translate('sellerVerifiedTier', {'tier': d.tier})
            : l10n.translate('verifyDocumentsLeft', {'tier': progress.$1, 'n': progress.$2});

    return Card(
      key: const Key('profile-verification'),
      margin: EdgeInsets.zero,
      child: ListTile(
        onTap: _open,
        leading: Icon(Icons.verified_user_outlined, color: fira.brandPrimary),
        title: Text(l10n.verifyTitle, style: const TextStyle(fontWeight: FontWeight.w700)),
        subtitle: Text(subtitle),
        trailing: Row(mainAxisSize: MainAxisSize.min, children: [
          if (d != null) WorkspaceStatusPill(status: d.status),
          const Icon(Icons.chevron_right),
        ]),
      ),
    );
  }
}

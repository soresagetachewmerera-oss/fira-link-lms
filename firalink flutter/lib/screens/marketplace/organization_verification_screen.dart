import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';
import 'package:share_plus/share_plus.dart';

import '../../l10n/app_localizations.dart';
import '../../models/verification_model.dart';
import '../../services/verification_service.dart';
import '../../theme/fira_colors.dart';
import '../../widgets/fira_error_state.dart';
import '../../widgets/verification_labels.dart';
import '../../widgets/workspace_status_pill.dart';
import 'document_capture_screen.dart';
import 'public_seller_verification_screen.dart';

/// Verification dossier for the active business: tiers, per-document review states and the
/// public page. Tier and states come from the server; nothing here can change them.
class OrganizationVerificationScreen extends StatefulWidget {
  const OrganizationVerificationScreen({super.key, this.service});

  final VerificationService? service;

  @override
  State<OrganizationVerificationScreen> createState() => _OrganizationVerificationScreenState();
}

class _OrganizationVerificationScreenState extends State<OrganizationVerificationScreen> {
  late final VerificationService _service = widget.service ?? VerificationService();
  VerificationDossier? _dossier;
  Object? _error;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final d = await _service.getDossier();
      if (!mounted) return;
      setState(() {
        _dossier = d;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e;
        _loading = false;
      });
    }
  }

  Future<void> _open(DossierDocument doc) async {
    final sent = await Navigator.of(context).push<bool>(
      MaterialPageRoute(
        builder: (_) => DocumentCaptureScreen(
          document: doc,
          expectedReviewDays: _dossier?.expectedReviewDays ?? 3,
          service: _service,
        ),
      ),
    );
    if (sent == true) _load();
  }

  Future<void> _withdraw(DossierDocument doc) async {
    final l10n = context.l10n;
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        content: Text(l10n.verifyWithdrawConfirm),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: Text(l10n.cancel)),
          TextButton(key: const Key('confirm-withdraw'), onPressed: () => Navigator.pop(ctx, true), child: Text(l10n.verifyWithdraw)),
        ],
      ),
    );
    if (ok != true || !mounted) return;
    try {
      await _service.withdrawDocument(doc.documentId!);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(l10n.verifyWithdrawn)));
      _load();
    } catch (e) {
      if (mounted) showFiraActionError(context, e);
    }
  }

  void _preview(VerificationDossier d) {
    Navigator.of(context).push(
      MaterialPageRoute(builder: (_) => PublicSellerVerificationScreen(firaId: d.firaId, service: _service)),
    );
  }

  Future<void> _share(VerificationDossier d) async {
    final text = context.l10n.translate('verifyShareText', {'name': d.businessName, 'url': d.publicUrl});
    await SharePlus.instance.share(ShareParams(text: text));
  }

  String _tierTitle(AppLocalizations l10n, VerificationDossier d, DossierTier t) {
    if (t.tier == 1) return l10n.verifyTier1Title;
    if (t.tier == 2) return d.businessRole == 'exporter' ? l10n.verifyTier2ExportTitle : l10n.verifyTier2OperationsTitle;
    return l10n.verifyTier3Title;
  }

  String? _subtitle(BuildContext context, DossierDocument doc) {
    final l10n = context.l10n;
    String date(DateTime? x) => VerificationLabels.date(context, x);
    switch (doc.state) {
      case 'approved':
        return doc.expiresAt != null ? l10n.translate('verifyDocValidUntil', {'date': date(doc.expiresAt)}) : l10n.verifyDocNoExpiry;
      case 'in_review':
        final submitted = l10n.translate('verifyDocSubmitted', {'date': date(doc.submittedAt)});
        return doc.approvedCopyCounts ? '$submitted\n${l10n.verifyDocOldCopyCounts}' : submitted;
      case 'needs_changes':
      case 'rejected':
        return VerificationLabels.reason(l10n, doc.reasonCode, doc.reason);
      case 'expired':
        return l10n.translate('verifyDocExpiredOn', {'date': date(doc.expiresAt)});
      default:
        return VerificationLabels.hint(l10n, doc.type);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    return Scaffold(
      appBar: AppBar(title: Text(l10n.verifyTitle)),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? FiraErrorState(error: _error, onRetry: _load)
              : RefreshIndicator(onRefresh: _load, child: _body(context, _dossier!)),
    );
  }

  Widget _body(BuildContext context, VerificationDossier d) {
    final l10n = context.l10n;
    final fira = context.fira;
    final nextTier = d.tier < d.maxTier ? d.tier + 1 : d.tier;
    final progress = d.tier >= d.maxTier
        ? l10n.translate('verifyTierReached', {'tier': d.tier, 'max': d.maxTier})
        : l10n.translate('verifyTierProgress', {'tier': nextTier, 'max': d.maxTier});
    final fix = d.canManage ? d.firstToFix : null;

    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.fromLTRB(20, 16, 20, 32),
      children: [
        Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        '${d.businessName} · ${WorkspaceLabels.businessRole(l10n, d.businessRole)}',
                        style: TextStyle(color: fira.mutedText),
                      ),
                    ),
                    WorkspaceStatusPill(status: d.status),
                  ],
                ),
                const SizedBox(height: 8),
                Text(progress, style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700)),
                const SizedBox(height: 4),
                Text(
                  l10n.translate('verifyCounts', {
                    'approved': d.counts['approved'] ?? 0,
                    'inReview': d.counts['in_review'] ?? 0,
                    'needsChanges': d.counts['needs_changes'] ?? 0,
                    'notUploaded': d.counts['not_uploaded'] ?? 0,
                  }),
                  style: TextStyle(color: fira.mutedText),
                ),
                const SizedBox(height: 8),
                Text(l10n.translate('verifyExpectedReview', {'days': d.expectedReviewDays}), style: TextStyle(color: fira.mutedText)),
                if (!d.canManage) ...[
                  const SizedBox(height: 8),
                  Text(l10n.verifyNotManager, style: TextStyle(color: fira.pending)),
                ],
              ],
            ),
          ),
        ),
        for (final t in d.tiers) ...[
          const SizedBox(height: 20),
          Text(_tierTitle(l10n, d, t), style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
          if (t.beingConfirmed) ...[
            const SizedBox(height: 4),
            Text(l10n.verifyTier2BeingConfirmed, style: TextStyle(color: fira.mutedText)),
          ],
          if (t.tier == 3)
            Card(
              child: ListTile(
                title: Text(t.activeUntil != null
                    ? l10n.translate('verifyTier3ActiveUntil', {'date': VerificationLabels.date(context, t.activeUntil)})
                    : l10n.verifyTier3After),
                subtitle: Text(l10n.verifyTier3Body),
              ),
            ),
          for (final doc in t.documents)
            Card(
              child: ListTile(
                key: Key('doc-${doc.type}'),
                title: Text(VerificationLabels.docType(l10n, doc.type)),
                subtitle: _subtitle(context, doc) == null ? null : Text(_subtitle(context, doc)!),
                trailing: DocumentStatePill(state: doc.state),
                onTap: !d.canManage
                    ? null
                    : doc.state == 'in_review'
                        ? (doc.documentId != null ? () => _withdraw(doc) : null)
                        : () => _open(doc),
              ),
            ),
        ],
        const SizedBox(height: 20),
        Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(l10n.verifyBuyersSeeTitle, style: const TextStyle(fontWeight: FontWeight.w700)),
                const SizedBox(height: 4),
                Text(l10n.verifyBuyersSeeBody, style: TextStyle(color: fira.mutedText)),
                if (d.hasPublicPage) ...[
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      if (d.qrSvgUrl != null)
                        SvgPicture.network(d.qrSvgUrl!, width: 96, height: 96, placeholderBuilder: (_) => const SizedBox(width: 96, height: 96)),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(l10n.verifyFiraId, style: TextStyle(color: fira.mutedText)),
                            SelectableText(d.firaId!, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 18)),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Wrap(
                    spacing: 12,
                    runSpacing: 8,
                    children: [
                      OutlinedButton(onPressed: () => _preview(d), child: Text(l10n.verifyPreview)),
                      FilledButton.icon(
                        key: const Key('share-verification-link'),
                        onPressed: () => _share(d),
                        icon: const Icon(Icons.share_outlined),
                        label: Text(l10n.verifyShareLink),
                      ),
                    ],
                  ),
                ],
                const SizedBox(height: 12),
                Text(
                  d.reviewedAt != null
                      ? l10n.translate('verifyMeaning', {'date': VerificationLabels.date(context, d.reviewedAt)})
                      : l10n.verifyMeaningNoDate,
                  style: TextStyle(color: fira.mutedText, fontSize: 13),
                ),
              ],
            ),
          ),
        ),
        if (fix != null) ...[
          const SizedBox(height: 20),
          FilledButton(
            key: const Key('fix-document'),
            onPressed: () => _open(fix),
            child: Text(l10n.translate('verifyFix', {'document': VerificationLabels.docType(l10n, fix.type)})),
          ),
        ],
      ],
    );
  }
}

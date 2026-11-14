import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';

import '../../l10n/app_localizations.dart';
import '../../models/verification_model.dart';
import '../../services/api_exception.dart';
import '../../services/verification_service.dart';
import '../../theme/fira_colors.dart';
import '../../widgets/fira_error_state.dart';
import '../../widgets/verification_labels.dart';
import '../../widgets/workspace_status_pill.dart';
import '../trust_safety/report_modal.dart';

/// Public seller check by Fira ID only (same data as the web page /verify/{firaId}).
/// Never shows TIN, ID numbers, phone numbers or documents; the server does not send them.
class PublicSellerVerificationScreen extends StatefulWidget {
  const PublicSellerVerificationScreen({super.key, this.firaId, this.service});

  final String? firaId;
  final VerificationService? service;

  @override
  State<PublicSellerVerificationScreen> createState() => _PublicSellerVerificationScreenState();
}

class _PublicSellerVerificationScreenState extends State<PublicSellerVerificationScreen> {
  late final VerificationService _service = widget.service ?? VerificationService();
  late final TextEditingController _id = TextEditingController(text: widget.firaId ?? '');
  PublicSeller? _seller;
  Object? _error;
  bool _notFound = false;
  bool _loading = false;

  @override
  void initState() {
    super.initState();
    if ((widget.firaId ?? '').trim().isNotEmpty) _lookup();
  }

  @override
  void dispose() {
    _id.dispose();
    super.dispose();
  }

  Future<void> _lookup() async {
    final id = _id.text.trim();
    if (id.isEmpty) return;
    setState(() {
      _loading = true;
      _error = null;
      _notFound = false;
    });
    try {
      final s = await _service.getPublicSeller(id);
      if (!mounted) return;
      setState(() {
        _seller = s;
        _loading = false;
      });
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() {
        _seller = null;
        _notFound = e.statusCode == 404;
        _error = _notFound ? null : e;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _seller = null;
        _error = e;
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    return Scaffold(
      appBar: AppBar(title: Text(l10n.sellerCheckTitle)),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? FiraErrorState(error: _error, onRetry: _lookup)
              : _seller != null
                  ? _profile(context, _seller!)
                  : _search(context),
    );
  }

  Widget _search(BuildContext context) {
    final l10n = context.l10n;
    return ListView(
      padding: const EdgeInsets.all(20),
      children: [
        if (_notFound) ...[
          Text(l10n.sellerCheckNotFound, key: const Key('seller-not-found'), style: TextStyle(color: context.fira.danger)),
          const SizedBox(height: 12),
        ],
        TextField(
          controller: _id,
          textCapitalization: TextCapitalization.characters,
          maxLength: 20,
          decoration: InputDecoration(labelText: l10n.sellerCheckEnterId, hintText: l10n.sellerCheckIdHint),
          onSubmitted: (_) => _lookup(),
        ),
        const SizedBox(height: 8),
        FilledButton(onPressed: _lookup, child: Text(l10n.sellerCheckButton)),
      ],
    );
  }

  Widget _profile(BuildContext context, PublicSeller s) {
    final l10n = context.l10n;
    final fira = context.fira;
    String date(DateTime? d) => VerificationLabels.date(context, d);
    final place = [if ((s.city ?? '').isNotEmpty) s.city!, if ((s.country ?? '').isNotEmpty) s.country!].join(' · ');
    final verified = s.status == 'verified';

    final (String badge, Color fg, Color bg) = switch (s.status) {
      'verified' => (l10n.translate('sellerVerifiedTier', {'tier': s.tier ?? 1}), fira.success, fira.success.withValues(alpha: 0.12)),
      'suspended' => (l10n.sellerSuspended, fira.danger, fira.danger.withValues(alpha: 0.10)),
      _ => (l10n.sellerExpired, fira.mutedText, fira.chipUnselectedBg),
    };

    return RefreshIndicator(
      onRefresh: _lookup,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 32),
        children: [
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(s.name, style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w700)),
                        const SizedBox(height: 4),
                        Text(
                          [WorkspaceLabels.businessRole(l10n, s.businessRole), if (place.isNotEmpty) place].join(' · '),
                          style: TextStyle(color: fira.mutedText),
                        ),
                        const SizedBox(height: 4),
                        Text('${l10n.verifyFiraId} ${s.firaId}', style: TextStyle(color: fira.mutedText)),
                        const SizedBox(height: 12),
                        Container(
                          key: const Key('seller-status'),
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                          decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(12)),
                          child: Text(badge, style: TextStyle(color: fg, fontWeight: FontWeight.w700)),
                        ),
                        if (verified) ...[
                          const SizedBox(height: 6),
                          Text(
                            [
                              l10n.translate('sellerReviewed', {'date': date(s.reviewedAt)}),
                              if (s.validUntil != null) l10n.translate('verifyDocValidUntil', {'date': date(s.validUntil)}),
                            ].join(' · '),
                            style: TextStyle(color: fira.mutedText, fontSize: 13),
                          ),
                        ],
                      ],
                    ),
                  ),
                  if (s.qrSvgUrl.isNotEmpty)
                    SvgPicture.network(s.qrSvgUrl, width: 96, height: 96, placeholderBuilder: (_) => const SizedBox(width: 96, height: 96)),
                ],
              ),
            ),
          ),
          if (verified) ...[
            const SizedBox(height: 16),
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(l10n.sellerWhatWeChecked, style: const TextStyle(fontWeight: FontWeight.w700)),
                    for (final item in s.checked)
                      ListTile(
                        contentPadding: EdgeInsets.zero,
                        leading: Icon(Icons.check_circle_outline, color: fira.success),
                        title: Text(VerificationLabels.check(l10n, item.check) ?? VerificationLabels.docType(l10n, item.documentType)),
                        subtitle: Text([
                          l10n.translate('sellerCheckedOn', {'date': date(item.checkedAt)}),
                          if (item.validUntil != null) l10n.translate('verifyDocValidUntil', {'date': date(item.validUntil)}),
                        ].join(' · ')),
                      ),
                    if (s.vetted) Text(l10n.sellerVetted, style: TextStyle(color: fira.mutedText)),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(l10n.sellerWhatWeDidNotCheck, style: const TextStyle(fontWeight: FontWeight.w700)),
                    const SizedBox(height: 4),
                    Text(l10n.sellerNotCheckedBody),
                    const SizedBox(height: 8),
                    Text(
                      s.reviewedAt != null ? l10n.translate('verifyMeaning', {'date': date(s.reviewedAt)}) : l10n.verifyMeaningNoDate,
                      style: TextStyle(color: fira.mutedText, fontSize: 13),
                    ),
                  ],
                ),
              ),
            ),
          ],
          const SizedBox(height: 16),
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
                children: [
                  Expanded(
                    child: s.confirmedDeals > 0
                        ? Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text('${s.confirmedDeals}', style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 20)),
                              Text(l10n.sellerConfirmedDeals, style: TextStyle(color: fira.mutedText)),
                            ],
                          )
                        : Text(l10n.sellerNoDeals, key: const Key('seller-no-deals'), style: TextStyle(color: fira.mutedText)),
                  ),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      Text('${s.memberSince}', style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 20)),
                      Text(l10n.sellerMemberSince, style: TextStyle(color: fira.mutedText)),
                    ],
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),
          Card(
            color: fira.brandTint,
            child: ListTile(
              leading: Icon(Icons.shield_outlined, color: fira.brandPrimary),
              title: Text(l10n.sellerNeverPayOutside, style: const TextStyle(fontWeight: FontWeight.w700)),
              subtitle: Text(l10n.sellerNeverAskMoney),
            ),
          ),
          const SizedBox(height: 16),
          OutlinedButton.icon(
            onPressed: () => ReportModal.show(context, targetTitle: '${s.name} (${s.firaId})'),
            icon: const Icon(Icons.flag_outlined),
            label: Text(l10n.sellerReport),
          ),
        ],
      ),
    );
  }
}

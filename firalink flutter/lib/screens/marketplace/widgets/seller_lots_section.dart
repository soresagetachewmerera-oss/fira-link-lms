import 'package:flutter/material.dart';

import '../../../l10n/app_localizations.dart';
import '../../../models/lot_model.dart';
import '../../../services/lot_service.dart';
import '../../../theme/fira_colors.dart';
import '../../../widgets/fira_error_state.dart';
import '../../../widgets/verification_labels.dart';
import '../../../widgets/workspace_status_pill.dart';
import '../create_lot_screen.dart';
import '../lot_evidence_screen.dart';

/// Seller home: the active business's lots, the "hidden until Tier 1" banner and the
/// "renew evidence" to-do. Everything shown comes from the server.
class SellerLotsSection extends StatefulWidget {
  const SellerLotsSection({super.key, this.service, this.now});

  final LotService? service;
  final DateTime Function()? now;

  @override
  State<SellerLotsSection> createState() => _SellerLotsSectionState();
}

class _SellerLotsSectionState extends State<SellerLotsSection> {
  static const _renewWithinDays = 7;

  late final LotService _service = widget.service ?? LotService();
  SellerLots? _data;
  Object? _error;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = _data == null;
      _error = null;
    });
    try {
      final data = await _service.getLots();
      if (!mounted) return;
      setState(() {
        _data = data;
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

  Future<void> _open(Widget screen) async {
    await Navigator.of(context).push(MaterialPageRoute(builder: (_) => screen));
    _load();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final fira = context.fira;
    if (_loading) return const Center(child: Padding(padding: EdgeInsets.all(24), child: CircularProgressIndicator()));
    if (_error != null) return FiraErrorState(error: _error, onRetry: _load);

    final data = _data!;
    final now = (widget.now ?? DateTime.now)();
    final expiring = data.lots.where((l) {
      final days = l.daysUntilEvidenceExpires(now);
      return days != null && days >= 0 && days <= _renewWithinDays;
    }).toList();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        if (!data.visibleToBuyers)
          Card(
            key: const Key('lots-hidden-banner'),
            color: fira.brandTint,
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(l10n.lotsHiddenTitle, style: TextStyle(color: fira.onSurface, fontWeight: FontWeight.w700)),
                        const SizedBox(height: 2),
                        Text(l10n.lotsHiddenBody, style: TextStyle(color: fira.mutedText)),
                      ],
                    ),
                  ),
                  WorkspaceStatusPill(status: data.businessStatus),
                ],
              ),
            ),
          ),
        if (expiring.isNotEmpty) ...[
          const SizedBox(height: 12),
          Text(l10n.lotsToDo, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
          for (final lot in expiring)
            Card(
              key: Key('renew-${lot.id}'),
              child: ListTile(
                title: Text(l10n.translate('lotsRenewEvidence', {'lot': lot.title})),
                subtitle: Text(l10n.translate('lotsExpiresInDays', {'days': lot.daysUntilEvidenceExpires(now)})),
                trailing: Text(l10n.lotsExpiring, style: TextStyle(color: fira.pending, fontWeight: FontWeight.w700)),
                onTap: data.canManage ? () => _open(LotEvidenceScreen(lotId: lot.id, service: _service)) : null,
              ),
            ),
        ],
        const SizedBox(height: 16),
        Row(
          children: [
            Expanded(child: Text(l10n.lotsMyLots, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 18))),
            if (data.canManage)
              FilledButton.icon(
                key: const Key('add-lot'),
                onPressed: () => _open(CreateLotScreen(service: _service)),
                icon: const Icon(Icons.add),
                label: Text(l10n.lotsAddLot),
              ),
          ],
        ),
        const SizedBox(height: 8),
        if (data.lots.isEmpty)
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 24),
            child: Text(l10n.lotsEmpty, textAlign: TextAlign.center, style: TextStyle(color: fira.mutedText)),
          )
        else
          for (final lot in data.lots)
            Card(
              key: Key('lot-${lot.id}'),
              child: ListTile(
                title: Text(lot.title, style: const TextStyle(fontWeight: FontWeight.w600)),
                subtitle: Text(lot.isDraft
                    ? l10n.lotStatusDraft
                    : [
                        l10n.translate('lotsVolumePrice', {
                          'bags': lot.totalQuantityBags ?? 0,
                          'price': lot.pricePerLb?.toStringAsFixed(2) ?? '–',
                        }),
                        if (lot.status != 'active') VerificationLabels.lotStatus(l10n, lot.status),
                      ].join(' · ')),
                trailing: LotLevelChip(level: lot.evidenceLevel),
                onTap: data.canManage ? () => _open(CreateLotScreen(lotId: lot.id, service: _service)) : null,
              ),
            ),
      ],
    );
  }
}

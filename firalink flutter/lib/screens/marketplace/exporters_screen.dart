import 'package:flutter/material.dart';

import '../../l10n/app_localizations.dart';
import '../../models/trade_model.dart';
import '../../router/app_router.dart';
import '../../services/trade_service.dart';
import '../../theme/fira_colors.dart';
import '../../widgets/fira_error_state.dart';
import '../../widgets/trade_labels.dart';
import '../../widgets/verification_labels.dart';
import '../../widgets/workspace_status_pill.dart';
import '../chat/trade_chat_screen.dart';
import 'create_rfq_screen.dart';
import 'public_seller_verification_screen.dart';

/// Exporters tab: verified seller businesses only (signed-in users, from the server).
class ExportersScreen extends StatefulWidget {
  const ExportersScreen({super.key, this.service});

  final TradeService? service;

  @override
  State<ExportersScreen> createState() => _ExportersScreenState();
}

class _ExportersScreenState extends State<ExportersScreen> {
  late final TradeService _service = widget.service ?? TradeService();
  final _search = TextEditingController();
  List<ExporterCard>? _items;
  Object? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _search.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() => _error = null);
    try {
      final items = await _service.exporters(search: _search.text.trim());
      if (mounted) setState(() => _items = items);
    } catch (e) {
      if (mounted) setState(() => _error = e);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final fira = context.fira;

    return Scaffold(
      appBar: AppBar(title: Text(l10n.marketExporters)),
      body: _error != null
          ? FiraErrorState(error: _error, onRetry: _load)
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.fromLTRB(20, 12, 20, 32),
                children: [
                  TextField(
                    controller: _search,
                    textInputAction: TextInputAction.search,
                    decoration: InputDecoration(labelText: l10n.exportersSearch, prefixIcon: const Icon(Icons.search)),
                    onSubmitted: (_) => _load(),
                  ),
                  const SizedBox(height: 12),
                  if (_items == null) const Center(child: Padding(padding: EdgeInsets.all(24), child: CircularProgressIndicator())),
                  if (_items != null && _items!.isEmpty)
                    Padding(
                      padding: const EdgeInsets.symmetric(vertical: 32),
                      child: Text(l10n.exportersEmpty, textAlign: TextAlign.center, style: TextStyle(color: fira.mutedText)),
                    ),
                  for (final e in _items ?? const <ExporterCard>[])
                    Card(
                      key: Key('exporter-${e.id}'),
                      child: ListTile(
                        onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => ExporterProfileScreen(organizationId: e.id, service: _service))),
                        title: Text(e.name, style: const TextStyle(fontWeight: FontWeight.w600)),
                        subtitle: Text([
                          WorkspaceLabels.businessRole(l10n, e.businessRole),
                          if ((e.city ?? '').isNotEmpty) e.city!,
                          '${e.activeLots} ${l10n.exporterActiveLots.toLowerCase()}',
                        ].join(' · ')),
                        trailing: Text(l10n.translate('sellerVerifiedTier', {'tier': e.tier}), style: TextStyle(color: fira.success, fontSize: 12, fontWeight: FontWeight.w600)),
                      ),
                    ),
                ],
              ),
            ),
    );
  }
}

/// S-114 Exporter profile: public fields only. "See what we checked" opens the public seller page.
class ExporterProfileScreen extends StatefulWidget {
  const ExporterProfileScreen({super.key, required this.organizationId, this.service});

  final int organizationId;
  final TradeService? service;

  @override
  State<ExporterProfileScreen> createState() => _ExporterProfileScreenState();
}

class _ExporterProfileScreenState extends State<ExporterProfileScreen> {
  late final TradeService _service = widget.service ?? TradeService();
  ExporterProfile? _profile;
  Object? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _error = null);
    try {
      final p = await _service.exporter(widget.organizationId);
      if (mounted) setState(() => _profile = p);
    } catch (e) {
      if (mounted) setState(() => _error = e);
    }
  }

  Widget _stat(BuildContext context, String value, String label) => Expanded(
        child: Column(children: [
          Text(value, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 18)),
          Text(label, textAlign: TextAlign.center, style: TextStyle(color: context.fira.mutedText, fontSize: 12)),
        ]),
      );

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final fira = context.fira;
    final p = _profile;

    return Scaffold(
      appBar: AppBar(title: Text(p?.card.name ?? l10n.marketExporters)),
      body: _error != null
          ? FiraErrorState(error: _error, onRetry: _load)
          : p == null
              ? const Center(child: CircularProgressIndicator())
              : ListView(
                  padding: const EdgeInsets.fromLTRB(20, 12, 20, 32),
                  children: [
                    Text(p.card.name, style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w700)),
                    Text([
                      if ((p.card.city ?? '').isNotEmpty) p.card.city!,
                      if ((p.card.country ?? '').isNotEmpty) p.card.country!,
                      l10n.translate('exporterMemberSince', {'year': p.card.memberSince}),
                    ].join(' · '), style: TextStyle(color: fira.mutedText)),
                    const SizedBox(height: 8),
                    Wrap(spacing: 8, crossAxisAlignment: WrapCrossAlignment.center, children: [
                      Text(l10n.translate('sellerVerifiedTier', {'tier': p.card.tier}), style: TextStyle(color: fira.success, fontWeight: FontWeight.w700)),
                      Chip(label: Text(WorkspaceLabels.businessRole(l10n, p.card.businessRole))),
                    ]),
                    if (p.card.firaId != null)
                      Card(
                        child: ListTile(
                          key: const Key('see-what-we-checked'),
                          title: Text(l10n.exporterSeeChecked),
                          subtitle: Text(l10n.exporterSeeCheckedBody),
                          trailing: const Icon(Icons.chevron_right),
                          onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => PublicSellerVerificationScreen(firaId: p.card.firaId))),
                        ),
                      ),
                    const SizedBox(height: 8),
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Row(children: [
                          _stat(context, '${p.card.activeLots}', l10n.exporterActiveLots),
                          _stat(context, '${p.confirmedDeals}', l10n.exporterDeals),
                          if (p.usualReplyHours != null)
                            _stat(context, l10n.translate('exporterReplyHours', {'n': p.usualReplyHours}), l10n.exporterUsualReply),
                        ]),
                      ),
                    ),
                    if ((p.about ?? '').isNotEmpty) ...[
                      const SizedBox(height: 16),
                      Text(l10n.exporterAbout, style: const TextStyle(fontWeight: FontWeight.w700)),
                      const SizedBox(height: 4),
                      Text(p.about!),
                      Text(l10n.exporterAboutNote, style: TextStyle(color: fira.mutedText, fontSize: 12)),
                    ],
                    const SizedBox(height: 16),
                    Text(l10n.exporterActiveLots, style: const TextStyle(fontWeight: FontWeight.w700)),
                    for (final lot in p.lots)
                      Card(
                        child: ListTile(
                          onTap: () => Navigator.pushNamed(context, AppRouter.coffeeListingDetail, arguments: lot['slug'] ?? lot['id']),
                          title: Text(lot['title']?.toString() ?? ''),
                          subtitle: Text([
                            lot['origin_region']?.toString() ?? '',
                            TradeLabels.process(l10n, lot['processing_method']?.toString()),
                            TradeLabels.grade(l10n, lot['grade']?.toString()),
                          ].where((s) => s.isNotEmpty).join(' · ')),
                          trailing: LotLevelChip(level: lot['evidence_level']?.toString() ?? 'claimed'),
                        ),
                      ),
                    const SizedBox(height: 16),
                    FilledButton(
                      key: const Key('exporter-request-quote'),
                      onPressed: () => Navigator.of(context).push(MaterialPageRoute(
                        builder: (_) => CreateRfqScreen(prefillListingId: p.lots.isNotEmpty ? (p.lots.first['id'] as num?)?.toInt() : null, service: _service),
                      )),
                      child: Text(l10n.exporterRequestQuote),
                    ),
                    const SizedBox(height: 8),
                    OutlinedButton.icon(
                      key: const Key('exporter-message-seller'),
                      onPressed: () => openSellerChat(context, sellerOrganizationId: widget.organizationId),
                      icon: const Icon(Icons.chat_bubble_outline),
                      label: Text(l10n.messageSeller),
                    ),
                  ],
                ),
    );
  }
}

import 'package:flutter/material.dart';

import '../../l10n/app_localizations.dart';
import '../../router/app_router.dart';
import '../../services/trade_service.dart';
import '../../theme/fira_colors.dart';
import '../../widgets/fira_error_state.dart';
import '../../widgets/trade_labels.dart';
import '../../widgets/verification_labels.dart';

/// Saved lots (Profile → Activity). Lots that are hidden from buyers again are left out by the server.
class SavedLotsScreen extends StatefulWidget {
  const SavedLotsScreen({super.key, this.service});

  final TradeService? service;

  @override
  State<SavedLotsScreen> createState() => _SavedLotsScreenState();
}

class _SavedLotsScreenState extends State<SavedLotsScreen> {
  late final TradeService _service = widget.service ?? TradeService();
  List<Map<String, dynamic>>? _lots;
  Object? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _error = null);
    try {
      final lots = await _service.savedLots();
      if (mounted) setState(() => _lots = lots);
    } catch (e) {
      if (mounted) setState(() => _error = e);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final fira = context.fira;
    return Scaffold(
      appBar: AppBar(title: Text(l10n.savedLotsTitle)),
      body: _error != null
          ? FiraErrorState(error: _error, onRetry: _load)
          : _lots == null
              ? const Center(child: CircularProgressIndicator())
              : RefreshIndicator(
                  onRefresh: _load,
                  child: ListView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.fromLTRB(20, 12, 20, 32),
                    children: [
                      if (_lots!.isEmpty)
                        Padding(
                          padding: const EdgeInsets.symmetric(vertical: 32),
                          child: Text(l10n.savedLotsEmpty, textAlign: TextAlign.center, style: TextStyle(color: fira.mutedText)),
                        ),
                      for (final lot in _lots!)
                        Card(
                          key: Key('saved-${lot['id']}'),
                          child: ListTile(
                            onTap: () async {
                              await Navigator.pushNamed(context, AppRouter.coffeeListingDetail, arguments: lot['slug'] ?? lot['id']);
                              if (mounted) _load();
                            },
                            title: Text(lot['title']?.toString() ?? '', style: const TextStyle(fontWeight: FontWeight.w700)),
                            subtitle: Text([
                              lot['lot_code']?.toString() ?? '',
                              lot['origin_region']?.toString() ?? '',
                              TradeLabels.process(l10n, lot['processing_method']?.toString()),
                              if (lot['indicative_price_usd_per_lb'] != null)
                                l10n.translate('dealPricePerLb', {'price': '\$${double.tryParse('${lot['indicative_price_usd_per_lb']}')?.toStringAsFixed(2) ?? ''}'}),
                            ].where((s) => s.isNotEmpty).join(' · ')),
                            trailing: LotLevelChip(level: lot['evidence_level']?.toString() ?? 'claimed'),
                          ),
                        ),
                    ],
                  ),
                ),
    );
  }
}

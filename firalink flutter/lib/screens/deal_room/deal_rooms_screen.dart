import 'package:flutter/material.dart';

import '../../l10n/app_localizations.dart';
import '../../models/deal_room_model.dart';
import '../../services/deal_room_service.dart';
import '../../theme/fira_colors.dart';
import '../../widgets/fira_error_state.dart';
import '../../widgets/trade_labels.dart';
import 'deal_room_hub_screen.dart';

/// Deals and payments (Profile → Activity): every deal room of my businesses, newest first.
/// The other side is shown as a business only.
class DealRoomsScreen extends StatefulWidget {
  const DealRoomsScreen({super.key, this.service});

  final DealRoomService? service;

  @override
  State<DealRoomsScreen> createState() => _DealRoomsScreenState();
}

class _DealRoomsScreenState extends State<DealRoomsScreen> {
  late final DealRoomService _service = widget.service ?? DealRoomService();
  List<DealRoomModel>? _items;
  Object? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _error = null);
    try {
      final items = await _service.getDealRooms();
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
      appBar: AppBar(title: Text(l10n.dealsTitle)),
      body: _error != null
          ? FiraErrorState(error: _error, onRetry: _load)
          : _items == null
              ? const Center(child: CircularProgressIndicator())
              : RefreshIndicator(
                  onRefresh: _load,
                  child: ListView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.fromLTRB(20, 12, 20, 32),
                    children: [
                      if (_items!.isEmpty)
                        Padding(
                          padding: const EdgeInsets.symmetric(vertical: 32),
                          child: Text(l10n.dealsEmpty, textAlign: TextAlign.center, style: TextStyle(color: fira.mutedText)),
                        ),
                      for (final d in _items!) _card(context, d),
                    ],
                  ),
                ),
    );
  }

  Widget _card(BuildContext context, DealRoomModel d) {
    final l10n = context.l10n;
    final fira = context.fira;
    final other = d.isSeller ? d.buyer : d.seller;
    return Card(
      key: Key('deal-${d.id}'),
      child: ListTile(
        onTap: () async {
          await Navigator.of(context).push(MaterialPageRoute(builder: (_) => DealRoomHubScreen(dealRoomId: d.id, service: _service)));
          if (mounted) _load();
        },
        title: Text(d.title, style: const TextStyle(fontWeight: FontWeight.w700)),
        subtitle: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text('${other?.name ?? ''} · ${d.dealReference}'),
          Text(
            d.paused
                ? l10n.dealsPaused
                : l10n.translate('dealStepLine', {'n': d.stepNumber, 'total': d.stepCount, 'status': DealLabels.waiting(l10n, d)}),
            style: TextStyle(color: d.paused ? fira.danger : fira.pending, fontWeight: FontWeight.w600),
          ),
        ]),
        trailing: Text(TradeLabels.money(d.targetValueUsd), style: const TextStyle(fontWeight: FontWeight.w700)),
        isThreeLine: true,
      ),
    );
  }
}

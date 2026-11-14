import 'package:flutter/material.dart';

import '../../l10n/app_localizations.dart';
import '../../models/sample_model.dart';
import '../../services/api_exception.dart';
import '../../services/sample_service.dart';
import '../../theme/fira_colors.dart';
import '../../widgets/fira_error_state.dart';
import 'sample_tracking_screen.dart';
import 'ship_sample_screen.dart';

/// Samples: the ones my business requested, and requests for my business's lots (seller inbox).
class SamplesScreen extends StatelessWidget {
  const SamplesScreen({super.key, this.service, this.initialTab = 0});

  final SampleService? service;
  final int initialTab;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final svc = service ?? SampleService();
    return DefaultTabController(
      length: 2,
      initialIndex: initialTab,
      child: Scaffold(
        appBar: AppBar(
          title: Text(l10n.samplesTitle),
          bottom: TabBar(tabs: [Tab(text: l10n.samplesMine), Tab(text: l10n.samplesRequests)]),
        ),
        body: TabBarView(children: [
          _SampleList(key: const Key('samples-buyer'), service: svc, side: 'buyer'),
          _SampleList(key: const Key('samples-seller'), service: svc, side: 'seller'),
        ]),
      ),
    );
  }
}

class _SampleList extends StatefulWidget {
  const _SampleList({super.key, required this.service, required this.side});

  final SampleService service;
  final String side;

  @override
  State<_SampleList> createState() => _SampleListState();
}

class _SampleListState extends State<_SampleList> {
  static const _tabs = ['to_send', 'sent', 'done', 'awaiting_payment'];
  String _tab = 'to_send';
  SampleList? _data;
  Object? _error;
  bool _noBusiness = false;

  bool get _seller => widget.side == 'seller';

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _error = null);
    try {
      final data = await widget.service.list(side: widget.side, tab: _seller ? _tab : null);
      if (mounted) setState(() => _data = data);
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() {
        _noBusiness = e.code == 'ORGANIZATION_REQUIRED';
        _error = _noBusiness ? null : e;
        _data = _noBusiness ? const SampleList(side: 'buyer', samples: []) : null;
      });
    } catch (e) {
      if (mounted) setState(() => _error = e);
    }
  }

  String _tabLabel(AppLocalizations l10n, String t) => switch (t) {
        'sent' => l10n.sampleTabSent,
        'done' => l10n.sampleTabDone,
        'awaiting_payment' => l10n.sampleTabAwaitingPayment,
        _ => l10n.sampleTabToSend,
      };

  Future<void> _open(SampleView s) async {
    final screen = _seller && s.status == 'fee_paid'
        ? ShipSampleScreen(sampleId: s.id, service: widget.service)
        : SampleTrackingScreen(sampleId: s.id, service: widget.service);
    await Navigator.of(context).push(MaterialPageRoute(builder: (_) => screen));
    _load();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final fira = context.fira;
    if (_error != null) return FiraErrorState(error: _error, onRetry: _load);
    if (_data == null) return const Center(child: CircularProgressIndicator());

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(20, 12, 20, 32),
        children: [
          if (_seller)
            Wrap(spacing: 8, runSpacing: 8, children: [
              for (final t in _tabs)
                ChoiceChip(
                  key: Key('tab-$t'),
                  label: Text('${_tabLabel(l10n, t)} ${_data!.counts[t] ?? 0}'),
                  selected: _tab == t,
                  onSelected: (_) {
                    setState(() => _tab = t);
                    _load();
                  },
                ),
            ]),
          const SizedBox(height: 8),
          if (_data!.samples.isEmpty)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 32),
              child: Text(l10n.samplesEmpty, textAlign: TextAlign.center, style: TextStyle(color: fira.mutedText)),
            ),
          for (final s in _data!.samples) SampleTile(sample: s, onTap: () => _open(s)),
          if (_seller) ...[
            const SizedBox(height: 16),
            Card(
              color: fira.brandTint,
              child: ListTile(
                leading: Icon(Icons.inventory_2_outlined, color: fira.brandPrimary),
                title: Text(l10n.retainedProtects, style: const TextStyle(fontWeight: FontWeight.w700)),
                subtitle: Text(l10n.retainedProtectsBody),
              ),
            ),
          ],
        ],
      ),
    );
  }
}

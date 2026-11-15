import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../l10n/app_localizations.dart';
import '../../models/sample_model.dart';
import '../../services/api_exception.dart';
import '../../services/sample_service.dart';
import '../../theme/fira_colors.dart';
import '../../widgets/fira_error_state.dart';
import '../../widgets/trade_labels.dart';
import '../../widgets/verification_labels.dart';
import '../trust_safety/report_modal.dart';
import 'ship_sample_screen.dart';

/// Sample tracking for both sides: requested, sealed, handed to courier, in transit, delivered
/// (the buyer confirms the seal), cupping result. Every action is checked again on the server.
class SampleTrackingScreen extends StatefulWidget {
  const SampleTrackingScreen({super.key, required this.sampleId, this.service});

  final int sampleId;
  final SampleService? service;

  @override
  State<SampleTrackingScreen> createState() => _SampleTrackingScreenState();
}

class SampleLabels {
  const SampleLabels._();

  static String status(AppLocalizations l10n, String s) => switch (s) {
        'requested' => l10n.sampleStatusRequested,
        'fee_paid' => l10n.sampleStatusFeePaid,
        'dispatched' => l10n.sampleStatusDispatched,
        'delivered' => l10n.sampleStatusDelivered,
        'cupped_approved' => l10n.sampleStatusApproved,
        'cupped_rejected' => l10n.sampleStatusRejected,
        _ => l10n.sampleStatusCancelled,
      };
}

class _SampleTrackingScreenState extends State<SampleTrackingScreen> {
  late final SampleService _service = widget.service ?? SampleService();
  final _score = TextEditingController();
  final _notes = TextEditingController();
  SampleView? _s;
  Map<String, String> _imageHeaders = const {};
  Object? _error;
  bool _busy = false;

  static const _order = ['requested', 'fee_paid', 'dispatched', 'delivered', 'cupped'];

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _score.dispose();
    _notes.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() => _error = null);
    try {
      final s = await _service.get(widget.sampleId);
      final headers = s.retainedPhotoUrl != null ? await _service.imageHeaders() : const <String, String>{};
      if (!mounted) return;
      setState(() {
        _s = s;
        _imageHeaders = headers;
      });
    } catch (e) {
      if (mounted) setState(() => _error = e);
    }
  }

  Future<void> _run(Future<SampleView> Function() action, {String? success}) async {
    setState(() => _busy = true);
    try {
      final s = await action();
      if (!mounted) return;
      setState(() => _s = s);
      if (success != null) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(success)));
      await _load();
    } on ApiException catch (e) {
      if (!mounted) return;
      if (e.code == 'PAYMENTS_NOT_AVAILABLE') {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(context.l10n.samplePaymentsUnavailable)));
      } else {
        showFiraActionError(context, e);
      }
    } catch (e) {
      if (mounted) showFiraActionError(context, e);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  int _reached(SampleView s) => switch (s.status) {
        'requested' => 0,
        'fee_paid' => 1,
        'dispatched' => 2,
        'delivered' => 3,
        'cupped_approved' || 'cupped_rejected' => 4,
        _ => -1,
      };

  Widget _step(BuildContext context, {required int index, required int reached, required String title, String? body, List<Widget> actions = const []}) {
    final fira = context.fira;
    final done = index < reached || (index == reached && index == _order.length - 1);
    final current = index == reached && !done;
    final color = done ? fira.success : (current ? fira.pending : fira.mutedText);

    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(done ? Icons.check_circle : (current ? Icons.radio_button_checked : Icons.radio_button_unchecked), color: color, size: 22),
        const SizedBox(width: 12),
        Expanded(
          child: Padding(
            padding: const EdgeInsets.only(bottom: 16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title, style: TextStyle(fontWeight: FontWeight.w700, color: current ? fira.pending : fira.onSurface)),
                if (body != null) Text(body, style: TextStyle(color: fira.mutedText)),
                ...actions,
              ],
            ),
          ),
        ),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    return Scaffold(
      appBar: AppBar(title: Text(l10n.sampleTrackingTitle)),
      body: _error != null
          ? FiraErrorState(error: _error, onRetry: _load)
          : _s == null
              ? const Center(child: CircularProgressIndicator())
              : RefreshIndicator(onRefresh: _load, child: _body(context, _s!)),
    );
  }

  Widget _body(BuildContext context, SampleView s) {
    final l10n = context.l10n;
    final fira = context.fira;
    String date(DateTime? d) => VerificationLabels.date(context, d);
    final reached = _reached(s);
    final buyer = !s.isSeller;
    final money = '\$${s.feeUsd.toStringAsFixed(2)}';

    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.fromLTRB(20, 12, 20, 32),
      children: [
        Card(
          child: ListTile(
            title: Text(s.lot?.title ?? '', style: const TextStyle(fontWeight: FontWeight.w700)),
            subtitle: Text([
              (buyer ? s.seller?.name : s.buyer?.name) ?? '',
              l10n.translate('sampleGrams', {'n': s.grams}),
              l10n.translate('createLotCode', {'code': s.lot?.lotCode ?? ''}),
            ].join(' · ')),
            trailing: Text(SampleLabels.status(l10n, s.status), key: const Key('sample-status'), style: TextStyle(color: fira.pending, fontWeight: FontWeight.w700)),
          ),
        ),
        const SizedBox(height: 16),
        if (s.status == 'cancelled')
          Text(l10n.sampleStatusCancelled, style: TextStyle(color: fira.mutedText))
        else ...[
          _step(context, index: 0, reached: reached, title: l10n.stepRequested, body: s.status == 'requested'
              ? l10n.sampleStatusRequested
              : s.paymentStatus == 'waived'
                  ? l10n.translate('stepRequestedFree', {'date': date(s.requestedAt)})
                  : '${l10n.translate('stepRequestedPaid', {'date': date(s.paidAt), 'amount': money})}${s.paymentMocked ? '\n${l10n.sampleTestPayment}' : ''}',
              actions: [
                if (buyer && s.status == 'requested')
                  FilledButton(
                    key: const Key('sample-pay'),
                    onPressed: _busy ? null : () => _run(() => _service.pay(s.id)),
                    child: Text(l10n.translate('samplePay', {'amount': money})),
                  ),
                if (buyer && (s.status == 'requested' || (s.status == 'fee_paid' && s.paymentStatus == 'waived')))
                  TextButton(key: const Key('sample-cancel'), onPressed: _busy ? null : () => _run(() => _service.cancel(s.id)), child: Text(l10n.sampleCancel)),
              ]),
          _step(context, index: 1, reached: reached, title: l10n.stepSealed,
              body: s.sealId != null ? l10n.translate('stepSealedBody', {'date': date(s.sealedAt), 'seal': s.sealId}) : null,
              actions: [
                if (!buyer && s.status == 'fee_paid')
                  FilledButton(
                    key: const Key('seal-and-ship'),
                    onPressed: () async {
                      final sent = await Navigator.of(context).push<bool>(MaterialPageRoute(builder: (_) => ShipSampleScreen(sampleId: s.id, service: _service)));
                      if (sent == true) _load();
                    },
                    child: Text(l10n.sealAndShip),
                  ),
              ]),
          _step(context, index: 2, reached: reached, title: l10n.stepCourier,
              body: s.trackingNumber != null ? l10n.translate('stepCourierBody', {'courier': (s.courier ?? '').toUpperCase(), 'number': s.trackingNumber}) : null,
              actions: [
                if (s.trackingUrl != null)
                  TextButton.icon(
                    onPressed: () => launchUrl(Uri.parse(s.trackingUrl!), mode: LaunchMode.externalApplication),
                    icon: const Icon(Icons.open_in_new),
                    label: Text(l10n.openCourierTracking),
                  ),
              ]),
          _step(context, index: 3, reached: reached, title: s.status == 'dispatched' ? l10n.stepTransit : l10n.stepDelivered,
              body: s.status == 'dispatched' ? l10n.stepTransitBody : (s.sealIntact == false ? l10n.sealBrokenNote : l10n.stepDeliveredBody),
              actions: [
                if (buyer && s.status == 'dispatched') ...[
                  FilledButton(key: const Key('seal-intact'), onPressed: _busy ? null : () => _run(() => _service.confirmDelivery(s.id, sealIntact: true)), child: Text(l10n.sealIntactYes)),
                  OutlinedButton(key: const Key('seal-broken'), onPressed: _busy ? null : () => _run(() => _service.confirmDelivery(s.id, sealIntact: false)), child: Text(l10n.sealIntactNo)),
                ],
              ]),
          _step(context, index: 4, reached: reached, title: l10n.stepCupping,
              body: s.cuppingScore != null
                  ? [l10n.translate('cuppingResult', {'score': s.cuppingScore!.toStringAsFixed(1)}), if ((s.cuppingNotes ?? '').isNotEmpty) s.cuppingNotes!].join(' · ')
                  : l10n.stepCuppingBody,
              actions: [if (buyer && s.status == 'delivered') _cuppingForm(context, s)]),
        ],
        if (s.retainedPhotoUrl != null) ...[
          Text(l10n.retainedSample, style: const TextStyle(fontWeight: FontWeight.w700)),
          const SizedBox(height: 8),
          ClipRRect(
            borderRadius: BorderRadius.circular(16),
            child: Image.network(s.retainedPhotoUrl!, headers: _imageHeaders, height: 180, fit: BoxFit.cover,
                errorBuilder: (_, _, _) => Container(height: 180, color: fira.chipUnselectedBg)),
          ),
          const SizedBox(height: 6),
          Text(l10n.translate('retainedSampleBody', {'seal': s.sealId ?? ''}), style: TextStyle(color: fira.mutedText)),
        ],
        const SizedBox(height: 16),
        OutlinedButton.icon(
          onPressed: () => ReportModal.show(context, reportedSampleId: s.id, targetTitle: '${(buyer ? s.seller?.name : s.buyer?.name) ?? ''} · ${s.reference}'),
          icon: const Icon(Icons.flag_outlined),
          label: Text(l10n.sampleReportProblem),
        ),
      ],
    );
  }

  Widget _cuppingForm(BuildContext context, SampleView s) {
    final l10n = context.l10n;
    void decide(bool approve) {
      final v = double.tryParse(_score.text.trim());
      if (v == null || v < 60 || v > 100) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('${l10n.cuppingScore}: ${l10n.captureRequired}')));
        return;
      }
      _run(() => _service.evaluate(s.id, approve: approve, score: v, notes: _notes.text.trim()));
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        TextField(key: const Key('cupping-score'), controller: _score, keyboardType: const TextInputType.numberWithOptions(decimal: true), decoration: InputDecoration(labelText: l10n.cuppingScore)),
        TextField(controller: _notes, maxLines: 2, maxLength: 2000, decoration: InputDecoration(labelText: l10n.cuppingNotes)),
        Row(children: [
          Expanded(
            child: OutlinedButton(
              key: const Key('cupping-reject'),
              onPressed: _busy ? null : () => decide(false),
              child: Text(l10n.cuppingReject),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: FilledButton(
              key: const Key('cupping-approve'),
              onPressed: _busy ? null : () => decide(true),
              child: Text(l10n.cuppingApprove),
            ),
          ),
        ]),
      ],
    );
  }
}

/// A sample row for lists (status from the server).
class SampleTile extends StatelessWidget {
  const SampleTile({super.key, required this.sample, required this.onTap});

  final SampleView sample;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final fira = context.fira;
    final s = sample;
    final other = s.isSeller ? s.buyer : s.seller;
    final shipLine = s.isSeller && s.status == 'fee_paid' && s.shipBy != null
        ? (s.paymentStatus == 'waived'
            ? l10n.translate('inboxFreeLine', {'date': VerificationLabels.date(context, s.shipBy)})
            : l10n.translate('inboxFeeLine', {'amount': '\$${s.feeUsd.toStringAsFixed(2)}', 'date': VerificationLabels.date(context, s.shipBy)}))
        : null;

    return Card(
      key: Key('sample-${s.id}'),
      child: ListTile(
        onTap: onTap,
        title: Text(other?.name ?? '', style: const TextStyle(fontWeight: FontWeight.w600)),
        subtitle: Text([
          '${s.lot?.title ?? ''} · ${l10n.translate('sampleGrams', {'n': s.grams})} · ${s.lot?.lotCode ?? ''}',
          ?shipLine,
        ].join('\n')),
        trailing: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            Text(SampleLabels.status(l10n, s.status), style: TextStyle(color: fira.pending, fontWeight: FontWeight.w600, fontSize: 12)),
            if (other != null) BusinessTierLabel(business: other),
          ],
        ),
      ),
    );
  }
}

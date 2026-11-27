import 'dart:io';

import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:path_provider/path_provider.dart';
import 'package:provider/provider.dart';
import 'package:share_plus/share_plus.dart';

import '../../l10n/app_localizations.dart';
import '../../models/market_series_detail.dart';
import '../../providers/auth_provider.dart';
import '../../screens/ai_chat/ai_chat_screen.dart';
import '../../services/market_account_service.dart';
import '../../theme/fira_colors.dart';
import '../auth/auth_gate.dart';

/// Detail sheet actions: watchlist, price alert and CSV export (signed-in; guests are asked to sign in
/// and come back to the same sheet), and Ask Fira AI (everyone).
List<Widget> marketDetailActions(BuildContext context, MarketSeriesDetail detail) => [MarketDetailActions(detail: detail)];

class MarketDetailActions extends StatefulWidget {
  const MarketDetailActions({super.key, required this.detail, this.service});

  final MarketSeriesDetail detail;
  final MarketAccountService? service;

  @override
  State<MarketDetailActions> createState() => _MarketDetailActionsState();
}

class _MarketDetailActionsState extends State<MarketDetailActions> {
  late final MarketAccountService _service = widget.service ?? MarketAccountService();
  bool? _watched;
  List<MarketAlert> _alerts = const [];
  bool _busy = false;

  bool get _signedIn {
    try {
      return context.read<AuthProvider>().isAuthenticated;
    } on ProviderNotFoundException {
      return false;
    }
  }

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _loadAccount());
  }

  Future<void> _loadAccount() async {
    if (!mounted || !_signedIn) return;
    try {
      final keys = await _service.watchlist();
      final alerts = await _service.alerts();
      if (!mounted) return;
      setState(() {
        _watched = keys.contains(widget.detail.key);
        _alerts = alerts.where((a) => a.key == widget.detail.key).toList();
      });
    } catch (_) {
      // Leave the buttons usable; the next action reports a failure if the server is unreachable.
    }
  }

  void _say(String text) => ScaffoldMessenger.maybeOf(context)?.showSnackBar(SnackBar(content: Text(text)));

  Future<bool> _guard() async {
    if (_busy) return false;
    if (!await ensureSignedIn(context) || !mounted) return false;
    if (_watched == null) await _loadAccount();
    return mounted;
  }

  Future<void> _toggleWatch() async {
    if (!await _guard() || !mounted) return;
    final l10n = context.l10n;
    setState(() => _busy = true);
    try {
      final keys = await _service.setWatched(widget.detail.key, !(_watched ?? false));
      if (!mounted) return;
      setState(() => _watched = keys.contains(widget.detail.key));
      _say(_watched! ? l10n.marketWatchAdded : l10n.marketWatchRemoved);
    } catch (_) {
      _say(l10n.marketActionFailed);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _export() async {
    if (!await _guard() || !mounted) return;
    final l10n = context.l10n;
    setState(() => _busy = true);
    try {
      final range = const ['1M', '3M', '1Y', '5Y'].contains(widget.detail.range) ? widget.detail.range : '1Y';
      final bytes = await _service.exportCsv(widget.detail.key, range: range);
      final file = File('${(await getTemporaryDirectory()).path}/${widget.detail.key.toLowerCase()}-${range.toLowerCase()}.csv');
      await file.writeAsBytes(bytes, flush: true);
      await SharePlus.instance.share(
        ShareParams(
          files: [XFile(file.path, mimeType: 'text/csv')],
          subject: widget.detail.name,
        ),
      );
    } catch (_) {
      _say(l10n.marketActionFailed);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  void _askAi() {
    Navigator.of(context).push(MaterialPageRoute(builder: (_) => AiChatScreen(initialPrompt: context.l10n.marketAskAiPrompt(widget.detail.shortName))));
  }

  Future<void> _alertSheet() async {
    if (!await _guard() || !mounted) return;
    final changed = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      builder: (_) => _AlertForm(detail: widget.detail, service: _service, alerts: _alerts),
    );
    if (changed == true) await _loadAccount();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final onDeep = Theme.of(context).colorScheme.onPrimary;
    Widget button(IconData icon, String label, VoidCallback onTap, {bool selected = false}) => Semantics(
      button: true,
      selected: selected,
      child: OutlinedButton.icon(
        onPressed: _busy ? null : onTap,
        icon: Icon(icon, size: 18),
        label: Text(label),
        style: OutlinedButton.styleFrom(
          foregroundColor: onDeep,
          minimumSize: const Size(44, 44),
          side: BorderSide(color: onDeep.withValues(alpha: selected ? 1 : .25)),
          shape: const StadiumBorder(),
        ),
      ),
    );
    final fmt = NumberFormat.decimalPatternDigits(locale: 'en', decimalDigits: widget.detail.decimals);
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Wrap(
          spacing: 8,
          runSpacing: 8,
          children: [
            button(
              _watched == true ? Icons.star_rounded : Icons.star_outline_rounded,
              _watched == true ? l10n.marketWatchRemove : l10n.marketWatchAdd,
              _toggleWatch,
              selected: _watched == true,
            ),
            button(Icons.notifications_none_rounded, l10n.marketAlertTitle, _alertSheet),
            button(Icons.auto_awesome_outlined, l10n.marketAskAi, _askAi),
            button(Icons.download_rounded, l10n.marketExport, _export),
          ],
        ),
        for (final a in _alerts)
          Padding(
            padding: const EdgeInsets.only(top: 8),
            child: Text(
              l10n.marketAlertRow(a.direction == 'above' ? l10n.marketAlertAbove : l10n.marketAlertBelow, fmt.format(a.threshold), a.unitLabel),
              style: TextStyle(color: onDeep.withValues(alpha: .74), fontSize: 13),
            ),
          ),
      ],
    );
  }
}

class _AlertForm extends StatefulWidget {
  const _AlertForm({required this.detail, required this.service, required this.alerts});

  final MarketSeriesDetail detail;
  final MarketAccountService service;
  final List<MarketAlert> alerts;

  @override
  State<_AlertForm> createState() => _AlertFormState();
}

class _AlertFormState extends State<_AlertForm> {
  final _threshold = TextEditingController();
  String _direction = 'above';
  final Set<String> _channels = {'push'};
  bool _busy = false;
  String? _error;
  late List<MarketAlert> _alerts = widget.alerts;

  @override
  void dispose() {
    _threshold.dispose();
    super.dispose();
  }

  // Alerts use the instrument's stored unit (the first unit the server lists).
  String get _unit => widget.detail.units.isNotEmpty ? widget.detail.units.first.label : widget.detail.unitLabel;

  Future<void> _save() async {
    final l10n = context.l10n;
    final value = double.tryParse(_threshold.text.replaceAll(',', '.').trim());
    if (value == null || value <= 0 || _channels.isEmpty) {
      setState(() => _error = l10n.marketAlertInvalid);
      return;
    }
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await widget.service.createAlert(key: widget.detail.key, direction: _direction, threshold: value, channels: _channels.toList());
      if (!mounted) return;
      ScaffoldMessenger.maybeOf(context)?.showSnackBar(SnackBar(content: Text(l10n.marketAlertSaved)));
      Navigator.of(context).pop(true);
    } catch (_) {
      if (mounted) setState(() => _error = l10n.marketActionFailed);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _delete(MarketAlert alert) async {
    final l10n = context.l10n;
    try {
      await widget.service.deleteAlert(alert.id);
      if (!mounted) return;
      setState(() => _alerts = _alerts.where((a) => a.id != alert.id).toList());
      ScaffoldMessenger.maybeOf(context)?.showSnackBar(SnackBar(content: Text(l10n.marketAlertDeleted)));
    } catch (_) {
      if (mounted) setState(() => _error = l10n.marketActionFailed);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final fira = context.fira;
    final fmt = NumberFormat.decimalPatternDigits(locale: 'en', decimalDigits: widget.detail.decimals);
    return Padding(
      padding: EdgeInsets.fromLTRB(20, 16, 20, 20 + MediaQuery.viewInsetsOf(context).bottom),
      child: SingleChildScrollView(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          mainAxisSize: MainAxisSize.min,
          children: [
            Semantics(header: true, child: Text('${l10n.marketAlertTitle} · ${widget.detail.shortName}', style: Theme.of(context).textTheme.titleMedium)),
            for (final a in _alerts)
              ListTile(
                contentPadding: EdgeInsets.zero,
                title: Text(l10n.marketAlertRow(a.direction == 'above' ? l10n.marketAlertAbove : l10n.marketAlertBelow, fmt.format(a.threshold), a.unitLabel)),
                trailing: TextButton(onPressed: () => _delete(a), child: Text(l10n.marketAlertDelete)),
              ),
            const SizedBox(height: 12),
            Text(
              l10n.marketAlertWhen,
              style: TextStyle(color: fira.mutedText, fontWeight: FontWeight.w600),
            ),
            const SizedBox(height: 8),
            SegmentedButton<String>(
              segments: [
                ButtonSegment(value: 'above', label: Text(l10n.marketAlertAbove)),
                ButtonSegment(value: 'below', label: Text(l10n.marketAlertBelow)),
              ],
              selected: {_direction},
              onSelectionChanged: (s) => setState(() => _direction = s.first),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _threshold,
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
              decoration: InputDecoration(labelText: l10n.marketAlertThreshold(_unit), errorText: _error),
            ),
            CheckboxListTile(
              contentPadding: EdgeInsets.zero,
              value: _channels.contains('push'),
              title: Text(l10n.marketAlertPush),
              onChanged: (v) => setState(() => v == true ? _channels.add('push') : _channels.remove('push')),
            ),
            CheckboxListTile(
              contentPadding: EdgeInsets.zero,
              value: _channels.contains('mail'),
              title: Text(l10n.marketAlertMail),
              onChanged: (v) => setState(() => v == true ? _channels.add('mail') : _channels.remove('mail')),
            ),
            const SizedBox(height: 8),
            FilledButton(
              onPressed: _busy ? null : _save,
              style: FilledButton.styleFrom(minimumSize: const Size.fromHeight(48)),
              child: Text(l10n.marketAlertSave),
            ),
          ],
        ),
      ),
    );
  }
}

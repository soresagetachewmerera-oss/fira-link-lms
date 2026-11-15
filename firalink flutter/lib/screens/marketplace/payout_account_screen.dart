import 'package:flutter/material.dart';

import '../../l10n/app_localizations.dart';
import '../../models/deal_room_model.dart';
import '../../services/payout_account_service.dart';
import '../../theme/fira_colors.dart';
import '../../widgets/fira_error_state.dart';
import '../../services/api_exception.dart';
import '../../widgets/auth/fresh_auth_sheet.dart';
import '../../widgets/verification_labels.dart';

/// The seller's payout bank account (slice 7). Shown masked. Only the owner changes it; a change
/// pauses payments for 48 hours and Fira Link calls the number already on file before buyers can pay it.
class PayoutAccountScreen extends StatefulWidget {
  const PayoutAccountScreen({super.key, this.service, this.confirmFreshAuth});

  final PayoutAccountService? service;

  /// Bank details need a fresh sign-in; defaults to the "confirm it is you" sheet.
  final Future<bool> Function(BuildContext context)? confirmFreshAuth;

  @override
  State<PayoutAccountScreen> createState() => _PayoutAccountScreenState();
}

class _PayoutAccountScreenState extends State<PayoutAccountScreen> {
  late final PayoutAccountService _service = widget.service ?? PayoutAccountService();
  final _formKey = GlobalKey<FormState>();
  final _bank = TextEditingController();
  final _name = TextEditingController();
  final _number = TextEditingController();
  PayoutAccountState? _state;
  Object? _error;
  bool _editing = false;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    for (final c in [_bank, _name, _number]) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _load() async {
    setState(() => _error = null);
    try {
      final s = await _service.get();
      if (mounted) setState(() => _state = s);
    } catch (e) {
      if (mounted) setState(() => _error = e);
    }
  }

  Future<void> _save() async {
    if (!(_formKey.currentState?.validate() ?? false)) return;
    final l10n = context.l10n;
    setState(() => _saving = true);
    try {
      Future<void> save() => _service.save(bankName: _bank.text.trim(), accountName: _name.text.trim(), accountNumber: _number.text.trim());
      try {
        await save();
      } on ApiException catch (e) {
        if (e.code != 'FRESH_AUTH_REQUIRED' || !mounted) rethrow;
        final ok = await (widget.confirmFreshAuth ?? (c) => FreshAuthSheet.confirm(c))(context);
        if (!ok || !mounted) return;
        await save();
      }
      if (!mounted) return;
      _number.clear();
      setState(() => _editing = false);
      ScaffoldMessenger.of(context)
        ..hideCurrentSnackBar()
        ..showSnackBar(SnackBar(content: Text(l10n.payoutSaved)));
      await _load();
    } catch (e) {
      if (mounted) showFiraActionError(context, e);
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    return Scaffold(
      appBar: AppBar(title: Text(l10n.payoutTitle)),
      body: _error != null
          ? FiraErrorState(error: _error, onRetry: _load)
          : _state == null
              ? const Center(child: CircularProgressIndicator())
              : _body(context, _state!),
    );
  }

  Widget _body(BuildContext context, PayoutAccountState s) {
    final l10n = context.l10n;
    final fira = context.fira;
    final a = s.account;
    final showForm = s.canChange && (_editing || a == null);

    return ListView(
      padding: const EdgeInsets.fromLTRB(20, 12, 20, 32),
      children: [
        Text(l10n.payoutIntro, style: TextStyle(color: fira.mutedText)),
        const SizedBox(height: 12),
        if (a == null)
          Card(child: ListTile(title: Text(l10n.payoutNone)))
        else
          _current(context, a),
        if (!s.canChange)
          Padding(
            padding: const EdgeInsets.only(top: 8),
            child: Text(l10n.payoutOwnerOnly, key: const Key('payout-owner-only'), style: TextStyle(color: fira.mutedText)),
          ),
        if (s.canChange && a != null && !_editing)
          Padding(
            padding: const EdgeInsets.only(top: 8),
            child: OutlinedButton(key: const Key('payout-change'), onPressed: () => setState(() => _editing = true), child: Text(l10n.payoutChange)),
          ),
        if (showForm) ...[
          const SizedBox(height: 12),
          if (a != null)
            Card(
              key: const Key('payout-change-warning'),
              color: fira.brandTint,
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Text(l10n.translate('payoutChangeWarning', {'hours': s.pauseHours}), style: TextStyle(color: fira.onSurface)),
              ),
            ),
          Form(
            key: _formKey,
            child: Column(children: [
              TextFormField(
                key: const Key('payout-bank'),
                controller: _bank,
                maxLength: 120,
                decoration: InputDecoration(labelText: l10n.payoutBank),
                validator: (v) => (v ?? '').trim().isEmpty ? l10n.captureRequired : null,
              ),
              TextFormField(
                key: const Key('payout-name'),
                controller: _name,
                maxLength: 200,
                decoration: InputDecoration(labelText: l10n.payoutAccountName),
                validator: (v) => (v ?? '').trim().isEmpty ? l10n.captureRequired : null,
              ),
              TextFormField(
                key: const Key('payout-number'),
                controller: _number,
                maxLength: 40,
                keyboardType: TextInputType.number,
                decoration: InputDecoration(labelText: l10n.payoutAccountNumber),
                validator: (v) => RegExp(r'^[0-9]{6,34}$').hasMatch((v ?? '').replaceAll(RegExp(r'[\s-]'), '')) ? null : l10n.payoutAccountNumberInvalid,
              ),
              const SizedBox(height: 8),
              SizedBox(
                width: double.infinity,
                child: FilledButton(
                  key: const Key('payout-save'),
                  onPressed: _saving ? null : _save,
                  child: _saving ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2)) : Text(l10n.payoutSave),
                ),
              ),
            ]),
          ),
        ],
      ],
    );
  }

  Widget _current(BuildContext context, PayoutAccountView a) {
    final l10n = context.l10n;
    final fira = context.fira;
    final color = switch (a.status) {
      'confirmed' => fira.success,
      'rejected' => fira.danger,
      _ => fira.pending,
    };
    return Card(
      key: Key('payout-${a.status}'),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text(a.accountName, style: const TextStyle(fontWeight: FontWeight.w700)),
          Text('${a.bankName} · ${l10n.translate('payoutCurrent', {'last4': a.last4})}'),
          const SizedBox(height: 6),
          Text(l10n.translate('payoutStatus_${a.status}'), style: TextStyle(color: color, fontWeight: FontWeight.w600)),
          if (a.inPause)
            Text(l10n.translate('payoutPausedUntil', {'date': VerificationLabels.date(context, a.activeFrom)}), style: TextStyle(color: fira.pending)),
          if (a.reviewNote != null) Text(a.reviewNote!, style: TextStyle(color: fira.mutedText)),
        ]),
      ),
    );
  }
}

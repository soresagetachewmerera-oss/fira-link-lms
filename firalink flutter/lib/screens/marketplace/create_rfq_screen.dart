import 'package:flutter/material.dart';

import '../../l10n/app_localizations.dart';
import '../../services/api_exception.dart';
import '../../services/trade_service.dart';
import '../../theme/fira_colors.dart';
import '../../widgets/fira_error_state.dart';
import '../../widgets/trade_labels.dart';
import '../../widgets/verification_labels.dart';

/// Post a request (RFQ). The server checks the business is a Tier 1 buyer or broker; only verified
/// sellers can see and answer it. "Request quote" on a lot opens this pre-filled from the lot.
class CreateRfqScreen extends StatefulWidget {
  const CreateRfqScreen({super.key, this.prefillListingId, this.service});

  final int? prefillListingId;
  final TradeService? service;

  @override
  State<CreateRfqScreen> createState() => _CreateRfqScreenState();
}

class _CreateRfqScreenState extends State<CreateRfqScreen> {
  late final TradeService _service = widget.service ?? TradeService();
  final _formKey = GlobalKey<FormState>();
  final _title = TextEditingController();
  final _volume = TextEditingController();
  final _destination = TextEditingController();
  final _target = TextEditingController();
  final _notes = TextEditingController();
  String _unit = 'bags';
  String _incoterm = 'FOB';
  String? _origin;
  String? _process;
  String? _grade;
  DateTime? _neededBy;
  bool _sampleFirst = false;
  bool _sending = false;

  @override
  void initState() {
    super.initState();
    if (widget.prefillListingId != null) _prefill();
  }

  @override
  void dispose() {
    for (final c in [_title, _volume, _destination, _target, _notes]) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _prefill() async {
    try {
      final p = await _service.prefillFromLot(widget.prefillListingId!);
      if (p == null || !mounted) return;
      setState(() {
        _title.text = p['title']?.toString() ?? '';
        _origin = p['origin_region']?.toString();
        _process = p['processing_method']?.toString();
        _grade = p['grade']?.toString();
        _incoterm = TradeLabels.incoterms.contains(p['incoterm']) ? p['incoterm'] as String : 'FOB';
      });
    } catch (_) {
      // Pre-filling is a convenience; the buyer fills the form in.
    }
  }

  Future<void> _pickDate() async {
    final now = DateTime.now();
    final d = await showDatePicker(
      context: context,
      initialDate: _neededBy ?? now.add(const Duration(days: 60)),
      firstDate: now.add(const Duration(days: 1)),
      lastDate: DateTime(now.year + 2),
    );
    if (d != null) setState(() => _neededBy = d);
  }

  Future<void> _post() async {
    if (!(_formKey.currentState?.validate() ?? false)) return;
    final l10n = context.l10n;
    setState(() => _sending = true);
    try {
      await _service.postRequest({
        'title': _title.text.trim(),
        if (_origin != null) 'origin_region': _origin,
        if (_process != null) 'processing_method': _process,
        if (_grade != null) 'grade': _grade,
        'volume_value': double.parse(_volume.text.trim()),
        'volume_unit': _unit,
        'destination': _destination.text.trim(),
        'incoterm': _incoterm,
        if (_target.text.trim().isNotEmpty) 'target_price_usd_per_lb': double.parse(_target.text.trim()),
        if (_neededBy != null)
          'needed_by': '${_neededBy!.year}-${_neededBy!.month.toString().padLeft(2, '0')}-${_neededBy!.day.toString().padLeft(2, '0')}',
        'sample_first': _sampleFirst,
        if (_notes.text.trim().isNotEmpty) 'notes': _notes.text.trim(),
      });
      if (!mounted) return;
      ScaffoldMessenger.of(context)
        ..hideCurrentSnackBar()
        ..showSnackBar(SnackBar(content: Text(l10n.rfqPosted)));
      Navigator.of(context).pop(true);
    } on ApiException catch (e) {
      if (!mounted) return;
      if (e.code == 'CONTACT_DETAILS') {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(l10n.tradeContactDetails)));
      } else {
        showFiraActionError(context, e);
      }
    } catch (e) {
      if (mounted) showFiraActionError(context, e);
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  String? _required(String? v) => (v == null || v.trim().isEmpty) ? context.l10n.captureRequired : null;

  bool get _hasUnsavedData =>
      _title.text.trim().isNotEmpty ||
      _volume.text.trim().isNotEmpty ||
      _destination.text.trim().isNotEmpty ||
      _target.text.trim().isNotEmpty ||
      _notes.text.trim().isNotEmpty;

  Future<bool?> _confirmDiscard(BuildContext context, AppLocalizations l10n) {
    return showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: context.fira.surface,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(28)),
        title: Text(l10n.discardChangesTitle, style: TextStyle(color: context.fira.onSurface, fontWeight: FontWeight.bold)),
        content: Text(l10n.discardChangesBody, style: TextStyle(color: context.fira.mutedText)),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(false),
            child: Text(l10n.keepEditing, style: TextStyle(color: context.fira.brandPrimary)),
          ),
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(true),
            child: Text(l10n.discard, style: TextStyle(color: context.fira.danger)),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final fira = context.fira;

    return PopScope(
      canPop: !_hasUnsavedData || _sending,
      onPopInvokedWithResult: (didPop, _) async {
        if (didPop) return;
        final discard = await _confirmDiscard(context, l10n);
        if (discard == true && context.mounted) {
          Navigator.of(context).pop();
        }
      },
      child: Scaffold(
        appBar: AppBar(title: Text(l10n.marketPostRequest)),
        body: SafeArea(
          child: Form(
          key: _formKey,
          child: ListView(
            padding: const EdgeInsets.fromLTRB(20, 12, 20, 32),
            children: [
              Card(
                color: fira.brandTint,
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Text(l10n.rfqOnlyVerified, style: TextStyle(color: fira.onSurface, fontWeight: FontWeight.w600)),
                ),
              ),
              const SizedBox(height: 12),
              TextFormField(key: const Key('rfq-title'), controller: _title, maxLength: 255, decoration: InputDecoration(labelText: l10n.rfqWhatCoffee), validator: _required),
              if (_origin != null || _process != null || _grade != null)
                Padding(
                  padding: const EdgeInsets.only(bottom: 8),
                  child: Wrap(spacing: 8, children: [
                    if (_origin != null) Chip(label: Text(_origin!)),
                    if (_process != null) Chip(label: Text(TradeLabels.process(l10n, _process))),
                    if (_grade != null) Chip(label: Text(TradeLabels.grade(l10n, _grade))),
                  ]),
                ),
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    flex: 3,
                    child: TextFormField(
                      key: const Key('rfq-volume'),
                      controller: _volume,
                      keyboardType: const TextInputType.numberWithOptions(decimal: true),
                      decoration: InputDecoration(labelText: l10n.rfqVolume),
                      validator: (v) => (double.tryParse(v?.trim() ?? '') ?? 0) > 0 ? null : l10n.captureRequired,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    flex: 2,
                    child: DropdownButtonFormField<String>(
                      isExpanded: true,
                      initialValue: _unit,
                      decoration: InputDecoration(labelText: l10n.rfqUnit),
                      items: [for (final u in TradeLabels.units) DropdownMenuItem(value: u, child: Text(TradeLabels.unit(l10n, u)))],
                      onChanged: (u) => setState(() => _unit = u ?? _unit),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              TextFormField(key: const Key('rfq-destination'), controller: _destination, maxLength: 150, decoration: InputDecoration(labelText: l10n.rfqDestination), validator: _required),
              Text(l10n.rfqIncoterm, style: const TextStyle(fontWeight: FontWeight.w600)),
              const SizedBox(height: 6),
              Wrap(spacing: 8, children: [
                for (final t in TradeLabels.incoterms)
                  ChoiceChip(key: Key('incoterm-$t'), label: Text(t), selected: _incoterm == t, onSelected: (_) => setState(() => _incoterm = t)),
              ]),
              const SizedBox(height: 12),
              TextFormField(
                controller: _target,
                keyboardType: const TextInputType.numberWithOptions(decimal: true),
                decoration: InputDecoration(labelText: l10n.rfqTargetPrice, helperText: l10n.rfqTargetPriceHint, helperMaxLines: 2),
                validator: (v) => (v == null || v.trim().isEmpty || (double.tryParse(v.trim()) ?? 0) >= 0.5) ? null : l10n.captureRequired,
              ),
              const SizedBox(height: 12),
              InkWell(
                onTap: _pickDate,
                child: InputDecorator(
                  decoration: InputDecoration(labelText: l10n.rfqNeededBy, suffixIcon: const Icon(Icons.calendar_today_outlined)),
                  child: Text(_neededBy == null ? l10n.captureChooseDate : VerificationLabels.date(context, _neededBy)),
                ),
              ),
              SwitchListTile(
                key: const Key('rfq-sample-first'),
                contentPadding: EdgeInsets.zero,
                title: Text(l10n.rfqSampleFirst),
                subtitle: Text(l10n.rfqSampleFirstHint),
                value: _sampleFirst,
                onChanged: (v) => setState(() => _sampleFirst = v),
              ),
              TextFormField(controller: _notes, maxLines: 3, maxLength: 3000, decoration: InputDecoration(labelText: l10n.rfqNotes)),
              const SizedBox(height: 12),
              FilledButton(
                key: const Key('rfq-post'),
                onPressed: _sending ? null : _post,
                child: _sending ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2)) : Text(l10n.rfqPost),
              ),
            ],
          ),
        ),
      ),
    ),
    );
  }
}

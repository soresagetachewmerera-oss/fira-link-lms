import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../l10n/app_localizations.dart';
import '../../models/organization_model.dart';
import '../../providers/workspace_provider.dart';
import '../../services/organization_service.dart';
import '../../theme/fira_colors.dart';
import '../../widgets/fira_error_state.dart';
import '../marketplace/organization_verification_screen.dart';

/// Business activities (release 2a): the business's category and what it does (produce, sell for
/// export, buy for import, ...). The owner switches activities on and off; the server decides whether
/// each one counts (verification and licence documents). No flow board exists for this screen; it is
/// built from the existing tokens and list patterns.
class BusinessActivitiesScreen extends StatefulWidget {
  const BusinessActivitiesScreen({super.key, required this.organizationId, this.service});

  final int organizationId;
  final OrganizationService? service;

  @override
  State<BusinessActivitiesScreen> createState() => _BusinessActivitiesScreenState();
}

class _BusinessActivitiesScreenState extends State<BusinessActivitiesScreen> {
  static const _localOnly = {'produce', 'aggregate', 'process', 'sell_domestic', 'buy_domestic'};

  late final OrganizationService _service = widget.service ?? OrganizationService();
  BusinessActivities? _data;
  Object? _error;
  bool _saving = false;
  Set<String> _declared = {};

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _error = null);
    try {
      final d = await _service.getActivities(widget.organizationId);
      if (!mounted) return;
      _apply(d);
    } catch (e) {
      if (mounted) setState(() => _error = e);
    }
  }

  void _apply(BusinessActivities d) {
    setState(() {
      _data = d;
      _declared = {for (final c in d.capabilities) if (c.declared) c.code};
    });
  }

  bool get _changed {
    final d = _data;
    if (d == null) return false;
    return _declared.length != d.capabilities.where((c) => c.declared).length ||
        d.capabilities.any((c) => c.declared != _declared.contains(c.code));
  }

  Future<void> _save() async {
    setState(() => _saving = true);
    try {
      final d = await _service.updateActivities(widget.organizationId, _declared.toList());
      if (!mounted) return;
      _apply(d);
      context.read<WorkspaceProvider>().loadWorkspaces();
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(context.l10n.activitiesSaved)));
    } catch (e) {
      if (mounted) showFiraActionError(context, e);
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  Future<void> _chooseCategory() async {
    final l10n = context.l10n;
    final lang = Localizations.localeOf(context).languageCode;
    List<BusinessCategoryOption> options;
    try {
      options = (await context.read<WorkspaceProvider>().loadOptions()).businessCategories;
    } catch (e) {
      if (mounted) showFiraActionError(context, e);
      return;
    }
    final current = _data?.category;
    // A verified business keeps its role: offer only categories of the same role (the server checks too).
    if (current != null && !(_data?.canChangeCategory ?? false)) {
      options = options.where((c) => c.businessRole == current.businessRole).toList();
    }
    if (!mounted) return;
    final picked = await showModalBottomSheet<String>(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(28))),
      builder: (ctx) => SafeArea(
        child: ConstrainedBox(
          constraints: BoxConstraints(maxHeight: MediaQuery.of(ctx).size.height * 0.8),
          child: ListView(
            shrinkWrap: true,
            padding: const EdgeInsets.symmetric(vertical: 12),
            children: [
              Padding(
                padding: const EdgeInsets.fromLTRB(20, 8, 20, 12),
                child: Text(l10n.businessCategoryLabel, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700)),
              ),
              for (final c in options)
                ListTile(
                  minVerticalPadding: 12,
                  title: Text(c.label(lang), style: const TextStyle(fontWeight: FontWeight.w600)),
                  subtitle: c.description(lang).isEmpty ? null : Text(c.description(lang)),
                  trailing: c.code == current?.code ? Icon(Icons.check, color: ctx.fira.brandPrimary) : null,
                  onTap: () => Navigator.pop(ctx, c.code),
                ),
            ],
          ),
        ),
      ),
    );
    if (picked == null || picked == current?.code || !mounted) return;
    setState(() => _saving = true);
    try {
      final d = await _service.updateCategory(widget.organizationId, picked);
      if (!mounted) return;
      _apply(d);
      context.read<WorkspaceProvider>().loadWorkspaces();
    } catch (e) {
      if (mounted) showFiraActionError(context, e);
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  static String capabilityLabel(AppLocalizations l10n, String code) => switch (code) {
        'produce' => l10n.capabilityProduce,
        'aggregate' => l10n.capabilityAggregate,
        'process' => l10n.capabilityProcess,
        'sell_domestic' => l10n.capabilitySellDomestic,
        'sell_export' => l10n.capabilitySellExport,
        'buy_domestic' => l10n.capabilityBuyDomestic,
        'buy_export' => l10n.capabilityBuyExport,
        _ => code,
      };

  /// Word, icon and color together: status is never shown by color alone.
  Widget _status(BuildContext context, String status) {
    final fira = context.fira;
    final l10n = context.l10n;
    final (String text, IconData icon, Color fg, Color bg) = switch (status) {
      'active' => (l10n.activityStatusActive, Icons.check_circle_outline, fira.success, fira.success.withValues(alpha: 0.10)),
      'needs_verification' => (l10n.activityStatusNeedsVerification, Icons.hourglass_empty, fira.pending, fira.brandTint),
      'needs_documents' => (l10n.activityStatusNeedsDocuments, Icons.description_outlined, fira.pending, fira.brandTint),
      'suspended' => (l10n.activityStatusSuspended, Icons.block, fira.danger, fira.danger.withValues(alpha: 0.10)),
      _ => (l10n.activityStatusOff, Icons.remove_circle_outline, fira.mutedText, fira.chipUnselectedBg),
    };
    return Container(
      margin: const EdgeInsets.only(top: 6),
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(12)),
      child: Row(mainAxisSize: MainAxisSize.min, children: [
        Icon(icon, size: 14, color: fg),
        const SizedBox(width: 6),
        Flexible(child: Text(text, style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: fg))),
      ]),
    );
  }

  Widget _notice(BuildContext context, String text) {
    final fira = context.fira;
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(color: fira.brandTint, borderRadius: BorderRadius.circular(16)),
      child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Icon(Icons.info_outline, size: 18, color: fira.brandPrimary),
        const SizedBox(width: 10),
        Expanded(child: Text(text, style: TextStyle(fontSize: 12.5, height: 1.4, color: fira.onSurface))),
      ]),
    );
  }

  @override
  Widget build(BuildContext context) {
    final fira = context.fira;
    final l10n = context.l10n;
    final lang = Localizations.localeOf(context).languageCode;
    final d = _data;

    Widget body;
    if (_error != null && d == null) {
      body = FiraErrorState(error: _error, onRetry: _load);
    } else if (d == null) {
      body = Center(child: CircularProgressIndicator(color: fira.brandPrimary));
    } else {
      final showLocalNote = d.capabilities.any((c) => _localOnly.contains(c.code));
      body = RefreshIndicator(
        onRefresh: _load,
        child: ListView(
          padding: const EdgeInsets.fromLTRB(20, 12, 20, 120),
          children: [
            Text(l10n.activitiesIntro, style: TextStyle(fontSize: 13.5, height: 1.45, color: fira.mutedText)),
            const SizedBox(height: 16),
            Card(
              margin: EdgeInsets.zero,
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Text(l10n.activitiesCategory, style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: fira.mutedText)),
                  const SizedBox(height: 4),
                  if (d.category != null) ...[
                    Text(d.category!.label(lang), style: TextStyle(fontSize: 17, fontWeight: FontWeight.w700, color: fira.onSurface)),
                    if (d.category!.description(lang).isNotEmpty)
                      Padding(
                        padding: const EdgeInsets.only(top: 4),
                        child: Text(d.category!.description(lang), style: TextStyle(fontSize: 13, height: 1.4, color: fira.mutedText)),
                      ),
                  ] else
                    Text(l10n.activitiesNoCategory, style: TextStyle(fontSize: 13.5, height: 1.4, color: fira.onSurface)),
                  if (d.category != null && !d.canChangeCategory) ...[
                    const SizedBox(height: 8),
                    Text(l10n.activitiesCategoryLocked, style: TextStyle(fontSize: 12, height: 1.4, color: fira.mutedText)),
                  ],
                  if (d.canEdit) ...[
                    const SizedBox(height: 8),
                    Align(
                      alignment: Alignment.centerLeft,
                      child: TextButton(
                        key: const Key('activities-change-category'),
                        style: TextButton.styleFrom(minimumSize: const Size(44, 44)),
                        onPressed: _saving ? null : _chooseCategory,
                        child: Text(d.category == null ? l10n.activitiesChooseCategory : l10n.activitiesChangeCategory,
                            style: TextStyle(color: fira.brandPrimary, fontWeight: FontWeight.w600)),
                      ),
                    ),
                  ],
                ]),
              ),
            ),
            const SizedBox(height: 16),
            if (!d.canEdit) ...[_notice(context, l10n.activitiesOwnerOnly), const SizedBox(height: 12)],
            for (final c in d.capabilities)
              Card(
                margin: const EdgeInsets.only(bottom: 10),
                child: Padding(
                  padding: const EdgeInsets.symmetric(vertical: 4),
                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    SwitchListTile(
                      key: Key('activity-${c.code}'),
                      value: _declared.contains(c.code),
                      activeThumbColor: fira.brandPrimary,
                      onChanged: (!d.canEdit || _saving)
                          ? null
                          : (on) => setState(() => on ? _declared.add(c.code) : _declared.remove(c.code)),
                      title: Text(capabilityLabel(l10n, c.code), style: const TextStyle(fontWeight: FontWeight.w600)),
                      // The saved status; it updates after Save.
                      subtitle: Align(alignment: Alignment.centerLeft, child: _status(context, c.status)),
                    ),
                    if (c.status == 'needs_documents' && d.canEdit)
                      Padding(
                        padding: const EdgeInsets.only(left: 8, bottom: 4),
                        child: TextButton.icon(
                          style: TextButton.styleFrom(minimumSize: const Size(44, 44)),
                          onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const OrganizationVerificationScreen())),
                          icon: Icon(Icons.upload_file, size: 18, color: fira.brandPrimary),
                          label: Text(l10n.activitiesAddDocument, style: TextStyle(color: fira.brandPrimary)),
                        ),
                      ),
                  ]),
                ),
              ),
            if (showLocalNote) ...[const SizedBox(height: 6), _notice(context, l10n.activitiesLocalSoon)],
            const SizedBox(height: 10),
            _notice(context, l10n.activitiesNotConfirmed),
          ],
        ),
      );
    }

    return Scaffold(
      backgroundColor: fira.surface,
      appBar: AppBar(backgroundColor: fira.surface, elevation: 0, title: Text(l10n.activitiesTitle)),
      body: SafeArea(child: body),
      bottomNavigationBar: (d != null && d.canEdit && _changed)
          ? SafeArea(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(20, 8, 20, 12),
                child: ElevatedButton(
                  key: const Key('activities-save'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: fira.brandPrimary,
                    foregroundColor: Theme.of(context).colorScheme.onPrimary,
                    minimumSize: const Size.fromHeight(52),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                    elevation: 0,
                  ),
                  onPressed: _saving ? null : _save,
                  child: _saving
                      ? SizedBox(width: 22, height: 22, child: CircularProgressIndicator(color: Theme.of(context).colorScheme.onPrimary, strokeWidth: 2))
                      : Text(l10n.activitiesSave, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                ),
              ),
            )
          : null,
    );
  }
}

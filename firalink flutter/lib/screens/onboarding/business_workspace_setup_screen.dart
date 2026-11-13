import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../l10n/app_localizations.dart';
import '../../models/organization_model.dart';
import '../../providers/workspace_provider.dart';
import '../../services/api_exception.dart';
import '../../theme/fira_colors.dart';
import '../../widgets/fira_error_state.dart';
import '../../widgets/workspace_status_pill.dart';
import 'intent_welcome_screen.dart';

/// Business setup (S-103). Used in onboarding (after Intent) and from "Add a business".
/// Entity types, roles and countries come from the server (one list, slice 3 maps documents to it).
class BusinessWorkspaceSetupScreen extends StatefulWidget {
  /// 'export' or 'buy' in onboarding; null when opened from the workspace switcher.
  final String? intent;

  const BusinessWorkspaceSetupScreen({super.key, this.intent});

  bool get isOnboarding => intent != null;

  @override
  State<BusinessWorkspaceSetupScreen> createState() => _BusinessWorkspaceSetupScreenState();
}

class _BusinessWorkspaceSetupScreenState extends State<BusinessWorkspaceSetupScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _tinController = TextEditingController();

  late Future<WorkspaceOptions> _optionsFuture;
  late String _role;
  String? _entityType;
  String _country = 'Ethiopia';
  bool _isCreating = false;
  bool _limitReached = false;
  Set<String> _serverInvalid = const {};

  @override
  void initState() {
    super.initState();
    _role = widget.intent == 'buy' ? 'buyer' : 'exporter';
    _optionsFuture = _loadOptions();
  }

  Future<WorkspaceOptions> _loadOptions() async {
    final options = await context.read<WorkspaceProvider>().loadOptions();
    if (mounted && !options.countries.contains(_country)) {
      setState(() => _country = options.defaultCountry);
    }
    return options;
  }

  @override
  void dispose() {
    _nameController.dispose();
    _tinController.dispose();
    super.dispose();
  }

  bool get _isEthiopia => _country == 'Ethiopia';

  /// Same rule as the server: Ethiopia 10 digits; elsewhere 4–30 letters, digits or dashes.
  String? _validateTin(AppLocalizations l10n, String? raw) {
    final value = (raw ?? '').replaceAll(RegExp(r'\s+'), '');
    if (value.isEmpty) return _serverInvalid.contains('tin_number') ? l10n.businessFixFields : null;
    if (_isEthiopia) {
      return RegExp(r'^\d{10}$').hasMatch(value) ? null : l10n.businessTinInvalidEthiopia;
    }
    return RegExp(r'^[A-Za-z0-9\-]{4,30}$').hasMatch(value) ? null : l10n.businessTinInvalidOther;
  }

  Future<void> _handleCreate() async {
    setState(() => _serverInvalid = const {});
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _isCreating = true;
      _limitReached = false;
    });
    try {
      await context.read<WorkspaceProvider>().createWorkspace(
            name: _nameController.text.trim(),
            businessRole: _role,
            entityType: _entityType!,
            country: _country,
            tinNumber: _tinController.text.replaceAll(RegExp(r'\s+'), ''),
          );
      if (!mounted) return;
      _finish(created: true);
    } on ApiException catch (e) {
      if (!mounted) return;
      if (e.code == 'WORKSPACE_LIMIT') {
        setState(() => _limitReached = true);
      } else if (e.statusCode == 422 && e.invalidFields.isNotEmpty) {
        setState(() => _serverInvalid = e.invalidFields);
        _formKey.currentState!.validate();
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(context.l10n.businessFixFields)));
      } else {
        showFiraActionError(context, e);
      }
    } catch (e) {
      if (mounted) showFiraActionError(context, e);
    } finally {
      if (mounted) setState(() => _isCreating = false);
    }
  }

  void _finish({required bool created}) {
    if (widget.isOnboarding) {
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(builder: (_) => IntentWelcomeScreen(intent: created ? widget.intent! : 'learn')),
      );
      return;
    }
    if (created) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(context.l10n.businessCreated)));
    }
    Navigator.pop(context);
  }

  InputDecoration _decoration(BuildContext context, {String? hint}) {
    return InputDecoration(
      hintText: hint,
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(16)),
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
    );
  }

  Widget _label(BuildContext context, String text, {String? trailing}) {
    final fira = context.fira;
    return Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: Text.rich(
        TextSpan(
          text: text,
          style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13, color: fira.onSurface),
          children: [
            if (trailing != null)
              TextSpan(
                text: ' $trailing',
                style: TextStyle(fontWeight: FontWeight.w400, color: fira.mutedText),
              ),
          ],
        ),
      ),
    );
  }

  Widget _notice(BuildContext context, String text, {bool danger = false}) {
    final fira = context.fira;
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: danger ? fira.danger.withValues(alpha: 0.10) : fira.brandTint,
        borderRadius: BorderRadius.circular(16),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(danger ? Icons.error_outline : Icons.info_outline,
              size: 18, color: danger ? fira.danger : fira.brandPrimary),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              text,
              style: TextStyle(fontSize: 12.5, height: 1.4, color: danger ? fira.danger : fira.onSurface),
            ),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final fira = context.fira;
    final l10n = context.l10n;
    final onPrimary = Theme.of(context).colorScheme.onPrimary;

    return Scaffold(
      backgroundColor: fira.surface,
      appBar: AppBar(
        backgroundColor: fira.surface,
        elevation: 0,
        leading: IconButton(
          icon: Icon(Icons.arrow_back, color: fira.onSurface),
          onPressed: () => Navigator.pop(context),
        ),
      ),
      body: SafeArea(
        child: FutureBuilder<WorkspaceOptions>(
          future: _optionsFuture,
          builder: (context, snapshot) {
            if (snapshot.hasError) {
              return FiraErrorState(
                error: snapshot.error,
                onRetry: () => setState(() => _optionsFuture = _loadOptions()),
              );
            }
            if (!snapshot.hasData) {
              return Center(child: CircularProgressIndicator(color: fira.brandPrimary));
            }
            final options = snapshot.data!;
            final selectedEntity = options.entityTypes.where((e) => e.value == _entityType).firstOrNull;
            final roles = options.businessRoles.isEmpty
                ? const ['exporter', 'supplier', 'broker', 'buyer']
                : options.businessRoles;

            return SingleChildScrollView(
              padding: const EdgeInsets.symmetric(horizontal: 24.0, vertical: 12.0),
              child: Form(
                key: _formKey,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    if (widget.isOnboarding) ...[
                      Text(
                        l10n.businessStepLabel,
                        style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: fira.brandPrimary),
                      ),
                      const SizedBox(height: 6),
                    ],
                    Text(
                      l10n.businessSetupTitle,
                      style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold, color: fira.onSurface),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      l10n.businessSetupSubtitle,
                      style: TextStyle(fontSize: 13, color: fira.mutedText, height: 1.4),
                    ),
                    const SizedBox(height: 24),

                    _label(context, l10n.businessNameLabel),
                    TextFormField(
                      controller: _nameController,
                      textCapitalization: TextCapitalization.words,
                      maxLength: 160,
                      decoration: _decoration(context).copyWith(counterText: ''),
                      validator: (v) {
                        if (v == null || v.trim().length < 2) return l10n.businessNameRequired;
                        return _serverInvalid.contains('name') ? l10n.businessFixFields : null;
                      },
                    ),
                    const SizedBox(height: 18),

                    _label(context, l10n.businessRoleLabel),
                    Wrap(
                      spacing: 8,
                      runSpacing: 8,
                      children: [
                        for (final role in roles)
                          ChoiceChip(
                            label: Text(WorkspaceLabels.businessRole(l10n, role)),
                            selected: _role == role,
                            showCheckmark: false,
                            selectedColor: fira.chipSelectedBg,
                            backgroundColor: fira.chipUnselectedBg,
                            labelStyle: TextStyle(
                              fontWeight: FontWeight.w600,
                              color: _role == role ? fira.brandPrimary : fira.onSurface,
                            ),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                            onSelected: (_) => setState(() => _role = role),
                          ),
                      ],
                    ),
                    const SizedBox(height: 18),

                    _label(context, l10n.entityTypeLabel),
                    DropdownButtonFormField<String>(
                      initialValue: _entityType,
                      isExpanded: true,
                      hint: Text(l10n.entityTypeHint),
                      decoration: _decoration(context),
                      items: [
                        for (final e in options.entityTypes)
                          DropdownMenuItem(
                            value: e.value,
                            child: Text(
                              WorkspaceLabels.entityType(l10n, e.value, fallback: e.label),
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                      ],
                      validator: (v) => v == null ? l10n.entityTypeRequired : null,
                      onChanged: (v) => setState(() => _entityType = v),
                    ),
                    if (selectedEntity?.needsManualReview == true) ...[
                      const SizedBox(height: 10),
                      _notice(context, l10n.entityOtherNotice),
                    ],
                    const SizedBox(height: 18),

                    _label(context, l10n.businessCountryLabel),
                    DropdownButtonFormField<String>(
                      initialValue: options.countries.contains(_country) ? _country : null,
                      isExpanded: true,
                      decoration: _decoration(context),
                      items: [
                        for (final c in options.countries) DropdownMenuItem(value: c, child: Text(c)),
                      ],
                      onChanged: (v) {
                        if (v == null) return;
                        setState(() => _country = v);
                        if (_tinController.text.isNotEmpty) _formKey.currentState?.validate();
                      },
                    ),
                    const SizedBox(height: 18),

                    _label(context, l10n.businessTinLabel, trailing: l10n.businessTinOptional),
                    TextFormField(
                      controller: _tinController,
                      keyboardType: _isEthiopia ? TextInputType.number : TextInputType.text,
                      maxLength: _isEthiopia ? 10 : 30,
                      decoration: _decoration(context).copyWith(counterText: ''),
                      validator: (v) => _validateTin(l10n, v),
                    ),
                    const SizedBox(height: 18),

                    if (_limitReached) ...[
                      _notice(
                        context,
                        l10n.translate('businessLimitReached', {'max': options.maxOwnedBusinesses}),
                        danger: true,
                      ),
                      const SizedBox(height: 12),
                    ],
                    _notice(context, l10n.businessVerificationNotice),
                    const SizedBox(height: 28),

                    ElevatedButton(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: fira.brandPrimary,
                        foregroundColor: onPrimary,
                        minimumSize: const Size.fromHeight(52),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                        elevation: 0,
                      ),
                      onPressed: (_isCreating || _limitReached) ? null : _handleCreate,
                      child: _isCreating
                          ? SizedBox(
                              width: 22,
                              height: 22,
                              child: CircularProgressIndicator(color: onPrimary, strokeWidth: 2),
                            )
                          : Text(l10n.businessCreate, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                    ),
                    const SizedBox(height: 8),
                    TextButton(
                      onPressed: _isCreating ? null : () => _finish(created: false),
                      child: Text(l10n.businessLater, style: TextStyle(color: fira.mutedText)),
                    ),
                    const SizedBox(height: 12),
                  ],
                ),
              ),
            );
          },
        ),
      ),
    );
  }
}

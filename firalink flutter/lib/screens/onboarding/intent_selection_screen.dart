import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../l10n/app_localizations.dart';
import '../../providers/workspace_provider.dart';
import '../../theme/fira_colors.dart';
import '../../widgets/fira_error_state.dart';
import 'business_workspace_setup_screen.dart';
import 'intent_welcome_screen.dart';

/// Intent (S-102): multi-select and skippable. Sell or buy leads to Business setup
/// (the server answers needs_business_setup); everything else goes to the home flow.
class IntentSelectionScreen extends StatefulWidget {
  const IntentSelectionScreen({super.key});

  @override
  State<IntentSelectionScreen> createState() => _IntentSelectionScreenState();
}

class _IntentOption {
  const _IntentOption(this.id, this.icon);
  final String id;
  final IconData icon;
}

class _IntentSelectionScreenState extends State<IntentSelectionScreen> {
  static const _options = [
    _IntentOption('export', Icons.local_shipping_outlined),
    _IntentOption('buy', Icons.shopping_bag_outlined),
    _IntentOption('learn', Icons.school_outlined),
    _IntentOption('events', Icons.explore_outlined),
  ];

  final Set<String> _selected = {};
  bool _isSaving = false;

  (String, String) _texts(AppLocalizations l10n, String id) {
    switch (id) {
      case 'export':
        return (l10n.intentSellTitle, l10n.intentSellSubtitle);
      case 'buy':
        return (l10n.intentBuyTitle, l10n.intentBuySubtitle);
      case 'learn':
        return (l10n.intentLearnTitle, l10n.intentLearnSubtitle);
      default:
        return (l10n.intentEventsTitle, l10n.intentEventsSubtitle);
    }
  }

  /// Keeps the order of the list so the first choice is stable.
  List<String> get _orderedSelection => [for (final o in _options) if (_selected.contains(o.id)) o.id];

  Future<void> _handleContinue() async {
    final intents = _orderedSelection;
    setState(() => _isSaving = true);
    try {
      final needsBusiness = await context.read<WorkspaceProvider>().saveIntents(intents);
      if (!mounted) return;
      _goNext(intents, needsBusiness);
    } catch (e) {
      if (mounted) showFiraActionError(context, e);
    } finally {
      if (mounted) setState(() => _isSaving = false);
    }
  }

  Future<void> _handleSkip() async {
    // Skipping records an empty choice; the next screen does not depend on it succeeding.
    try {
      await context.read<WorkspaceProvider>().saveIntents(const []);
    } catch (_) {}
    if (!mounted) return;
    _goNext(const [], false);
  }

  void _goNext(List<String> intents, bool needsBusiness) {
    if (needsBusiness) {
      Navigator.push(
        context,
        MaterialPageRoute(
          builder: (_) => BusinessWorkspaceSetupScreen(intent: intents.contains('export') ? 'export' : 'buy'),
        ),
      );
      return;
    }
    final welcome = intents.contains('events') && !intents.contains('learn') ? 'events' : 'learn';
    Navigator.pushReplacement(
      context,
      MaterialPageRoute(builder: (_) => IntentWelcomeScreen(intent: welcome)),
    );
  }

  @override
  Widget build(BuildContext context) {
    final fira = context.fira;
    final l10n = context.l10n;
    final theme = Theme.of(context);

    return Scaffold(
      backgroundColor: fira.surface,
      appBar: AppBar(
        backgroundColor: fira.surface,
        elevation: 0,
        leading: Navigator.canPop(context)
            ? IconButton(
                icon: Icon(Icons.arrow_back, color: fira.onSurface),
                onPressed: () => Navigator.pop(context),
              )
            : null,
        actions: [
          TextButton(
            onPressed: _isSaving ? null : _handleSkip,
            child: Text(l10n.intentSkip, style: TextStyle(color: fira.mutedText)),
          ),
        ],
      ),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 24.0, vertical: 12.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text(
                l10n.intentStepLabel,
                style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: fira.brandPrimary),
              ),
              const SizedBox(height: 6),
              Text(
                l10n.intentTitle,
                style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: fira.onSurface),
              ),
              const SizedBox(height: 8),
              Text(
                l10n.intentSubtitle,
                style: TextStyle(fontSize: 13, color: fira.mutedText, height: 1.4),
              ),
              const SizedBox(height: 24),
              Expanded(
                child: ListView.separated(
                  itemCount: _options.length + 1,
                  separatorBuilder: (_, _) => const SizedBox(height: 14),
                  itemBuilder: (context, index) {
                    if (index == _options.length) {
                      return Text(
                        l10n.intentHint,
                        style: TextStyle(fontSize: 12, color: fira.mutedText, height: 1.4),
                      );
                    }
                    final option = _options[index];
                    final isSelected = _selected.contains(option.id);
                    final (title, subtitle) = _texts(l10n, option.id);

                    return Semantics(
                      checked: isSelected,
                      button: true,
                      child: InkWell(
                        onTap: () => setState(() {
                          isSelected ? _selected.remove(option.id) : _selected.add(option.id);
                        }),
                        borderRadius: BorderRadius.circular(22),
                        child: AnimatedContainer(
                          duration: const Duration(milliseconds: 200),
                          padding: const EdgeInsets.all(18),
                          decoration: BoxDecoration(
                            borderRadius: BorderRadius.circular(22),
                            border: Border.all(
                              color: isSelected ? fira.brandPrimary : theme.dividerColor,
                              width: isSelected ? 2 : 1,
                            ),
                            color: isSelected ? fira.brandTint : fira.card,
                          ),
                          child: Row(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Container(
                                padding: const EdgeInsets.all(12),
                                decoration: BoxDecoration(
                                  color: fira.brandPrimary.withValues(alpha: 0.12),
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: Icon(option.icon, color: fira.brandPrimary, size: 26),
                              ),
                              const SizedBox(width: 16),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      title,
                                      style: TextStyle(
                                        fontSize: 16,
                                        fontWeight: FontWeight.bold,
                                        color: isSelected ? fira.brandPrimary : fira.onSurface,
                                      ),
                                    ),
                                    const SizedBox(height: 4),
                                    Text(
                                      subtitle,
                                      style: TextStyle(fontSize: 12, color: fira.mutedText, height: 1.3),
                                    ),
                                  ],
                                ),
                              ),
                              const SizedBox(width: 10),
                              Checkbox(
                                value: isSelected,
                                activeColor: fira.brandPrimary,
                                onChanged: (v) => setState(() {
                                  v == true ? _selected.add(option.id) : _selected.remove(option.id);
                                }),
                              ),
                            ],
                          ),
                        ),
                      ),
                    );
                  },
                ),
              ),
              ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: fira.brandPrimary,
                  foregroundColor: theme.colorScheme.onPrimary,
                  minimumSize: const Size.fromHeight(52),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                  elevation: 0,
                ),
                onPressed: (_isSaving || _selected.isEmpty) ? null : _handleContinue,
                child: _isSaving
                    ? SizedBox(
                        width: 22,
                        height: 22,
                        child: CircularProgressIndicator(color: theme.colorScheme.onPrimary, strokeWidth: 2),
                      )
                    : Text(l10n.intentContinue, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
              ),
              const SizedBox(height: 12),
            ],
          ),
        ),
      ),
    );
  }
}

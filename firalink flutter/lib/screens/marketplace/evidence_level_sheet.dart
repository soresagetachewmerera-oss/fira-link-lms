import 'package:flutter/material.dart';

import '../../l10n/app_localizations.dart';
import '../../theme/fira_colors.dart';
import '../../widgets/verification_labels.dart';

/// "What does this level mean?" — a read-only explanation of lot evidence levels.
/// Nobody picks a level here: it comes from evidence our reviewers approved.
class EvidenceLevelSheet extends StatelessWidget {
  const EvidenceLevelSheet({super.key, this.currentLevel = 'claimed'});

  final String currentLevel;

  static Future<void> show(BuildContext context, {String currentLevel = 'claimed'}) {
    return showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      showDragHandle: true,
      builder: (_) => EvidenceLevelSheet(currentLevel: currentLevel),
    );
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final fira = context.fira;
    final levels = [
      ('claimed', l10n.evidenceClaimedBody),
      ('documented', l10n.evidenceDocumentedBody),
      ('lab_backed', l10n.evidenceLabBody),
      ('inspected', l10n.evidenceInspectedBody),
    ];

    return SafeArea(
      child: ListView(
        shrinkWrap: true,
        padding: const EdgeInsets.fromLTRB(20, 0, 20, 24),
        children: [
          Text(l10n.evidenceWhatLevelMeans, style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700)),
          const SizedBox(height: 8),
          Text(l10n.evidenceIntro, style: TextStyle(color: fira.mutedText)),
          const SizedBox(height: 12),
          for (final (level, body) in levels)
            Card(
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(22),
                side: level == currentLevel ? BorderSide(color: fira.brandPrimary, width: 2) : BorderSide.none,
              ),
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(children: [LotLevelChip(level: level)]),
                    const SizedBox(height: 8),
                    Text(body),
                  ],
                ),
              ),
            ),
        ],
      ),
    );
  }
}

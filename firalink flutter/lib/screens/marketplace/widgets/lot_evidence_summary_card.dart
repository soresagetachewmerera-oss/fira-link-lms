import 'package:flutter/material.dart';

import '../../../l10n/app_localizations.dart';
import '../../../theme/fira_colors.dart';
import '../../../widgets/verification_labels.dart';
import '../evidence_level_sheet.dart';

/// Lot detail (S-015): the evidence level with what backs it. Data from the server's public
/// summary: approved photos without location, the lot code and the expiry. Never lab files.
class LotEvidenceSummaryCard extends StatelessWidget {
  const LotEvidenceSummaryCard({super.key, required this.evidence});

  final Map<String, dynamic> evidence;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final fira = context.fira;
    final level = evidence['level']?.toString() ?? 'claimed';
    final photos = (evidence['photos'] as List? ?? []).whereType<Map>().toList();
    final code = evidence['lot_code']?.toString();
    final validUntil = DateTime.tryParse(evidence['valid_until']?.toString() ?? '');
    final takenDates = photos.map((p) => DateTime.tryParse(p['taken_on']?.toString() ?? '')).whereType<DateTime>().toList()..sort();

    return Card(
      key: const Key('lot-evidence-card'),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(l10n.translate('lotEvidenceLevel', {'level': VerificationLabels.level(l10n, level)}),
                style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15)),
            const SizedBox(height: 8),
            if (photos.isNotEmpty) ...[
              Text(l10n.translate('lotPhotosTaken', {
                'n': photos.length,
                'date': VerificationLabels.date(context, takenDates.isEmpty ? null : takenDates.last),
              })),
              const SizedBox(height: 8),
              SizedBox(
                height: 72,
                child: ListView.separated(
                  scrollDirection: Axis.horizontal,
                  itemCount: photos.length,
                  separatorBuilder: (_, _) => const SizedBox(width: 8),
                  itemBuilder: (_, i) => ClipRRect(
                    borderRadius: BorderRadius.circular(12),
                    child: Image.network(
                      photos[i]['url'].toString(),
                      width: 72,
                      height: 72,
                      fit: BoxFit.cover,
                      semanticLabel: VerificationLabels.lotKind(l10n, photos[i]['kind']?.toString() ?? ''),
                      errorBuilder: (_, _, _) => Container(width: 72, height: 72, color: fira.chipUnselectedBg),
                    ),
                  ),
                ),
              ),
            ],
            if (code != null && code.isNotEmpty && photos.isNotEmpty) ...[
              const SizedBox(height: 8),
              Text(l10n.translate('lotCodeVisible', {'code': code})),
            ],
            if (evidence['lab_report_checked'] == true) ...[
              const SizedBox(height: 4),
              Text(l10n.lotLabChecked),
            ],
            if (validUntil != null) ...[
              const SizedBox(height: 4),
              Text(l10n.translate('lotEvidenceExpires', {'date': VerificationLabels.date(context, validUntil)}),
                  style: TextStyle(color: fira.mutedText)),
            ],
            if (level == 'claimed') Text(l10n.evidenceClaimedBody, style: TextStyle(color: fira.mutedText)),
            TextButton(
              style: TextButton.styleFrom(padding: EdgeInsets.zero),
              onPressed: () => EvidenceLevelSheet.show(context, currentLevel: level),
              child: Text(l10n.evidenceWhatLevelMeans),
            ),
          ],
        ),
      ),
    );
  }
}

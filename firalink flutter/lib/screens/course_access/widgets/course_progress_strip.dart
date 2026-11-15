import 'package:flutter/material.dart';

import '../../../l10n/app_localizations.dart';
import '../../../theme/fira_colors.dart';

/// Academy flow: "Lesson 3 of 12", progress %, up next and "Ask Fira AI about this lesson".
/// Numbers come from the server (`progress` on the course, `position` on the lesson).
class CourseProgressStrip extends StatelessWidget {
  const CourseProgressStrip({super.key, required this.progress, this.position, this.onAskAi});

  final Map<String, dynamic>? progress;
  final Map<String, dynamic>? position;
  final VoidCallback? onAskAi;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final fira = context.fira;
    final percent = int.tryParse('${progress?['percent'] ?? 0}') ?? 0;
    final upNext = progress?['up_next'] is Map ? progress!['up_next'] as Map : null;
    final n = position?['number'];
    final total = position?['total'];

    return Padding(
      key: const Key('course-progress'),
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Row(children: [
          if (n != null && total != null)
            Expanded(child: Text(l10n.translate('courseLessonOf', {'n': n, 'total': total}), style: TextStyle(color: fira.mutedText))),
          if (n == null || total == null) const Spacer(),
          Text(l10n.translate('courseProgressPercent', {'n': percent}), style: TextStyle(color: fira.brandPrimary, fontWeight: FontWeight.w700)),
        ]),
        const SizedBox(height: 6),
        ClipRRect(
          borderRadius: BorderRadius.circular(12),
          child: LinearProgressIndicator(value: percent / 100, minHeight: 6, color: fira.brandPrimary, backgroundColor: fira.brandTint),
        ),
        if (upNext != null) ...[
          const SizedBox(height: 6),
          Text(l10n.translate('courseUpNext', {'title': upNext['title'] ?? ''}), key: const Key('course-up-next'), style: TextStyle(color: fira.mutedText, fontSize: 12)),
        ],
        if (onAskAi != null)
          Align(
            alignment: Alignment.centerLeft,
            child: TextButton.icon(
              key: const Key('course-ask-ai'),
              onPressed: onAskAi,
              icon: const Icon(Icons.auto_awesome_outlined, size: 18),
              label: Text(l10n.courseAskAi),
            ),
          ),
      ]),
    );
  }
}

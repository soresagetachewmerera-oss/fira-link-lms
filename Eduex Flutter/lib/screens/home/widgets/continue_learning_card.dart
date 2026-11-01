import 'package:flutter/material.dart';
import '../../course_access/course_access_screen.dart';
import '../models/continue_learning_data.dart';
import 'home_design_constants.dart';
import '../../../l10n/app_localizations.dart';

class ContinueLearningCard extends StatelessWidget {
  const ContinueLearningCard({
    super.key,
    required this.course,
  });

  final ContinueLearningCourse? course;

  @override
  Widget build(BuildContext context) {
    if (course == null) {
      return const SizedBox.shrink();
    }

    final double progress = course!.progressPercent.clamp(0.0, 1.0);
    final int percentInt = (progress * 100).round();
    final Color cardBg = HomeDesignConstants.surface(context);
    final Color textColor = HomeDesignConstants.text(context);
    final Color mutedColor = HomeDesignConstants.mutedText(context);
    final Color primaryColor = HomeDesignConstants.brandPrimary(context);
    final Color tintColor = HomeDesignConstants.brandTint(context);

    return Padding(
      padding: const EdgeInsets.symmetric(
        horizontal: HomeDesignConstants.horizontalPadding,
      ),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        decoration: BoxDecoration(
          color: cardBg,
          borderRadius: BorderRadius.circular(HomeDesignConstants.radiusLargeCard),
          boxShadow: HomeDesignConstants.softShadow(context),
          border: Border.all(
            color: primaryColor.withValues(alpha: 0.12),
            width: 1.0,
          ),
        ),
        child: Row(
          children: <Widget>[
            // Circular progress indicator with percentage inside
            SizedBox(
              width: 52,
              height: 52,
              child: Stack(
                alignment: Alignment.center,
                children: [
                  CircularProgressIndicator(
                    value: progress,
                    strokeWidth: 4.5,
                    backgroundColor: tintColor,
                    valueColor: AlwaysStoppedAnimation<Color>(primaryColor),
                  ),
                  Text(
                    '$percentInt%',
                    style: TextStyle(
                      color: textColor,
                      fontSize: 12,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(width: 14),

            // Course title and Lesson progress
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: <Widget>[
                  Text(
                    course!.title,
                    style: TextStyle(
                      color: textColor,
                      fontSize: 14,
                      fontWeight: FontWeight.w700,
                      letterSpacing: -0.1,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 4),
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                        decoration: BoxDecoration(
                          color: tintColor,
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Text(
                          context.l10n.inProgress.toUpperCase(),
                          style: TextStyle(
                            color: primaryColor,
                            fontSize: 9.5,
                            fontWeight: FontWeight.w800,
                            letterSpacing: 0.4,
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Flexible(
                        child: Text(
                          '${context.l10n.lesson} ${course!.currentLesson}/${course!.totalLessons}',
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            color: mutedColor,
                            fontSize: 12,
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(width: 10),

            // Purple gradient "Resume" button
            Semantics(
              label: '${context.l10n.resume} ${context.l10n.lesson} ${course!.currentLesson}',
              button: true,
              child: InkWell(
                onTap: () {
                  Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (context) => CourseAccessScreen(
                        courseId: course!.courseId,
                        courseTitle: course!.title,
                      ),
                    ),
                  );
                },
                borderRadius: BorderRadius.circular(16),
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                  decoration: BoxDecoration(
                    gradient: const LinearGradient(
                      colors: [Color(0xFF6D1A95), Color(0xFF4A0E66)],
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                    ),
                    borderRadius: BorderRadius.circular(16),
                    boxShadow: [
                      BoxShadow(
                        color: const Color(0xFF6D1A95).withValues(alpha: 0.3),
                        blurRadius: 8,
                        offset: const Offset(0, 3),
                      ),
                    ],
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(
                        context.l10n.resume,
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 12.5,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      const SizedBox(width: 4),
                      const Icon(
                        Icons.play_arrow_rounded,
                        color: Colors.white,
                        size: 16,
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

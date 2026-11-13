class ContinueLearningCourse {
  final int courseId;
  final String title;
  final int currentLesson;
  final int totalLessons;
  final double progressPercent; // 0.0 to 1.0

  const ContinueLearningCourse({
    required this.courseId,
    required this.title,
    required this.currentLesson,
    required this.totalLessons,
    required this.progressPercent,
  });

  factory ContinueLearningCourse.fromJson(Map<String, dynamic> json) {
    return ContinueLearningCourse(
      courseId: (json['course_id'] as num?)?.toInt() ?? 0,
      title: json['title']?.toString() ?? '',
      currentLesson: (json['current_lesson'] as num?)?.toInt() ?? 1,
      totalLessons: (json['total_lessons'] as num?)?.toInt() ?? 1,
      progressPercent: (json['progress'] as num?)?.toDouble() ?? 0,
    );
  }
}

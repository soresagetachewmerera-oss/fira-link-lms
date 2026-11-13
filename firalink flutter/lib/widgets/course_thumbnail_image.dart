import 'package:flutter/material.dart';
import '../config/config.dart';
import '../theme/app_theme.dart';

class CourseThumbnailImage extends StatelessWidget {
  final String? imageUrl;
  final int? courseId;
  final double? width;
  final double? height;
  final BoxFit fit;
  final BorderRadius? borderRadius;

  const CourseThumbnailImage({
    super.key,
    required this.imageUrl,
    this.courseId,
    this.width,
    this.height,
    this.fit = BoxFit.cover,
    this.borderRadius,
  });

  String get _fallbackAsset {
    final id = courseId ?? 1;
    final index = ((id - 1) % 5) + 1;
    return 'assets/img/courses/course$index.jpg';
  }

  Widget _buildFallback(BuildContext context) {
    return Image.asset(
      _fallbackAsset,
      width: width,
      height: height,
      fit: fit,
      errorBuilder: (context, error, stackTrace) => Container(
        width: width,
        height: height,
        color: AppTheme.getMint200(context),
        child: const Center(
          child: Icon(Icons.school, color: Colors.white, size: 28),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final trimmed = imageUrl?.trim() ?? '';

    Widget content;
    if (trimmed.isEmpty) {
      content = _buildFallback(context);
    } else if (trimmed.startsWith('assets/')) {
      content = Image.asset(
        trimmed,
        width: width,
        height: height,
        fit: fit,
        errorBuilder: (context, error, stackTrace) => _buildFallback(context),
      );
    } else {
      final resolvedUrl = AppConfig.getImageUrl(trimmed);
      content = Image.network(
        resolvedUrl,
        width: width,
        height: height,
        fit: fit,
        loadingBuilder: (context, child, loadingProgress) {
          if (loadingProgress == null) return child;
          return Container(
            width: width,
            height: height,
            color: AppTheme.getMint100(context),
            child: const Center(
              child: SizedBox(
                width: 20,
                height: 20,
                child: CircularProgressIndicator(
                  strokeWidth: 2,
                  valueColor: AlwaysStoppedAnimation<Color>(Colors.white70),
                ),
              ),
            ),
          );
        },
        errorBuilder: (context, error, stackTrace) => _buildFallback(context),
      );
    }

    if (borderRadius != null) {
      return ClipRRect(
        borderRadius: borderRadius!,
        child: content,
      );
    }

    return content;
  }
}

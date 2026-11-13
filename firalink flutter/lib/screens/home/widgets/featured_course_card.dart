import 'dart:ui';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:hugeicons/hugeicons.dart';
import 'package:provider/provider.dart';

import '../../../models/course.dart';
import '../../../providers/settings_provider.dart';
import '../../../providers/wishlist_provider.dart';
import '../../../widgets/course_thumbnail_image.dart';
import '../../course_detail/course_detail_screen.dart';
import 'home_design_constants.dart';

class FeaturedCourseCard extends StatelessWidget {
  const FeaturedCourseCard({
    super.key,
    required this.course,
    this.width = 236.0,
  });

  final Course course;
  final double width;

  @override
  Widget build(BuildContext context) {
    final settingsProvider = Provider.of<SettingsProvider>(context);
    final Color cardBg = HomeDesignConstants.surface(context);
    final Color textColor = HomeDesignConstants.text(context);
    final Color mutedColor = HomeDesignConstants.mutedText(context);
    final Color primaryColor = HomeDesignConstants.brandPrimary(context);
    final Color tintColor = HomeDesignConstants.brandTint(context);

    final double regularPrice = course.price ?? 0.0;
    final double? discounted = course.discountedPrice;
    final bool isFree = regularPrice == 0.0;
    final bool hasDiscount = !isFree && discounted != null && discounted < regularPrice;

    int discountPercent = 0;
    if (hasDiscount && regularPrice > 0) {
      discountPercent = (((regularPrice - discounted) / regularPrice) * 100).round();
    }

    // Rating text logic
    final bool hasReviews = course.reviewsCount > 0 && (course.rating ?? 0.0) > 0;
    final String ratingText = hasReviews
        ? '${(course.rating!).toStringAsFixed(1)} (${course.reviewsCount})'
        : 'New course';

    final String level = (course.difficulty != null && course.difficulty!.isNotEmpty)
        ? course.difficulty!
        : 'All Levels';

    return Semantics(
      label: '${course.title}, $level, $ratingText',
      button: true,
      child: GestureDetector(
        onTap: () {
          Navigator.of(context).push(
            MaterialPageRoute(
              builder: (context) => CourseDetailScreen(courseId: course.id),
            ),
          );
        },
        child: Container(
          width: width,
          decoration: BoxDecoration(
            color: cardBg,
            borderRadius: BorderRadius.circular(HomeDesignConstants.radiusLargeCard),
            boxShadow: HomeDesignConstants.softShadow(
              context,
              blur: 14,
              offset: const Offset(0, 4),
            ),
            border: Border.all(
              color: tintColor.withValues(alpha: 0.6),
              width: 1.0,
            ),
          ),
          clipBehavior: Clip.antiAlias,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: <Widget>[
              // 16:10 Aspect Ratio Image Header
              AspectRatio(
                aspectRatio: 16 / 10,
                child: Stack(
                  fit: StackFit.expand,
                  children: <Widget>[
                    CourseThumbnailImage(
                      imageUrl: course.thumbnail,
                      courseId: course.id,
                      fit: BoxFit.cover,
                    ),

                    // Soft bottom gradient overlay
                    Positioned.fill(
                      child: DecoratedBox(
                        decoration: BoxDecoration(
                          gradient: LinearGradient(
                            begin: Alignment.topCenter,
                            end: Alignment.bottomCenter,
                            colors: [
                              Colors.transparent,
                              Colors.black.withValues(alpha: 0.45),
                            ],
                            stops: const [0.55, 1.0],
                          ),
                        ),
                      ),
                    ),

                    // Frosted Bookmark Button (top right)
                    Positioned(
                      top: 8,
                      right: 8,
                      child: Consumer<WishlistProvider>(
                        builder: (context, wishlist, _) {
                          final bool isSaved = wishlist.isInWishlist(course.id);
                          return Semantics(
                            label: isSaved ? 'Remove from favorites' : 'Add to favorites',
                            button: true,
                            child: InkWell(
                              onTap: () {
                                HapticFeedback.selectionClick();
                                wishlist.toggleWishlist(course);
                              },
                              borderRadius: BorderRadius.circular(16),
                              child: ClipRRect(
                                borderRadius: BorderRadius.circular(16),
                                child: BackdropFilter(
                                  filter: ImageFilter.blur(sigmaX: 8, sigmaY: 8),
                                  child: Container(
                                    width: 32,
                                    height: 32,
                                    decoration: BoxDecoration(
                                      color: isSaved
                                          ? Colors.white
                                          : Colors.black.withValues(alpha: 0.28),
                                      shape: BoxShape.circle,
                                    ),
                                    child: Center(
                                      child: HugeIcon(
                                        icon: HugeIcons.strokeRoundedBookmark01,
                                        size: 16,
                                        color: isSaved ? primaryColor : Colors.white,
                                      ),
                                    ),
                                  ),
                                ),
                              ),
                            ),
                          );
                        },
                      ),
                    ),

                    // Level and Lesson count frosted tags (bottom left)
                    Positioned(
                      left: 8,
                      bottom: 8,
                      right: 8,
                      child: Wrap(
                        spacing: 6,
                        runSpacing: 4,
                        children: [
                          _buildFrostedTag(level),
                          _buildFrostedTag('${course.lessonsCount} lessons'),
                        ],
                      ),
                    ),
                  ],
                ),
              ),

              // Course Details
              Padding(
                padding: const EdgeInsets.all(12),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: <Widget>[
                    // Title: max 2 lines with ellipsis
                    Text(
                      course.title,
                      style: TextStyle(
                        color: textColor,
                        fontSize: 13.5,
                        fontWeight: FontWeight.w700,
                        height: 1.3,
                        letterSpacing: -0.15,
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 6),

                    // Instructor
                    Text(
                      course.instructor?.name ?? course.instructorName ?? 'Fira Link Trade Faculty',
                      style: TextStyle(
                        color: mutedColor,
                        fontSize: 11,
                        fontWeight: FontWeight.w500,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 8),

                    // Rating & Reviews
                    Row(
                      children: <Widget>[
                        Icon(
                          Icons.star_rounded,
                          size: 15,
                          color: hasReviews ? const Color(0xFFF59E0B) : mutedColor,
                        ),
                        const SizedBox(width: 3),
                        Text(
                          ratingText,
                          style: TextStyle(
                            color: mutedColor,
                            fontSize: 11,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 10),

                    // Pricing Section
                    Wrap(
                      crossAxisAlignment: WrapCrossAlignment.center,
                      spacing: 6,
                      runSpacing: 4,
                      children: <Widget>[
                        if (isFree)
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                            decoration: BoxDecoration(
                              color: tintColor,
                              borderRadius: BorderRadius.circular(HomeDesignConstants.radiusPill),
                            ),
                            child: Text(
                              'Free',
                              style: TextStyle(
                                color: primaryColor,
                                fontSize: 12,
                                fontWeight: FontWeight.w800,
                              ),
                            ),
                          )
                        else if (hasDiscount) ...[
                          Text(
                            settingsProvider.formatPrice(discounted),
                            style: TextStyle(
                              color: primaryColor,
                              fontSize: 13.5,
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                          Text(
                            settingsProvider.formatPrice(regularPrice),
                            style: TextStyle(
                              color: mutedColor,
                              fontSize: 11,
                              fontWeight: FontWeight.w500,
                              decoration: TextDecoration.lineThrough,
                            ),
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                            decoration: BoxDecoration(
                              color: primaryColor,
                              borderRadius: BorderRadius.circular(6),
                            ),
                            child: Text(
                              '-$discountPercent%',
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 9.5,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                        ] else
                          Text(
                            settingsProvider.formatPrice(regularPrice),
                            style: TextStyle(
                              color: textColor,
                              fontSize: 13.5,
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildFrostedTag(String text) {
    return ClipRRect(
      borderRadius: BorderRadius.circular(8),
      child: BackdropFilter(
        filter: ImageFilter.blur(sigmaX: 6, sigmaY: 6),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
          color: Colors.black.withValues(alpha: 0.35),
          child: Text(
            text,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 10,
              fontWeight: FontWeight.w600,
              letterSpacing: 0.1,
            ),
          ),
        ),
      ),
    );
  }
}

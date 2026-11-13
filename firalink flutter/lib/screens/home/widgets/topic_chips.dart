import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'home_design_constants.dart';

class TopicChips extends StatelessWidget {
  const TopicChips({
    super.key,
    required this.selectedTopic,
    required this.onTopicSelected,
  });

  final String selectedTopic;
  final ValueChanged<String> onTopicSelected;

  static const List<String> topics = [
    'All',
    'Sourcing',
    'Cupping & quality',
    'Export docs',
    'Logistics',
    'Finance',
    'Buyers',
  ];

  @override
  Widget build(BuildContext context) {
    final Color primaryColor = HomeDesignConstants.brandPrimary(context);
    final Color tintColor = HomeDesignConstants.brandTint(context);

    return SizedBox(
      height: 38,
      child: ListView.separated(
        padding: const EdgeInsets.symmetric(
          horizontal: HomeDesignConstants.horizontalPadding,
        ),
        scrollDirection: Axis.horizontal,
        physics: const BouncingScrollPhysics(),
        itemCount: topics.length,
        separatorBuilder: (_, _) => const SizedBox(width: 8),
        itemBuilder: (context, index) {
          final String topic = topics[index];
          final bool isSelected = topic == selectedTopic;

          return Semantics(
            label: '$topic topic filter',
            selected: isSelected,
            button: true,
            child: InkWell(
              onTap: () {
                HapticFeedback.selectionClick();
                onTopicSelected(topic);
              },
              borderRadius: BorderRadius.circular(HomeDesignConstants.radiusPill),
              child: AnimatedContainer(
                duration: const Duration(milliseconds: 200),
                curve: Curves.easeInOut,
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                decoration: BoxDecoration(
                  color: isSelected ? primaryColor : tintColor,
                  borderRadius: BorderRadius.circular(HomeDesignConstants.radiusPill),
                  border: isSelected
                      ? null
                      : Border.all(
                          color: primaryColor.withValues(alpha: 0.12),
                          width: 1.0,
                        ),
                  boxShadow: isSelected
                      ? [
                          BoxShadow(
                            color: primaryColor.withValues(alpha: 0.28),
                            blurRadius: 8,
                            offset: const Offset(0, 3),
                          ),
                        ]
                      : null,
                ),
                child: Center(
                  child: Text(
                    topic,
                    style: TextStyle(
                      color: isSelected ? Colors.white : primaryColor,
                      fontSize: 12.5,
                      fontWeight: isSelected ? FontWeight.w700 : FontWeight.w600,
                      letterSpacing: -0.1,
                    ),
                  ),
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}

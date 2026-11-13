import 'package:flutter/material.dart';

import 'home_design_constants.dart';

class HomePrimaryAction extends StatelessWidget {
  const HomePrimaryAction({
    super.key,
    required this.eyebrow,
    required this.title,
    required this.description,
    required this.actionLabel,
    required this.icon,
    required this.onPressed,
  });

  final String eyebrow;
  final String title;
  final String description;
  final String actionLabel;
  final IconData icon;
  final VoidCallback onPressed;

  @override
  Widget build(BuildContext context) {
    final primary = HomeDesignConstants.brandPrimary(context);
    final text = HomeDesignConstants.text(context);
    final muted = HomeDesignConstants.mutedText(context);

    return Padding(
      padding: const EdgeInsets.symmetric(
        horizontal: HomeDesignConstants.horizontalPadding,
      ),
      child: Container(
        width: double.infinity,
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          color: HomeDesignConstants.surface(context),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: primary.withValues(alpha: .16)),
        ),
        child: LayoutBuilder(
          builder: (context, constraints) {
            final compact = constraints.maxWidth < 520;
            final copy = Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  eyebrow.toUpperCase(),
                  style: TextStyle(
                    color: primary,
                    fontSize: 11,
                    fontWeight: FontWeight.w800,
                    letterSpacing: .8,
                  ),
                ),
                const SizedBox(height: 7),
                Text(
                  title,
                  style: TextStyle(
                    color: text,
                    fontSize: compact ? 20 : 24,
                    height: 1.2,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                const SizedBox(height: 7),
                Text(
                  description,
                  style: TextStyle(color: muted, fontSize: 13.5, height: 1.45),
                ),
              ],
            );

            final action = FilledButton.icon(
              onPressed: onPressed,
              icon: Icon(icon, size: 18),
              label: Text(actionLabel),
              style: FilledButton.styleFrom(
                backgroundColor: primary,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(
                  horizontal: 18,
                  vertical: 13,
                ),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
            );

            if (compact) {
              return Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [copy, const SizedBox(height: 18), action],
              );
            }
            return Row(
              crossAxisAlignment: CrossAxisAlignment.center,
              children: [
                Expanded(child: copy),
                const SizedBox(width: 28),
                action,
              ],
            );
          },
        ),
      ),
    );
  }
}

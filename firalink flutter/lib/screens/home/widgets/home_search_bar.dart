import 'package:flutter/material.dart';
import 'package:hugeicons/hugeicons.dart';
import '../../../l10n/app_localizations.dart';
import 'home_design_constants.dart';

class HomeSearchBar extends StatelessWidget {
  const HomeSearchBar({
    super.key,
    required this.controller,
    required this.onSearch,
  });

  final TextEditingController controller;
  final ValueChanged<String> onSearch;

  @override
  Widget build(BuildContext context) {
    final Color cardBg = HomeDesignConstants.surface(context);
    final Color textColor = HomeDesignConstants.text(context);
    final Color mutedColor = HomeDesignConstants.mutedText(context);
    final Color primaryColor = HomeDesignConstants.brandPrimary(context);

    return Padding(
      padding: const EdgeInsets.symmetric(
        horizontal: HomeDesignConstants.horizontalPadding,
      ),
      child: Container(
        height: 52,
        decoration: BoxDecoration(
          color: cardBg,
          borderRadius: BorderRadius.circular(HomeDesignConstants.radiusSearch),
          boxShadow: HomeDesignConstants.softShadow(context, blur: 16, offset: const Offset(0, 4)),
        ),
        child: Row(
          children: <Widget>[
            const SizedBox(width: 18),
            HugeIcon(
              icon: HugeIcons.strokeRoundedSearch01,
              size: 20,
              color: mutedColor,
            ),
            const SizedBox(width: 12),
            Expanded(
              child: TextField(
                controller: controller,
                onSubmitted: onSearch,
                textInputAction: TextInputAction.search,
                style: TextStyle(
                  color: textColor,
                  fontSize: 14,
                  fontWeight: FontWeight.w500,
                ),
                decoration: InputDecoration(
                  hintText: context.l10n.search,
                  hintStyle: TextStyle(
                    color: mutedColor,
                    fontSize: 14,
                    fontWeight: FontWeight.w400,
                  ),
                  border: InputBorder.none,
                  isDense: true,
                  contentPadding: EdgeInsets.zero,
                ),
              ),
            ),
            const SizedBox(width: 8),
            // Circular purple search button
            Semantics(
              label: 'Search courses and tours',
              button: true,
              child: InkWell(
                onTap: () => onSearch(controller.text),
                borderRadius: BorderRadius.circular(20),
                child: Container(
                  width: 40,
                  height: 40,
                  margin: const EdgeInsets.only(right: 6),
                  decoration: BoxDecoration(
                    color: primaryColor,
                    shape: BoxShape.circle,
                  ),
                  child: const Center(
                    child: HugeIcon(
                      icon: HugeIcons.strokeRoundedArrowRight01,
                      size: 20,
                      color: Colors.white,
                    ),
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

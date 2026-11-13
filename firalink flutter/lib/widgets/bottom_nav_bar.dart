import 'package:flutter/material.dart';
import 'fira_bottom_dock.dart';

enum BottomNavTab { home, courses, b2b, events, tours, profile, instructors }

class AppBottomNavBar extends StatelessWidget {
  const AppBottomNavBar({super.key, required this.currentTab});

  final BottomNavTab currentTab;

  @override
  Widget build(BuildContext context) {
    return FiraBottomDock(currentTab: currentTab);
  }
}

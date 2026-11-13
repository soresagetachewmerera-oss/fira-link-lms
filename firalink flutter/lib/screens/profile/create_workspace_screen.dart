import 'package:flutter/material.dart';

import '../onboarding/business_workspace_setup_screen.dart';

/// "Add a business" from the workspace switcher: the same Business setup form, outside onboarding.
class CreateWorkspaceScreen extends StatelessWidget {
  const CreateWorkspaceScreen({super.key});

  @override
  Widget build(BuildContext context) => const BusinessWorkspaceSetupScreen();
}

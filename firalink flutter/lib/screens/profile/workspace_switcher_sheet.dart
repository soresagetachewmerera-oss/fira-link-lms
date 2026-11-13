import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/workspace_provider.dart';
import 'create_workspace_screen.dart';
import 'team_seats_screen.dart';

class WorkspaceSwitcherSheet extends StatelessWidget {
  const WorkspaceSwitcherSheet({super.key});

  static Future<void> show(BuildContext context) {
    return showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => const WorkspaceSwitcherSheet(),
    );
  }

  @override
  Widget build(BuildContext context) {
    const primaryColor = Color(0xFF6D1A95);

    return Consumer<WorkspaceProvider>(
      builder: (context, ws, _) {
        final currentOrg = ws.currentOrganization;
        final orgs = ws.organizations;

        return Container(
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
          ),
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 20),
          child: SafeArea(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Center(
                  child: Container(
                    width: 40,
                    height: 4,
                    decoration: BoxDecoration(
                      color: Colors.grey.shade300,
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                ),
                const SizedBox(height: 18),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text(
                      'Switch Workspace',
                      style: TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                        color: Color(0xFF1E293B),
                      ),
                    ),
                    TextButton.icon(
                      icon: const Icon(Icons.add, size: 18, color: primaryColor),
                      label: const Text(
                        'Add Business',
                        style: TextStyle(color: primaryColor, fontWeight: FontWeight.bold),
                      ),
                      onPressed: () {
                        Navigator.pop(context);
                        Navigator.push(
                          context,
                          MaterialPageRoute(builder: (_) => const CreateWorkspaceScreen()),
                        );
                      },
                    ),
                  ],
                ),
                const SizedBox(height: 12),

                // Workspace List
                if (orgs.isEmpty)
                  Padding(
                    padding: const EdgeInsets.symmetric(vertical: 24),
                    child: Center(
                      child: Column(
                        children: [
                          Icon(Icons.business_outlined, size: 40, color: Colors.grey.shade400),
                          const SizedBox(height: 8),
                          Text(
                            'No business workspace found',
                            style: TextStyle(color: Colors.grey.shade600),
                          ),
                        ],
                      ),
                    ),
                  )
                else
                  ListView.separated(
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    itemCount: orgs.length,
                    separatorBuilder: (_, __) => const SizedBox(height: 10),
                    itemBuilder: (context, index) {
                      final org = orgs[index];
                      final isCurrent = currentOrg?.id == org.id;

                      return InkWell(
                        onTap: () async {
                          if (!isCurrent) {
                            Navigator.pop(context);
                            await ws.switchWorkspace(org.id);
                          }
                        },
                        borderRadius: BorderRadius.circular(16),
                        child: Container(
                          padding: const EdgeInsets.all(14),
                          decoration: BoxDecoration(
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(
                              color: isCurrent ? primaryColor : Colors.grey.shade200,
                              width: isCurrent ? 2 : 1,
                            ),
                            color: isCurrent
                                ? primaryColor.withValues(alpha: 0.04)
                                : Colors.white,
                          ),
                          child: Row(
                            children: [
                              Container(
                                width: 44,
                                height: 44,
                                decoration: BoxDecoration(
                                  color: isCurrent
                                      ? primaryColor.withValues(alpha: 0.1)
                                      : Colors.grey.shade100,
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: Center(
                                  child: Text(
                                    org.name.isNotEmpty ? org.name[0].toUpperCase() : 'B',
                                    style: TextStyle(
                                      fontWeight: FontWeight.bold,
                                      fontSize: 18,
                                      color: isCurrent ? primaryColor : Colors.grey.shade700,
                                    ),
                                  ),
                                ),
                              ),
                              const SizedBox(width: 14),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Row(
                                      children: [
                                        Flexible(
                                          child: Text(
                                            org.name,
                                            style: const TextStyle(
                                              fontWeight: FontWeight.bold,
                                              fontSize: 15,
                                              color: Color(0xFF1E293B),
                                            ),
                                            overflow: TextOverflow.ellipsis,
                                          ),
                                        ),
                                        if (org.isVerified) ...[
                                          const SizedBox(width: 6),
                                          const Icon(Icons.verified, color: Colors.blue, size: 16),
                                        ],
                                      ],
                                    ),
                                    const SizedBox(height: 2),
                                    Text(
                                      '${org.businessType.toUpperCase()} • ${org.role?.toUpperCase() ?? 'MEMBER'}',
                                      style: TextStyle(
                                        fontSize: 11,
                                        color: Colors.grey.shade500,
                                        fontWeight: FontWeight.w500,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                              if (isCurrent)
                                const Icon(Icons.check_circle, color: primaryColor, size: 22)
                              else
                                IconButton(
                                  icon: const Icon(Icons.group_outlined, size: 20, color: Colors.grey),
                                  onPressed: () {
                                    Navigator.pop(context);
                                    Navigator.push(
                                      context,
                                      MaterialPageRoute(
                                        builder: (_) => TeamSeatsScreen(organization: org),
                                      ),
                                    );
                                  },
                                ),
                            ],
                          ),
                        ),
                      );
                    },
                  ),
                const SizedBox(height: 16),
              ],
            ),
          ),
        );
      },
    );
  }
}


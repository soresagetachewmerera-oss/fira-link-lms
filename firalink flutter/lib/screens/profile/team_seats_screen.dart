import 'package:flutter/material.dart';
import '../../models/organization_model.dart';
import '../../services/organization_service.dart';

class TeamSeatsScreen extends StatefulWidget {
  final OrganizationModel organization;

  const TeamSeatsScreen({super.key, required this.organization});

  @override
  State<TeamSeatsScreen> createState() => _TeamSeatsScreenState();
}

class _TeamSeatsScreenState extends State<TeamSeatsScreen> {
  final OrganizationService _service = OrganizationService();
  List<OrganizationMemberModel> _members = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadMembers();
  }

  Future<void> _loadMembers() async {
    setState(() => _isLoading = true);
    final list = await _service.getMembers(widget.organization.id);
    if (mounted) {
      setState(() {
        _members = list;
        _isLoading = false;
      });
    }
  }

  void _showInviteDialog() {
    final emailCtrl = TextEditingController();
    String role = 'staff';

    showDialog(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (context, setDialogState) => AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          title: const Text('Invite Team Member', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text('Enter colleague email address and assign seat role:'),
              const SizedBox(height: 12),
              TextField(
                controller: emailCtrl,
                keyboardType: TextInputType.emailAddress,
                decoration: InputDecoration(
                  hintText: 'colleague@company.com',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                ),
              ),
              const SizedBox(height: 14),
              DropdownButtonFormField<String>(
                value: role,
                decoration: InputDecoration(
                  labelText: 'Role',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                ),
                items: const [
                  DropdownMenuItem(value: 'manager', child: Text('Manager (Trading & Lots)')),
                  DropdownMenuItem(value: 'staff', child: Text('Staff (View & Operations)')),
                ],
                onChanged: (val) {
                  if (val != null) setDialogState(() => role = val);
                },
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx),
              child: const Text('Cancel', style: TextStyle(color: Colors.grey)),
            ),
            ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF6D1A95),
                foregroundColor: Colors.white,
              ),
              onPressed: () async {
                Navigator.pop(ctx);
                final res = await _service.addMember(widget.organization.id, emailCtrl.text.trim(), role);
                if (mounted) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(
                      content: Text(res['message'] ?? 'Invite sent'),
                      backgroundColor: res['success'] == true ? Colors.green : Colors.redAccent,
                    ),
                  );
                  _loadMembers();
                }
              },
              child: const Text('Send Invite'),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    const primaryColor = Color(0xFF6D1A95);

    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: Text(
          '${widget.organization.name} Seats',
          style: const TextStyle(color: Color(0xFF1E293B), fontWeight: FontWeight.bold, fontSize: 16),
        ),
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Color(0xFF1E293B)),
          onPressed: () => Navigator.pop(context),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.person_add_outlined, color: primaryColor),
            onPressed: _showInviteDialog,
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: primaryColor))
          : _members.isEmpty
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.people_outline, size: 48, color: Colors.grey.shade400),
                      const SizedBox(height: 12),
                      const Text('No team members yet', style: TextStyle(color: Colors.grey)),
                      const SizedBox(height: 16),
                      ElevatedButton.icon(
                        icon: const Icon(Icons.person_add),
                        label: const Text('Invite First Member'),
                        style: ElevatedButton.styleFrom(backgroundColor: primaryColor),
                        onPressed: _showInviteDialog,
                      ),
                    ],
                  ),
                )
              : ListView.separated(
                  padding: const EdgeInsets.all(20),
                  itemCount: _members.length,
                  separatorBuilder: (_, __) => const SizedBox(height: 12),
                  itemBuilder: (context, index) {
                    final member = _members[index];
                    final isOwner = member.role == 'owner';

                    return Container(
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        border: Border.all(color: Colors.grey.shade200),
                        borderRadius: BorderRadius.circular(14),
                      ),
                      child: Row(
                        children: [
                          CircleAvatar(
                            backgroundColor: primaryColor.withValues(alpha: 0.1),
                            child: Text(
                              member.name.isNotEmpty ? member.name[0].toUpperCase() : 'U',
                              style: const TextStyle(color: primaryColor, fontWeight: FontWeight.bold),
                            ),
                          ),
                          const SizedBox(width: 14),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  member.name,
                                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                                ),
                                const SizedBox(height: 2),
                                Text(
                                  member.email,
                                  style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                                ),
                              ],
                            ),
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                            decoration: BoxDecoration(
                              color: isOwner
                                  ? primaryColor.withValues(alpha: 0.1)
                                  : Colors.grey.shade100,
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: Text(
                              member.role.toUpperCase(),
                              style: TextStyle(
                                fontSize: 11,
                                fontWeight: FontWeight.bold,
                                color: isOwner ? primaryColor : Colors.grey.shade700,
                              ),
                            ),
                          ),
                          if (!isOwner) ...[
                            const SizedBox(width: 8),
                            IconButton(
                              icon: const Icon(Icons.delete_outline, color: Colors.redAccent, size: 20),
                              onPressed: () async {
                                final confirmed = await showDialog<bool>(
                                  context: context,
                                  builder: (ctx) => AlertDialog(
                                    title: const Text('Remove Member?'),
                                    content: Text('Remove ${member.name} from this workspace?'),
                                    actions: [
                                      TextButton(
                                        onPressed: () => Navigator.pop(ctx, false),
                                        child: const Text('Cancel'),
                                      ),
                                      TextButton(
                                        onPressed: () => Navigator.pop(ctx, true),
                                        child: const Text('Remove', style: TextStyle(color: Colors.red)),
                                      ),
                                    ],
                                  ),
                                );
                                if (confirmed == true) {
                                  await _service.removeMember(widget.organization.id, member.userId);
                                  _loadMembers();
                                }
                              },
                            ),
                          ],
                        ],
                      ),
                    );
                  },
                ),
    );
  }
}


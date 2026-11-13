import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/workspace_provider.dart';
import 'business_workspace_setup_screen.dart';
import 'intent_welcome_screen.dart';

class IntentSelectionScreen extends StatefulWidget {
  const IntentSelectionScreen({super.key});

  @override
  State<IntentSelectionScreen> createState() => _IntentSelectionScreenState();
}

class _IntentSelectionScreenState extends State<IntentSelectionScreen> {
  String _selectedIntent = 'export';
  bool _isSaving = false;

  final List<Map<String, dynamic>> _intents = [
    {
      'id': 'export',
      'title': 'Export or Sell Coffee',
      'subtitle': 'Ethiopian licensed exporter, washing station, commercial farm, or cooperative.',
      'icon': Icons.local_shipping_outlined,
      'color': Color(0xFF6D1A95),
      'requires_business': true,
    },
    {
      'id': 'buy',
      'title': 'Source & Import Coffee',
      'subtitle': 'International green coffee buyer, specialty roaster, or import distributor.',
      'icon': Icons.shopping_bag_outlined,
      'color': Color(0xFF0D9488),
      'requires_business': true,
    },
    {
      'id': 'learn',
      'title': 'Learn & Build Skills',
      'subtitle': 'Access Fira Link Academy, cupping courses, export trade regulations & certifications.',
      'icon': Icons.school_outlined,
      'color': Color(0xFFD97706),
      'requires_business': false,
    },
    {
      'id': 'events',
      'title': 'Origin Tours & Events',
      'subtitle': 'Explore Ethiopian coffee regions, harvest tours, B2B cupping & trade summits.',
      'icon': Icons.explore_outlined,
      'color': Color(0xFF2563EB),
      'requires_business': false,
    },
  ];

  Future<void> _handleContinue() async {
    setState(() => _isSaving = true);
    final wsProvider = Provider.of<WorkspaceProvider>(context, listen: false);
    await wsProvider.saveIntent(_selectedIntent);

    if (!mounted) return;
    setState(() => _isSaving = false);

    final intentMeta = _intents.firstWhere((i) => i['id'] == _selectedIntent);
    final requiresBusiness = intentMeta['requires_business'] == true;

    if (requiresBusiness) {
      Navigator.push(
        context,
        MaterialPageRoute(
          builder: (_) => BusinessWorkspaceSetupScreen(intent: _selectedIntent),
        ),
      );
    } else {
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(
          builder: (_) => IntentWelcomeScreen(intent: _selectedIntent),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    const primaryColor = Color(0xFF6D1A95);

    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Color(0xFF1E293B)),
          onPressed: () => Navigator.pop(context),
        ),
        actions: [
          TextButton(
            onPressed: () {
              Navigator.pushReplacement(
                context,
                MaterialPageRoute(
                  builder: (_) => const IntentWelcomeScreen(intent: 'learn'),
                ),
              );
            },
            child: const Text('Skip for now', style: TextStyle(color: Colors.grey)),
          ),
        ],
      ),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 24.0, vertical: 12.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const Text(
                'What is your primary goal?',
                style: TextStyle(
                  fontSize: 24,
                  fontWeight: FontWeight.bold,
                  color: Color(0xFF1E293B),
                ),
              ),
              const SizedBox(height: 8),
              const Text(
                'We tailor your initial space accordingly. You can always add other roles or workspaces later.',
                style: TextStyle(fontSize: 13, color: Colors.black54, height: 1.4),
              ),
              const SizedBox(height: 24),

              Expanded(
                child: ListView.separated(
                  itemCount: _intents.length,
                  separatorBuilder: (_, __) => const SizedBox(height: 14),
                  itemBuilder: (context, index) {
                    final item = _intents[index];
                    final isSelected = _selectedIntent == item['id'];
                    final itemColor = item['color'] as Color;

                    return InkWell(
                      onTap: () => setState(() => _selectedIntent = item['id']),
                      borderRadius: BorderRadius.circular(18),
                      child: AnimatedContainer(
                        duration: const Duration(milliseconds: 200),
                        padding: const EdgeInsets.all(18),
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(18),
                          border: Border.all(
                            color: isSelected ? primaryColor : Colors.grey.shade200,
                            width: isSelected ? 2 : 1,
                          ),
                          color: isSelected
                              ? primaryColor.withValues(alpha: 0.04)
                              : Colors.white,
                          boxShadow: isSelected
                              ? [
                                  BoxShadow(
                                    color: primaryColor.withValues(alpha: 0.08),
                                    blurRadius: 10,
                                    offset: const Offset(0, 4),
                                  )
                                ]
                              : [],
                        ),
                        child: Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Container(
                              padding: const EdgeInsets.all(12),
                              decoration: BoxDecoration(
                                color: itemColor.withValues(alpha: 0.12),
                                borderRadius: BorderRadius.circular(14),
                              ),
                              child: Icon(item['icon'] as IconData, color: itemColor, size: 26),
                            ),
                            const SizedBox(width: 16),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    item['title'],
                                    style: TextStyle(
                                      fontSize: 16,
                                      fontWeight: FontWeight.bold,
                                      color: isSelected ? primaryColor : const Color(0xFF1E293B),
                                    ),
                                  ),
                                  const SizedBox(height: 4),
                                  Text(
                                    item['subtitle'],
                                    style: TextStyle(fontSize: 12, color: Colors.grey.shade600, height: 1.3),
                                  ),
                                ],
                              ),
                            ),
                            const SizedBox(width: 10),
                            Radio<String>(
                              value: item['id'],
                              groupValue: _selectedIntent,
                              activeColor: primaryColor,
                              onChanged: (val) {
                                if (val != null) setState(() => _selectedIntent = val);
                              },
                            ),
                          ],
                        ),
                      ),
                    );
                  },
                ),
              ),

              ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: primaryColor,
                  foregroundColor: Colors.white,
                  minimumSize: const Size.fromHeight(52),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                  elevation: 0,
                ),
                onPressed: _isSaving ? null : _handleContinue,
                child: _isSaving
                    ? const SizedBox(
                        width: 22,
                        height: 22,
                        child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                      )
                    : const Text(
                        'Continue',
                        style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                      ),
              ),
              const SizedBox(height: 12),
            ],
          ),
        ),
      ),
    );
  }
}


import 'package:flutter/material.dart';

class EvidenceLevelSheet extends StatelessWidget {
  final String currentLevel;
  final ValueChanged<String>? onSelected;

  const EvidenceLevelSheet({
    super.key,
    this.currentLevel = 'claimed',
    this.onSelected,
  });

  static Future<String?> show(BuildContext context, {String currentLevel = 'claimed'}) {
    return showModalBottomSheet<String>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => EvidenceLevelSheet(currentLevel: currentLevel),
    );
  }

  @override
  Widget build(BuildContext context) {
    const primaryColor = Color(0xFF6D1A95);

    final levels = [
      {
        'id': 'claimed',
        'title': 'Level 1: Claimed Lot',
        'badge': 'CLAIMED',
        'color': Colors.grey,
        'desc': 'Exporter declaration of variety, processing method, and estimated cupping score without third-party lab documentation.',
        'req': 'No file attachment required',
      },
      {
        'id': 'documented',
        'title': 'Level 2: Documented Lot',
        'badge': 'DOCUMENTED',
        'color': Colors.blue,
        'desc': 'Traceability backed by washing station delivery receipts, ECX origin consignment, or cooperative records.',
        'req': 'Origin dispatch/coop consignment attached',
      },
      {
        'id': 'lab_backed',
        'title': 'Level 3: Lab-Backed Lot',
        'badge': 'LAB-BACKED',
        'color': Colors.amber.shade800,
        'desc': 'Certified SCA cupping score sheet, moisture percentage, and water activity analysis from an accredited coffee lab.',
        'req': 'Accredited lab cupping certificate required',
      },
      {
        'id': 'inspected',
        'title': 'Level 4: Officially Inspected',
        'badge': 'INSPECTED',
        'color': Colors.green,
        'desc': 'Physical pre-shipment warehouse inspection & container lot verification conducted by ECTA or licensed surveyor (SGS/Bureau Veritas).',
        'req': 'Official ECTA/SGS inspection survey report required',
      },
    ];

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
            const Text(
              'Coffee Lot Evidence Levels',
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
                color: Color(0xFF1E293B),
              ),
            ),
            const SizedBox(height: 6),
            const Text(
              'Higher evidence tiers gain 3.4x more buyer inquiry requests and faster deal closing.',
              style: TextStyle(fontSize: 12, color: Colors.black54),
            ),
            const SizedBox(height: 16),

            ListView.separated(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: levels.length,
              separatorBuilder: (_, __) => const SizedBox(height: 12),
              itemBuilder: (context, index) {
                final item = levels[index];
                final isSelected = currentLevel == item['id'];
                final color = item['color'] as Color;

                return InkWell(
                  onTap: () {
                    if (onSelected != null) {
                      onSelected!(item['id'] as String);
                    }
                    Navigator.pop(context, item['id'] as String);
                  },
                  borderRadius: BorderRadius.circular(16),
                  child: Container(
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(
                        color: isSelected ? primaryColor : Colors.grey.shade200,
                        width: isSelected ? 2 : 1,
                      ),
                      color: isSelected
                          ? primaryColor.withValues(alpha: 0.04)
                          : Colors.white,
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              item['title'] as String,
                              style: TextStyle(
                                fontWeight: FontWeight.bold,
                                fontSize: 14,
                                color: isSelected ? primaryColor : const Color(0xFF1E293B),
                              ),
                            ),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                              decoration: BoxDecoration(
                                color: color.withValues(alpha: 0.15),
                                borderRadius: BorderRadius.circular(6),
                              ),
                              child: Text(
                                item['badge'] as String,
                                style: TextStyle(
                                  color: color,
                                  fontWeight: FontWeight.bold,
                                  fontSize: 10,
                                ),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 6),
                        Text(
                          item['desc'] as String,
                          style: TextStyle(fontSize: 12, color: Colors.grey.shade600, height: 1.3),
                        ),
                        const SizedBox(height: 6),
                        Row(
                          children: [
                            Icon(Icons.attachment, size: 14, color: Colors.grey.shade500),
                            const SizedBox(width: 4),
                            Text(
                              item['req'] as String,
                              style: TextStyle(fontSize: 11, color: Colors.grey.shade500, fontStyle: FontStyle.italic),
                            ),
                          ],
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
  }
}


import 'package:flutter/material.dart';

class ScamWarningSheet extends StatelessWidget {
  const ScamWarningSheet({super.key});

  static Future<void> show(BuildContext context) {
    return showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (_) => const ScamWarningSheet(),
    );
  }

  @override
  Widget build(BuildContext context) {
    const primaryColor = Color(0xFF6D1A95);

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
              children: [
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: Colors.amber.shade100,
                    shape: BoxShape.circle,
                  ),
                  child: Icon(Icons.security, color: Colors.amber.shade900, size: 24),
                ),
                const SizedBox(width: 12),
                const Text(
                  'B2B Trade Safety Rules',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF1E293B)),
                ),
              ],
            ),
            const SizedBox(height: 14),

            _buildRule(
              '1. Never Pay Outside Deal Rooms',
              'Scammers request direct western union or personal wire transfers. Always use Deal Room documentary milestone payments or verified LC.',
              Icons.no_photography_outlined,
            ),
            const SizedBox(height: 10),
            _buildRule(
              '2. Check Retained Twin Seal',
              'Every verified sample includes a cryptographic seal number. Retain your sample container intact for pre-shipment comparison.',
              Icons.fingerprint,
            ),
            const SizedBox(height: 10),
            _buildRule(
              '3. Verify Exporter Credentials',
              'Look for the blue Verified Exporter badge. Verify ECTA competence validity before signing export contracts.',
              Icons.verified,
            ),
            const SizedBox(height: 20),

            ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: primaryColor,
                foregroundColor: Colors.white,
                minimumSize: const Size.fromHeight(48),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              ),
              onPressed: () => Navigator.pop(context),
              child: const Text('I Understand & Agree', style: TextStyle(fontWeight: FontWeight.bold)),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildRule(String title, String desc, IconData icon) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.grey.shade50,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.grey.shade200),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 20, color: const Color(0xFF6D1A95)),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                const SizedBox(height: 2),
                Text(desc, style: TextStyle(fontSize: 11, color: Colors.grey.shade600, height: 1.3)),
              ],
            ),
          ),
        ],
      ),
    );
  }
}


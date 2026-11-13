import 'package:flutter/material.dart';

class SuspendedLicenseScreen extends StatelessWidget {
  final String companyName;
  final String reason;
  final String? authority;

  const SuspendedLicenseScreen({
    super.key,
    required this.companyName,
    required this.reason,
    this.authority,
  });

  @override
  Widget build(BuildContext context) {
    const primaryColor = Color(0xFF6D1A95);

    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: const Text(
          'Compliance Notice',
          style: TextStyle(color: Color(0xFF1E293B), fontWeight: FontWeight.bold, fontSize: 16),
        ),
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Color(0xFF1E293B)),
          onPressed: () => Navigator.pop(context),
        ),
      ),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(24.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const Spacer(),
              Center(
                child: Container(
                  width: 80,
                  height: 80,
                  decoration: BoxDecoration(
                    color: Colors.red.shade50,
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(Icons.block, color: Colors.redAccent, size: 44),
                ),
              ),
              const SizedBox(height: 24),
              const Text(
                'Trading Privileges Suspended',
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold, color: Color(0xFF1E293B)),
              ),
              const SizedBox(height: 10),
              Text(
                'The regulatory license or verification for $companyName is currently suspended or expired.',
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 13, color: Colors.grey.shade600, height: 1.4),
              ),
              const SizedBox(height: 28),

              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.grey.shade50,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: Colors.grey.shade200),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('REASON FOR RESTRICTION', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 11, color: Colors.grey)),
                    const SizedBox(height: 6),
                    Text(reason, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
                    if (authority != null) ...[
                      const SizedBox(height: 12),
                      const Text('REGULATORY AUTHORITY', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 11, color: Colors.grey)),
                      const SizedBox(height: 4),
                      Text(authority!, style: const TextStyle(fontSize: 12, color: Colors.black87)),
                    ],
                  ],
                ),
              ),

              const Spacer(),

              OutlinedButton(
                style: OutlinedButton.styleFrom(
                  minimumSize: const Size.fromHeight(50),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                ),
                onPressed: () => Navigator.pop(context),
                child: const Text('Return to Marketplace'),
              ),
              const SizedBox(height: 12),
              ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: primaryColor,
                  foregroundColor: Colors.white,
                  minimumSize: const Size.fromHeight(50),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                ),
                onPressed: () {
                  // Navigate to compliance support
                  Navigator.pop(context);
                },
                child: const Text('Submit Renewal / Appeal Documents', style: TextStyle(fontWeight: FontWeight.bold)),
              ),
            ],
          ),
        ),
      ),
    );
  }
}


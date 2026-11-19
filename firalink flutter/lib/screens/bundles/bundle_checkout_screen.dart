import 'dart:convert';
import 'package:flutter/material.dart';
import '../../models/bundle.dart';
import '../../services/course_service.dart';
import '../../providers/settings_provider.dart';
import '../../theme/app_theme.dart';
import '../common/webview_screen.dart';
import '../../widgets/fira_error_state.dart';
import 'package:provider/provider.dart';

class BundleCheckoutScreen extends StatefulWidget {
  final Bundle bundle;
  const BundleCheckoutScreen({super.key, required this.bundle});

  @override
  State<BundleCheckoutScreen> createState() => _BundleCheckoutScreenState();
}

class _BundleCheckoutScreenState extends State<BundleCheckoutScreen> {
  bool _isProcessing = false;

  Future<void> _processPayment({bool confirmDuplicate = false}) async {
    setState(() { _isProcessing = true; });
    try {
      final courseService = CourseService();
      final isFree = (widget.bundle.price ?? 0) <= 0;
      final method = isFree ? 'free' : 'chapa';
      final response = await courseService.enrollBundle(
        widget.bundle.id,
        method,
        confirmDuplicate: confirmDuplicate,
      );

      if (!mounted) return;
      final messenger = ScaffoldMessenger.of(context);
      final nav = Navigator.of(context);

      final data = jsonDecode(response.body);
      if (response.statusCode == 200) {
        if (data['is_duplicate_warning'] == true) {
          setState(() { _isProcessing = false; });
          bool? proceed = await showDialog<bool>(
            context: context,
            builder: (ctx) => AlertDialog(
              title: const Text('Duplicate Courses'),
              content: Text(data['message'] ?? 'You already own some courses in this bundle. Proceeding will enroll you in the remaining courses.'),
              actions: [
                TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
                ElevatedButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Proceed Anyway')),
              ],
            ),
          );
          if (proceed == true) {
            await _processPayment(confirmDuplicate: true);
          }
          return;
        }

        // Paid Bundle flow with Chapa
        if (!isFree && data['checkout_url'] != null) {
          final String checkoutUrl = data['checkout_url'];
          final String txRef = data['tx_ref'] ?? '';
          final int enrollmentId = data['enrollment_id'] ?? 0;

          setState(() { _isProcessing = false; });

          // Open Chapa checkout
          await Navigator.push(
            context,
            MaterialPageRoute(
              builder: (context) => WebViewScreen(
                url: checkoutUrl,
                title: 'Chapa Bundle Payment',
                shouldExit: (url) => url.contains('/payment/chapa/callback'),
              ),
            ),
          );

          if (!mounted) return;

          // Verify with Backend
          setState(() { _isProcessing = true; });
          showDialog(
            context: context,
            barrierDismissible: false,
            builder: (context) => const Center(child: CircularProgressIndicator()),
          );

          final verifyResponse = await courseService.verifyChapaPayment(
            txRef: txRef,
            enrollmentId: enrollmentId,
          );

          if (mounted) {
            Navigator.of(context, rootNavigator: true).pop(); // Close loading dialog
          }

          final verifyData = jsonDecode(verifyResponse.body);
          if (verifyResponse.statusCode == 200 && verifyData['success'] == true) {
            messenger.showSnackBar(
              SnackBar(content: Text(verifyData['message'] ?? 'Payment successful! Enrolled in bundle.')),
            );
            nav.pop(true);
          } else {
            messenger.showSnackBar(
              SnackBar(content: Text(verifyData['message'] ?? 'Payment verification failed. If charged, contact support.')),
            );
          }
        } else {
          messenger.showSnackBar(SnackBar(content: Text(data['message'] ?? 'Enrolled in bundle successfully!')));
          nav.pop(true);
        }
      } else {
        messenger.showSnackBar(SnackBar(content: Text(data['message'] ?? 'Failed to enroll in bundle')));
      }
    } catch (e) {
      if (!mounted) return;
      showFiraActionError(context, e);
    } finally {
      if (mounted) setState(() { _isProcessing = false; });
    }
  }

  @override
  Widget build(BuildContext context) {
    final settingsProvider = Provider.of<SettingsProvider>(context);
    final primaryColor = AppTheme.getPrimaryColor(context);
    final isFree = (widget.bundle.price ?? 0) <= 0;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Bundle Checkout', style: TextStyle(fontWeight: FontWeight.bold)),
      ),
      body: Center(
        child: Container(
          constraints: const BoxConstraints(maxWidth: 480),
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Container(
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  color: AppTheme.getCardColor(context),
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: Colors.grey.shade200),
                ),
                child: Column(
                  children: [
                    Icon(Icons.collections_bookmark_outlined, size: 48, color: primaryColor),
                    const SizedBox(height: 12),
                    Text(
                      widget.bundle.title,
                      style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 16),
                    Text(
                      isFree ? 'Free Enrollment' : settingsProvider.formatPrice(widget.bundle.price),
                      style: TextStyle(fontSize: 26, fontWeight: FontWeight.w800, color: primaryColor),
                    ),
                    const SizedBox(height: 16),
                    if (!isFree)
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                        decoration: BoxDecoration(
                          color: primaryColor.withValues(alpha: 0.08),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.lock_outline, size: 16, color: primaryColor),
                            const SizedBox(width: 6),
                            Text(
                              'Instant & Secure Payment via Chapa (ETB)',
                              style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: primaryColor),
                            ),
                          ],
                        ),
                      ),
                  ],
                ),
              ),
              const SizedBox(height: 32),
              _isProcessing
                  ? const Center(child: CircularProgressIndicator())
                  : ElevatedButton(
                      onPressed: () => _processPayment(),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: primaryColor,
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                      child: Text(
                        isFree ? 'Enroll for Free' : 'Pay with Chapa',
                        style: const TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold),
                      ),
                    ),
            ],
          ),
        ),
      ),
    );
  }
}

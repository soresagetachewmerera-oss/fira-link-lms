import 'package:flutter/material.dart';
import 'package:hugeicons/hugeicons.dart';
import 'package:provider/provider.dart';
import 'package:flutter_stripe/flutter_stripe.dart';
import 'package:razorpay_flutter/razorpay_flutter.dart';
import '../../models/subscription_plan_model.dart';
import '../../providers/subscription_provider.dart';
import '../../providers/settings_provider.dart';
import '../../theme/app_theme.dart';
import '../../providers/auth_provider.dart';
import '../../router/app_router.dart';
import '../common/webview_screen.dart';
import '../profile/my_subscription_screen.dart';
import 'offline_subscription_payment_screen.dart';

class SubscriptionCheckoutScreen extends StatefulWidget {
  final SubscriptionPlanModel plan;
  const SubscriptionCheckoutScreen({super.key, required this.plan});

  @override
  State<SubscriptionCheckoutScreen> createState() => _SubscriptionCheckoutScreenState();
}

class _SubscriptionCheckoutScreenState extends State<SubscriptionCheckoutScreen> {
  String? _selectedMethod;
  bool _isProcessing = false;
  final TextEditingController _transactionIdController = TextEditingController();
  final TextEditingController _notesController = TextEditingController();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final auth = Provider.of<AuthProvider>(context, listen: false);
      if (!auth.isAuthenticated) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Please log in to proceed with subscription.')),
        );
        Navigator.pushReplacementNamed(context, AppRouter.auth);
      }
    });
  }

  @override
  void dispose() {
    _transactionIdController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  void _processCheckout() async {
    if (_selectedMethod == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please select a payment method.')),
      );
      return;
    }

    final String method = _selectedMethod!.toLowerCase();

    // Offline Payment Screen Routing (matches Course Checkout pattern)
    if (method == 'offline') {
      Navigator.push(
        context,
        MaterialPageRoute(
          builder: (_) => OfflineSubscriptionPaymentScreen(plan: widget.plan),
        ),
      );
      return;
    }

    setState(() {
      _isProcessing = true;
    });

    final provider = Provider.of<SubscriptionProvider>(context, listen: false);

    try {
      final res = await provider.checkout(
        planId: widget.plan.id,
        paymentMethod: _selectedMethod!,
        transactionId: _transactionIdController.text.trim().isNotEmpty ? _transactionIdController.text.trim() : null,
        notes: _notesController.text.trim().isNotEmpty ? _notesController.text.trim() : null,
      );

      if (!mounted) return;

      final data = res['data'] ?? {};
      final String checkoutUrl = data['checkout_url'] ?? data['authorization_url'] ?? data['approve_url'] ?? '';
      final int? paymentId = data['payment_id'];

      // Native Razorpay Flow
      if (method == 'razorpay') {
        await _processRazorpay(data, provider);
        return;
      }

      // Native Stripe Flow
      if (method == 'stripe') {
        await _processStripe(data, provider);
        return;
      }

      // Webview gateway flow (Paystack, SSLCommerz, bKash, XPay, PayPal, Mollie, Flutterwave)
      if (checkoutUrl.isNotEmpty) {
        final resultUrl = await Navigator.push<String>(
          context,
          MaterialPageRoute(
            builder: (_) => WebViewScreen(
              url: checkoutUrl,
              title: '${widget.plan.name} Payment',
              shouldExit: (url) {
                return url.contains('/payment/chapa/callback') ||
                    url.contains('/student/subscriptions') ||
                    url.contains('/student/dashboard') ||
                    url.contains('status=success') ||
                    url.contains('status=fail') ||
                    url.contains('status=cancel') ||
                    url.contains('close');
              },
            ),
          ),
        );

        if (!mounted) return;

        if (resultUrl != null && (resultUrl.contains('status=fail') || resultUrl.contains('status=cancel'))) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Payment was cancelled or failed.')),
          );
          return;
        }

        if (paymentId != null) {
          final isSuccess = await provider.verifyPayment(paymentId);
          if (!mounted) return;
          if (isSuccess) {
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(content: Text('Subscription activated successfully!')),
            );
            Navigator.pushReplacement(
              context,
              MaterialPageRoute(builder: (_) => const MySubscriptionScreen()),
            );
          } else {
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(content: Text('Payment was not completed or was cancelled.')),
            );
          }
        }
      } else if (method == 'free' || widget.plan.effectivePrice <= 0) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Free subscription activated successfully!')),
        );
        Navigator.pushReplacement(
          context,
          MaterialPageRoute(builder: (_) => const MySubscriptionScreen()),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(res['message'] ?? 'Could not initialize payment gateway.')),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(e.toString().replaceAll('Exception: ', ''))),
        );
      }
    } finally {
      if (mounted) {
        setState(() {
          _isProcessing = false;
        });
      }
    }
  }

  Future<void> _processRazorpay(Map<String, dynamic> data, SubscriptionProvider provider) async {
    try {
      final String key = data['key']?.toString() ?? '';
      final int amount = data['amount'] ?? 0;
      final String currency = data['currency']?.toString() ?? 'INR';
      final String orderId = data['order_id']?.toString() ?? '';
      final String name = data['name']?.toString() ?? 'Fira Link Academy';
      final String description = data['description']?.toString() ?? '';
      final int? paymentId = data['payment_id'];

      final prefillMap = data['prefill'] as Map<String, dynamic>? ?? {};
      final String prefillName = prefillMap['name']?.toString() ?? '';
      final String prefillEmail = prefillMap['email']?.toString() ?? '';
      final String prefillContact = prefillMap['contact']?.toString() ?? '';

      final razorpay = Razorpay();
      razorpay.on(Razorpay.EVENT_PAYMENT_SUCCESS, (PaymentSuccessResponse response) async {
        if (!mounted) return;
        if (paymentId != null) {
          final isSuccess = await provider.verifyPayment(
            paymentId,
            razorpayPaymentId: response.paymentId,
          );
          if (!mounted) return;
          if (isSuccess) {
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(content: Text('Subscription activated successfully!')),
            );
            Navigator.pushReplacement(
              context,
              MaterialPageRoute(builder: (_) => const MySubscriptionScreen()),
            );
          }
        }
        razorpay.clear();
      });
      razorpay.on(Razorpay.EVENT_PAYMENT_ERROR, (PaymentFailureResponse response) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Payment Failed: ${response.message}')),
          );
        }
        razorpay.clear();
      });
      razorpay.on(Razorpay.EVENT_EXTERNAL_WALLET, (_) {
        razorpay.clear();
      });

      final options = {
        'key': key,
        'amount': amount,
        'currency': currency,
        'name': name,
        'order_id': orderId,
        'description': description,
        'prefill': {
          if (prefillName.isNotEmpty) 'name': prefillName,
          if (prefillEmail.isNotEmpty) 'email': prefillEmail,
          if (prefillContact.isNotEmpty) 'contact': prefillContact,
        },
        'timeout': 300,
      };

      razorpay.open(options);
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error initializing Razorpay: $e')),
        );
      }
    }
  }

  Future<void> _processStripe(Map<String, dynamic> data, SubscriptionProvider provider) async {
    try {
      final String publishableKey = data['publishable_key'] ?? '';
      final String clientSecret = data['client_secret'] ?? '';
      final int? paymentId = data['payment_id'];
      final String? paymentIntentId = data['payment_intent_id'];

      if (publishableKey.isNotEmpty) {
        Stripe.publishableKey = publishableKey;
        await Stripe.instance.applySettings();
      }

      await Stripe.instance.initPaymentSheet(
        paymentSheetParameters: SetupPaymentSheetParameters(
          paymentIntentClientSecret: clientSecret,
          merchantDisplayName: 'Fira Link Academy',
          style: Theme.of(context).brightness == Brightness.dark
              ? ThemeMode.dark
              : ThemeMode.light,
        ),
      );

      await Stripe.instance.presentPaymentSheet();

      if (paymentId != null) {
        final isSuccess = await provider.verifyPayment(
          paymentId,
          paymentIntentId: paymentIntentId ?? clientSecret,
        );
        if (!mounted) return;
        if (isSuccess) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Subscription activated successfully!')),
          );
          Navigator.pushReplacement(
            context,
            MaterialPageRoute(builder: (_) => const MySubscriptionScreen()),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        if (e is StripeException) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Stripe Error: ${e.error.localizedMessage}')),
          );
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Error: $e')),
          );
        }
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final settingsProvider = Provider.of<SettingsProvider>(context);
    final isDark = Theme.of(context).brightness == Brightness.dark;

    final methods = settingsProvider.settings?.paymentMethods.where((m) => m.isEnabled).toList() ?? [];

    return Scaffold(
      appBar: AppBar(
        title: const Text('Subscription Checkout'),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Order Summary Card
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: AppTheme.getCardColor(context),
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: isDark ? Colors.white12 : Colors.grey.shade300),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('Plan Summary', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 12),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(widget.plan.name, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600)),
                      Text(widget.plan.formattedPrice, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: AppTheme.primary)),
                    ],
                  ),
                  const SizedBox(height: 4),
                  Text('Billing Period: ${widget.plan.billingPeriod.toUpperCase()} (${widget.plan.durationDays} days)', style: const TextStyle(fontSize: 12, color: Colors.grey)),
                  const SizedBox(height: 12),
                  const Divider(),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text('Total Payable:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                      Text(widget.plan.formattedPrice, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 20, color: AppTheme.primary)),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),

            // Select Payment Method
            const Text('Select Payment Method', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
            const SizedBox(height: 12),

            if (methods.isEmpty)
              _buildMethodOption('offline', 'Offline / Bank Transfer', HugeIcons.strokeRoundedBank)
            else
              ...methods.map((m) {
                dynamic icon = HugeIcons.strokeRoundedCreditCard;
                if (m.identifier == 'razorpay') icon = HugeIcons.strokeRoundedWallet01;
                if (m.identifier == 'sslcommerz') icon = HugeIcons.strokeRoundedSecurityCheck;
                if (m.identifier == 'bkash') icon = HugeIcons.strokeRoundedSmartPhone01;
                if (m.identifier == 'chapa') icon = HugeIcons.strokeRoundedWallet02;
                if (m.identifier == 'offline') icon = HugeIcons.strokeRoundedBank;
                return _buildMethodOption(m.identifier, m.name, icon);
              }),

            const SizedBox(height: 32),

            SizedBox(
              width: double.infinity,
              height: 50,
              child: ElevatedButton(
                onPressed: _isProcessing ? null : _processCheckout,
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppTheme.primary,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                ),
                child: _isProcessing
                    ? const CircularProgressIndicator(color: Colors.white)
                    : const Text(
                        'Complete Subscription Payment',
                        style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.white),
                      ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildMethodOption(String key, String title, dynamic icon) {
    final isSelected = _selectedMethod == key;
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      decoration: BoxDecoration(
        color: AppTheme.getCardColor(context),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: isSelected ? AppTheme.primary : (isDark ? Colors.white12 : Colors.grey.shade300),
          width: isSelected ? 2 : 1,
        ),
      ),
      child: ListTile(
        leading: HugeIcon(icon: icon, color: isSelected ? AppTheme.primary : Colors.grey, size: 20),
        title: Text(title, style: const TextStyle(fontWeight: FontWeight.w600)),
        trailing: Radio<String>(
          value: key,
          groupValue: _selectedMethod,
          onChanged: (val) {
            setState(() {
              _selectedMethod = val;
            });
          },
          activeColor: AppTheme.primary,
        ),
        onTap: () {
          setState(() {
            _selectedMethod = key;
          });
        },
      ),
    );
  }
}

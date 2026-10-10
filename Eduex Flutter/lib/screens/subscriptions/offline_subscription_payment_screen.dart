import 'dart:io';
import 'package:flutter/material.dart';
import 'package:hugeicons/hugeicons.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';
import '../../models/subscription_plan_model.dart';
import '../../models/platform_settings.dart';
import '../../providers/settings_provider.dart';
import '../../providers/subscription_provider.dart';
import '../../theme/app_theme.dart';
import '../profile/my_subscription_screen.dart';

class OfflineSubscriptionPaymentScreen extends StatefulWidget {
  final SubscriptionPlanModel plan;

  const OfflineSubscriptionPaymentScreen({
    super.key,
    required this.plan,
  });

  @override
  State<OfflineSubscriptionPaymentScreen> createState() => _OfflineSubscriptionPaymentScreenState();
}

class _OfflineSubscriptionPaymentScreenState extends State<OfflineSubscriptionPaymentScreen> {
  final _formKey = GlobalKey<FormState>();
  final _transactionController = TextEditingController();
  final _notesController = TextEditingController();
  File? _receiptFile;
  bool _isSubmitting = false;
  final ImagePicker _picker = ImagePicker();

  Future<void> _pickImage() async {
    final XFile? image = await _picker.pickImage(source: ImageSource.gallery);
    if (image != null) {
      setState(() {
        _receiptFile = File(image.path);
      });
    }
  }

  @override
  void dispose() {
    _transactionController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_receiptFile == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please upload a payment receipt image.')),
      );
      return;
    }

    setState(() {
      _isSubmitting = true;
    });

    try {
      final subProvider = Provider.of<SubscriptionProvider>(context, listen: false);
      final res = await subProvider.checkout(
        planId: widget.plan.id,
        paymentMethod: 'offline',
        transactionId: _transactionController.text.trim(),
        notes: _notesController.text.trim().isNotEmpty ? _notesController.text.trim() : null,
        receiptPath: _receiptFile!.path,
      );

      if (!mounted) return;

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(res['message'] ?? 'Subscription request submitted successfully! Pending admin approval.')),
      );

      Navigator.pushReplacement(
        context,
        MaterialPageRoute(builder: (_) => const MySubscriptionScreen()),
      );
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: ${e.toString().replaceAll('Exception: ', '')}')),
        );
      }
    } finally {
      if (mounted) {
        setState(() {
          _isSubmitting = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final settingsProvider = Provider.of<SettingsProvider>(context);

    return Scaffold(
      backgroundColor: AppTheme.getBackgroundColor(context),
      appBar: AppBar(
        title: Text(
          'Offline Payment',
          style: TextStyle(
            color: AppTheme.getTextColor(context),
            fontSize: 20,
            fontWeight: FontWeight.w700,
          ),
        ),
        backgroundColor: AppTheme.getBackgroundColor(context),
        elevation: 0,
        leading: IconButton(
          icon: HugeIcon(
            icon: HugeIcons.strokeRoundedArrowLeft01,
            size: 20,
            color: AppTheme.getTextColor(context),
          ),
          onPressed: () => Navigator.of(context).pop(),
        ),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Plan Summary Card
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: AppTheme.getCardColor(context),
                  borderRadius: BorderRadius.circular(16),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.05),
                      blurRadius: 10,
                      offset: const Offset(0, 4),
                    ),
                  ],
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      widget.plan.name,
                      style: TextStyle(
                        color: AppTheme.getTextColor(context),
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      settingsProvider.formatPrice(widget.plan.effectivePrice),
                      style: const TextStyle(
                        color: AppTheme.primary,
                        fontSize: 20,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 24),

              // Payment Instructions
              _buildInstructions(context, settingsProvider),

              Text(
                'Transaction Details',
                style: TextStyle(
                  color: AppTheme.getTextColor(context),
                  fontSize: 18,
                  fontWeight: FontWeight.w700,
                ),
              ),
              const SizedBox(height: 16),

              TextFormField(
                controller: _transactionController,
                decoration: InputDecoration(
                  labelText: 'Transaction ID / Reference',
                  hintText: 'Enter payment transaction ID',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                  prefixIcon: const Icon(Icons.receipt_long),
                ),
                validator: (value) {
                  if (value == null || value.trim().isEmpty) {
                    return 'Please enter transaction ID';
                  }
                  return null;
                },
              ),

              const SizedBox(height: 16),

              TextFormField(
                controller: _notesController,
                maxLines: 2,
                decoration: InputDecoration(
                  labelText: 'Additional Notes (Optional)',
                  hintText: 'Enter bank name or payment notes',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                  prefixIcon: const Icon(Icons.note_alt_outlined),
                ),
              ),

              const SizedBox(height: 24),

              // Payment Receipt Image Picker
              Text(
                'Payment Receipt',
                style: TextStyle(
                  color: AppTheme.getTextColor(context),
                  fontSize: 18,
                  fontWeight: FontWeight.w700,
                ),
              ),
              const SizedBox(height: 16),
              Material(
                color: Colors.transparent,
                child: InkWell(
                  onTap: _pickImage,
                  borderRadius: BorderRadius.circular(12),
                  child: Ink(
                    width: double.infinity,
                    height: 160,
                    decoration: BoxDecoration(
                      border: Border.all(color: Colors.grey[400]!, width: 1),
                      borderRadius: BorderRadius.circular(12),
                      color: AppTheme.getCardColor(context),
                    ),
                    child: _receiptFile != null
                        ? ClipRRect(
                            borderRadius: BorderRadius.circular(12),
                            child: Image.file(_receiptFile!, fit: BoxFit.cover),
                          )
                        : Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Icon(
                                Icons.add_a_photo,
                                size: 40,
                                color: Colors.grey[400],
                              ),
                              const SizedBox(height: 8),
                              Text(
                                'Upload Receipt Image',
                                style: TextStyle(color: Colors.grey[600]),
                              ),
                            ],
                          ),
                  ),
                ),
              ),

              const SizedBox(height: 32),

              // Submit Button
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: _isSubmitting ? null : _submit,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppTheme.primary,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(16),
                    ),
                  ),
                  child: _isSubmitting
                      ? const SizedBox(
                          height: 20,
                          width: 20,
                          child: CircularProgressIndicator(
                            color: Colors.white,
                            strokeWidth: 2,
                          ),
                        )
                      : const Text(
                          'Submit Subscription Request',
                          style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
                        ),
                ),
              ),
              const SizedBox(height: 24),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildInstructions(BuildContext context, SettingsProvider settingsProvider) {
    final offlineMethod = settingsProvider.paymentMethods.firstWhere(
      (method) => method.identifier == 'offline',
      orElse: () => PaymentMethod(
        identifier: 'offline',
        name: 'Offline Payment',
        type: 'offline',
        isEnabled: false,
        instructions: null,
      ),
    );
    final instructions = offlineMethod.instructions;

    if (instructions == null || instructions.trim().isEmpty) {
      return const SizedBox.shrink();
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Payment Details',
          style: TextStyle(
            color: AppTheme.getTextColor(context),
            fontSize: 18,
            fontWeight: FontWeight.w700,
          ),
        ),
        const SizedBox(height: 12),
        Container(
          width: double.infinity,
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: AppTheme.getMint100(context),
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: AppTheme.getMint200(context), width: 1),
          ),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              HugeIcon(
                icon: HugeIcons.strokeRoundedInformationDiamond,
                size: 24,
                color: AppTheme.getPrimaryColor(context),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Text(
                  instructions.trim(),
                  style: TextStyle(
                    color: AppTheme.getTextColor(context),
                    fontSize: 14,
                    fontWeight: FontWeight.w500,
                    height: 1.5,
                  ),
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 24),
      ],
    );
  }
}

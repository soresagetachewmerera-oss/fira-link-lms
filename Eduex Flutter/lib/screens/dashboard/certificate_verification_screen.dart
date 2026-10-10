import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:hugeicons/hugeicons.dart';

import '../../theme/app_theme.dart';
import '../../l10n/app_localizations.dart';
import '../../services/student_course_service.dart';

class CertificateVerificationScreen extends StatefulWidget {
  const CertificateVerificationScreen({super.key, this.initialCode});

  final String? initialCode;

  @override
  State<CertificateVerificationScreen> createState() =>
      _CertificateVerificationScreenState();
}

class _CertificateVerificationScreenState
    extends State<CertificateVerificationScreen> {
  final TextEditingController _codeController = TextEditingController();
  final StudentCourseService _courseService = StudentCourseService();

  bool _isLoading = false;
  Map<String, dynamic>? _verificationResult;

  @override
  void initState() {
    super.initState();
    if (widget.initialCode != null && widget.initialCode!.isNotEmpty) {
      _codeController.text = widget.initialCode!;
      _verifyCertificate();
    }
  }

  @override
  void dispose() {
    _codeController.dispose();
    super.dispose();
  }

  Future<void> _verifyCertificate() async {
    final String code = _codeController.text.trim();
    if (code.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Please enter a certificate number to verify.'),
        ),
      );
      return;
    }

    FocusScope.of(context).unfocus();

    setState(() {
      _isLoading = true;
      _verificationResult = null;
    });

    try {
      final response = await _courseService.verifyCertificate(code);
      final Map<String, dynamic> data = jsonDecode(response.body);

      if (data['success'] == true && data['data'] != null) {
        setState(() {
          _verificationResult = data['data'];
        });
      } else {
        setState(() {
          _verificationResult = {
            'is_valid': false,
            'message':
                data['message'] ??
                'No authentic certificate record was found for this code.',
          };
        });
      }
    } catch (e) {
      debugPrint('Error verifying certificate: $e');
      setState(() {
        _verificationResult = {
          'is_valid': false,
          'message': 'Failed to verify certificate. Please check your connection and try again.',
        };
      });
    } finally {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.getBackgroundColor(context),
      body: SafeArea(
        child: Column(
          children: [
            // Header
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 12, 20, 16),
              child: Row(
                children: [
                  IconButton(
                    icon: HugeIcon(
                      icon: HugeIcons.strokeRoundedArrowLeft01,
                      size: 20,
                      color: AppTheme.getTextColor(context),
                    ),
                    onPressed: () => Navigator.of(context).pop(),
                    padding: EdgeInsets.zero,
                  ),
                  Expanded(
                    child: Text(
                      context.l10n.verifyCertificate,
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        color: AppTheme.getTextColor(context),
                        fontSize: 20,
                        fontWeight: FontWeight.w700,
                        letterSpacing: 0.3,
                      ),
                    ),
                  ),
                  const SizedBox(width: 40),
                ],
              ),
            ),

            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.symmetric(horizontal: 20),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    const SizedBox(height: 12),
                    // Banner Icon & Info
                    Container(
                      padding: const EdgeInsets.all(20),
                      decoration: BoxDecoration(
                        color: AppTheme.getCardColor(context),
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: Column(
                        children: [
                          Container(
                            width: 64,
                            height: 64,
                            decoration: BoxDecoration(
                              color: AppTheme.getMint100(context),
                              shape: BoxShape.circle,
                            ),
                            child: Center(
                              child: HugeIcon(
                                icon: HugeIcons.strokeRoundedCertificate01,
                                size: 32,
                                color: AppTheme.getPrimaryColor(context),
                              ),
                            ),
                          ),
                          const SizedBox(height: 16),
                          Text(
                            context.l10n.certificateVerifier,
                            style: TextStyle(
                              color: AppTheme.getTextColor(context),
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                            ),
                            textAlign: TextAlign.center,
                          ),
                          const SizedBox(height: 8),
                          Text(
                            context.l10n.verifyCertificateDesc,
                            style: TextStyle(
                              color: AppTheme.getTextColor(
                                context,
                              ).withValues(alpha: 0.6),
                              fontSize: 13,
                              height: 1.4,
                            ),
                            textAlign: TextAlign.center,
                          ),
                          const SizedBox(height: 20),

                          // Search TextField
                          TextField(
                            controller: _codeController,
                            textCapitalization: TextCapitalization.characters,
                            style: TextStyle(
                              color: AppTheme.getTextColor(context),
                              fontWeight: FontWeight.w600,
                              fontSize: 15,
                            ),
                            decoration: InputDecoration(
                              hintText: 'e.g. CERT-000012-A1B2C3',
                              hintStyle: TextStyle(
                                color: AppTheme.getTextColor(
                                  context,
                                ).withValues(alpha: 0.4),
                                fontWeight: FontWeight.normal,
                              ),
                              prefixIcon: HugeIcon(
                                icon: HugeIcons.strokeRoundedQrCode,
                                color: AppTheme.getPrimaryColor(context),
                                size: 20,
                              ),
                              suffixIcon: Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  if (_codeController.text.isNotEmpty)
                                    IconButton(
                                      icon: const Icon(Icons.clear, size: 18),
                                      onPressed: () {
                                        _codeController.clear();
                                        setState(() {
                                          _verificationResult = null;
                                        });
                                      },
                                    ),
                                  IconButton(
                                    icon: const Icon(Icons.paste_rounded, size: 18),
                                    onPressed: () async {
                                      final clipboardData =
                                          await Clipboard.getData('text/plain');
                                      if (clipboardData?.text != null) {
                                        setState(() {
                                          _codeController.text =
                                              clipboardData!.text!.trim();
                                        });
                                      }
                                    },
                                  ),
                                ],
                              ),
                              filled: true,
                              fillColor: AppTheme.getSoftGray150(context),
                              border: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(16),
                                borderSide: BorderSide.none,
                              ),
                              contentPadding: const EdgeInsets.symmetric(
                                horizontal: 16,
                                vertical: 16,
                              ),
                            ),
                            onSubmitted: (_) => _verifyCertificate(),
                          ),

                          const SizedBox(height: 16),

                          // Verify Button
                          SizedBox(
                            width: double.infinity,
                            height: 50,
                            child: ElevatedButton(
                              onPressed: _isLoading ? null : _verifyCertificate,
                              style: ElevatedButton.styleFrom(
                                backgroundColor: AppTheme.getPrimaryColor(context),
                                foregroundColor: Colors.white,
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(16),
                                ),
                                elevation: 0,
                              ),
                              child: _isLoading
                                  ? const SizedBox(
                                      width: 24,
                                      height: 24,
                                      child: CircularProgressIndicator(
                                        color: Colors.white,
                                        strokeWidth: 2.5,
                                      ),
                                    )
                                  : Row(
                                      mainAxisAlignment:
                                          MainAxisAlignment.center,
                                      children: [
                                        const HugeIcon(
                                          icon: HugeIcons.strokeRoundedCheckmarkBadge01,
                                          color: Colors.white,
                                          size: 20,
                                        ),
                                        const SizedBox(width: 8),
                                        Text(
                                          context.l10n.verifyCertificate,
                                          style: const TextStyle(
                                            fontSize: 16,
                                            fontWeight: FontWeight.bold,
                                          ),
                                        ),
                                      ],
                                    ),
                            ),
                          ),
                        ],
                      ),
                    ),

                    const SizedBox(height: 24),

                    // Result Area
                    if (_verificationResult != null)
                      _buildResultWidget(_verificationResult!),

                    const SizedBox(height: 30),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildResultWidget(Map<String, dynamic> result) {
    final bool isValid = result['is_valid'] == true;

    if (isValid) {
      return Container(
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          color: AppTheme.getCardColor(context),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: const Color(0xFF10B981).withValues(alpha: 0.4),
            width: 1.5,
          ),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Status Header Banner
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              decoration: BoxDecoration(
                color: const Color(0xFF10B981).withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Row(
                children: [
                  const Icon(
                    Icons.verified_rounded,
                    color: Color(0xFF10B981),
                    size: 24,
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      context.l10n.authenticCertificate,
                      style: const TextStyle(
                        color: Color(0xFF10B981),
                        fontWeight: FontWeight.bold,
                        fontSize: 15,
                      ),
                    ),
                  ),
                ],
              ),
            ),

            const SizedBox(height: 20),

            _buildDetailRow(
              'Certificate Number',
              result['certificate_number'] ?? '',
              isHighlight: true,
            ),
            const Divider(height: 24),
            _buildDetailRow(
              'Student Name',
              result['student_name'] ?? '',
            ),
            const Divider(height: 24),
            _buildDetailRow(
              'Course Title',
              result['course_title'] ?? '',
            ),
            const Divider(height: 24),
            _buildDetailRow(
              'Instructor',
              result['instructor_name'] ?? '',
            ),
            const Divider(height: 24),
            _buildDetailRow(
              'Completion Date',
              result['completion_date'] ?? '',
            ),
          ],
        ),
      );
    } else {
      return Container(
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          color: AppTheme.getCardColor(context),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: Colors.redAccent.withValues(alpha: 0.4),
            width: 1.5,
          ),
        ),
        child: Column(
          children: [
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              decoration: BoxDecoration(
                color: Colors.redAccent.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Row(
                children: [
                  const Icon(
                    Icons.error_outline_rounded,
                    color: Colors.redAccent,
                    size: 24,
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      context.l10n.unverifiedCertificate,
                      style: const TextStyle(
                        color: Colors.redAccent,
                        fontWeight: FontWeight.bold,
                        fontSize: 15,
                      ),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),
            Text(
              result['message'] ??
                  'No authentic record was found for this certificate code.',
              style: TextStyle(
                color: AppTheme.getTextColor(context).withValues(alpha: 0.7),
                fontSize: 14,
                height: 1.4,
              ),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      );
    }
  }

  Widget _buildDetailRow(String label, String value, {bool isHighlight = false}) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label.toUpperCase(),
          style: TextStyle(
            color: AppTheme.getTextColor(context).withValues(alpha: 0.5),
            fontSize: 11,
            fontWeight: FontWeight.w600,
            letterSpacing: 0.8,
          ),
        ),
        const SizedBox(height: 4),
        Text(
          value,
          style: TextStyle(
            color: isHighlight
                ? AppTheme.getPrimaryColor(context)
                : AppTheme.getTextColor(context),
            fontSize: isHighlight ? 16 : 15,
            fontWeight: isHighlight ? FontWeight.bold : FontWeight.w600,
          ),
        ),
      ],
    );
  }
}

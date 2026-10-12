import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_svg/flutter_svg.dart';
import 'package:hugeicons/hugeicons.dart';
import 'package:provider/provider.dart';
import 'package:toastification/toastification.dart';
import '../../theme/app_theme.dart';
import '../../l10n/app_localizations.dart';
import '../../providers/auth_provider.dart';
import '../dashboard/dashboard_screen.dart';
import 'auth_screen.dart';

class OtpVerificationScreen extends StatefulWidget {
  const OtpVerificationScreen({
    super.key,
    this.email,
    this.phone,
    this.phoneMasked,
    this.isRegistration = false,
  });

  final String? email;
  final String? phone;
  final String? phoneMasked;
  final bool isRegistration;

  @override
  State<OtpVerificationScreen> createState() => _OtpVerificationScreenState();
}

class _OtpVerificationScreenState extends State<OtpVerificationScreen> {
  final List<TextEditingController> _controllers = List.generate(
    6,
    (_) => TextEditingController(),
  );
  final List<FocusNode> _focusNodes = List.generate(6, (_) => FocusNode());
  int _resendCountdown = 60;
  bool _canResend = false;
  Timer? _countdownTimer;

  late String _currentPhone;
  late String _currentPhoneMasked;

  @override
  void initState() {
    super.initState();
    _currentPhone = widget.phone ?? '';
    _currentPhoneMasked = widget.phoneMasked ?? _maskPhone(widget.phone ?? '');
    _startCountdown();

    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) {
        _focusNodes[0].requestFocus();
      }
    });
  }

  String _maskPhone(String raw) {
    if (raw.length <= 4) return raw;
    final start = raw.substring(0, raw.length > 6 ? 5 : 2);
    final end = raw.substring(raw.length - 2);
    final masked = '*' * (raw.length - start.length - end.length);
    return '$start$masked$end';
  }

  void _startCountdown() {
    _countdownTimer?.cancel();
    _countdownTimer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (!mounted) {
        timer.cancel();
        return;
      }
      setState(() {
        if (_resendCountdown > 0) {
          _resendCountdown--;
        } else {
          _canResend = true;
          timer.cancel();
        }
      });
    });
  }

  void _onOtpChanged(int index, String value) {
    if (value.length == 1) {
      if (index < 5) {
        _focusNodes[index + 1].requestFocus();
      } else {
        _focusNodes[index].unfocus();
        _verifyOtp();
      }
    } else if (value.isEmpty && index > 0) {
      _focusNodes[index - 1].requestFocus();
    }
  }

  Future<void> _verifyOtp() async {
    if (!mounted) return;
    final String otp = _controllers.map((c) => c.text).join();
    if (otp.length == 6) {
      final authProvider = Provider.of<AuthProvider>(context, listen: false);

      final Map<String, dynamic> result;
      if (widget.isRegistration) {
        result = await authProvider.verifyRegistrationOtp(_currentPhone, otp);
      } else {
        result = await authProvider.verifyOtp(widget.email!, otp);
      }

      if (!mounted) return;

      if (result['success']) {
        toastification.show(
          context: context,
          type: ToastificationType.success,
          title: const Text('Verification Successful'),
          description: Text(result['message'] ?? 'Phone verified and account activated!'),
          autoCloseDuration: const Duration(seconds: 3),
        );
        Navigator.of(context).pushAndRemoveUntil(
          MaterialPageRoute(builder: (_) => const DashboardScreen()),
          (route) => false,
        );
      } else {
        toastification.show(
          context: context,
          type: ToastificationType.error,
          title: const Text('Verification Failed'),
          description: Text(result['message'] ?? 'Invalid or expired OTP code.'),
          autoCloseDuration: const Duration(seconds: 4),
        );
      }
    }
  }

  Future<void> _resendCode() async {
    if (!mounted || !_canResend) return;

    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final Map<String, dynamic> result;

    if (widget.isRegistration) {
      result = await authProvider.resendRegistrationOtp(_currentPhone);
    } else {
      result = await authProvider.resendOtp(widget.email!);
    }

    if (!mounted) return;

    if (result['success']) {
      final int cooldown = (result['cooldown_seconds'] is int)
          ? result['cooldown_seconds']
          : 60;
      setState(() {
        _canResend = false;
        _resendCountdown = cooldown;
        for (final controller in _controllers) {
          controller.clear();
        }
        if (mounted) {
          _focusNodes[0].requestFocus();
        }
      });
      _startCountdown();

      toastification.show(
        context: context,
        type: ToastificationType.success,
        title: const Text('Code Resent'),
        description: Text(
          widget.isRegistration
              ? 'New SMS verification code dispatched.'
              : 'Check your email for the new code.',
        ),
        autoCloseDuration: const Duration(seconds: 3),
      );
    } else {
      final dynamic cooldown = result['cooldown_seconds'];
      if (cooldown != null && cooldown is int && cooldown > 0) {
        setState(() {
          _canResend = false;
          _resendCountdown = cooldown;
        });
        _startCountdown();
      }
      toastification.show(
        context: context,
        type: ToastificationType.error,
        title: const Text('Resend Failed'),
        description: Text(result['message'] ?? 'Failed to resend code'),
        autoCloseDuration: const Duration(seconds: 4),
      );
    }
  }

  Future<void> _showChangePhoneDialog() async {
    final TextEditingController newPhoneController = TextEditingController();
    final formKey = GlobalKey<FormState>();

    await showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (modalContext) {
        return Padding(
          padding: EdgeInsets.only(
            bottom: MediaQuery.of(modalContext).viewInsets.bottom,
          ),
          child: Container(
            decoration: BoxDecoration(
              color: AppTheme.getCardColor(context),
              borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
            ),
            padding: const EdgeInsets.all(24),
            child: Form(
              key: formKey,
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        'Change Phone Number',
                        style: TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.bold,
                          color: AppTheme.getTextColor(context),
                        ),
                      ),
                      IconButton(
                        icon: const Icon(Icons.close),
                        onPressed: () => Navigator.of(modalContext).pop(),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'Enter your correct Ethiopian mobile number. Your previous verification code will be invalidated, and a fresh code will be sent.',
                    style: TextStyle(
                      fontSize: 14,
                      color: AppTheme.getTextColor(context).withValues(alpha: 0.7),
                      height: 1.4,
                    ),
                  ),
                  const SizedBox(height: 16),
                  TextFormField(
                    controller: newPhoneController,
                    keyboardType: TextInputType.phone,
                    decoration: InputDecoration(
                      labelText: 'New Phone Number',
                      hintText: '0911234567 or +251911234567',
                      filled: true,
                      fillColor: Theme.of(context).brightness == Brightness.dark
                          ? AppTheme.surfaceDark
                          : Colors.grey[100],
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                    validator: (val) {
                      if (val == null || val.trim().isEmpty) return 'Phone number cannot be empty';
                      final clean = val.replaceAll(RegExp(r'[\s\-\(\)]'), '');
                      final reg = RegExp(r'^(?:\+251|251|0)?([79]\d{8})$');
                      if (!reg.hasMatch(clean)) return 'Enter a valid Ethiopian number (09... or 07...)';
                      return null;
                    },
                  ),
                  const SizedBox(height: 20),
                  SizedBox(
                    width: double.infinity,
                    height: 52,
                    child: ElevatedButton(
                      onPressed: () async {
                        if (!formKey.currentState!.validate()) return;
                        Navigator.of(modalContext).pop();
                        await _handleChangePhone(newPhoneController.text.trim());
                      },
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppTheme.getTextColor(context),
                        foregroundColor: AppTheme.getCardColor(context),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                      child: const Text(
                        'Update Phone & Resend OTP',
                        style: TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
                      ),
                    ),
                  ),
                  const SizedBox(height: 12),
                ],
              ),
            ),
          ),
        );
      },
    );
  }

  Future<void> _handleChangePhone(String newPhone) async {
    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final result = await authProvider.changeRegistrationPhone(_currentPhone, newPhone);

    if (!mounted) return;

    if (result['success']) {
      final int cooldown = (result['cooldown_seconds'] is int)
          ? result['cooldown_seconds']
          : 60;
      setState(() {
        _currentPhone = result['phone'] ?? newPhone;
        _currentPhoneMasked = result['phone_masked'] ?? _maskPhone(newPhone);
        _canResend = false;
        _resendCountdown = cooldown;
        for (final c in _controllers) {
          c.clear();
        }
        _focusNodes[0].requestFocus();
      });
      _startCountdown();

      toastification.show(
        context: context,
        type: ToastificationType.success,
        title: const Text('Phone Updated'),
        description: Text(result['message'] ?? 'New verification code dispatched via SMS.'),
        autoCloseDuration: const Duration(seconds: 4),
      );
    } else {
      toastification.show(
        context: context,
        type: ToastificationType.error,
        title: const Text('Update Failed'),
        description: Text(result['message'] ?? 'Could not update phone number.'),
        autoCloseDuration: const Duration(seconds: 4),
      );
    }
  }

  @override
  void dispose() {
    _countdownTimer?.cancel();
    for (final controller in _controllers) {
      controller.dispose();
    }
    for (final node in _focusNodes) {
      node.dispose();
    }
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final Size size = MediaQuery.of(context).size;
    final authProvider = Provider.of<AuthProvider>(context);

    return Scaffold(
      body: Stack(
        children: <Widget>[
          _BackgroundDecoration(size: size),

          SafeArea(
            child: SingleChildScrollView(
              child: Column(
                children: <Widget>[
                  const SizedBox(height: 80),
                  _HeaderSection(),
                  const SizedBox(height: 40),
                  _OtpVerificationCard(
                    controllers: _controllers,
                    focusNodes: _focusNodes,
                    onOtpChanged: _onOtpChanged,
                    onVerify: _verifyOtp,
                    onResend: _resendCode,
                    onChangePhone: widget.isRegistration ? _showChangePhoneDialog : null,
                    canResend: _canResend,
                    resendCountdown: _resendCountdown,
                    isLoading: authProvider.isLoading,
                    email: widget.email,
                    phone: _currentPhone,
                    phoneMasked: _currentPhoneMasked,
                    isRegistration: widget.isRegistration,
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _BackgroundDecoration extends StatelessWidget {
  const _BackgroundDecoration({required this.size});

  final Size size;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          colors: <Color>[
            AppTheme.getBackgroundColor(context),
            AppTheme.getCardColor(context),
          ],
          stops: const <double>[0.0, 0.5],
        ),
      ),
    );
  }
}

class _HeaderSection extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    final bool isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final String logoPath = isDarkMode
        ? 'assets/img/logo_white.svg'
        : 'assets/img/logo.svg';

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 24.0),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: <Widget>[SvgPicture.asset(logoPath, width: 142, height: 44)],
      ),
    );
  }
}

class _OtpVerificationCard extends StatelessWidget {
  const _OtpVerificationCard({
    required this.controllers,
    required this.focusNodes,
    required this.onOtpChanged,
    required this.onVerify,
    required this.onResend,
    this.onChangePhone,
    required this.canResend,
    required this.resendCountdown,
    required this.isLoading,
    this.email,
    this.phone,
    this.phoneMasked,
    required this.isRegistration,
  });

  final List<TextEditingController> controllers;
  final List<FocusNode> focusNodes;
  final void Function(int, String) onOtpChanged;
  final VoidCallback onVerify;
  final VoidCallback onResend;
  final VoidCallback? onChangePhone;
  final bool canResend;
  final int resendCountdown;
  final bool isLoading;
  final String? email;
  final String? phone;
  final String? phoneMasked;
  final bool isRegistration;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: AppTheme.getCardColor(context),
        borderRadius: const BorderRadius.only(
          topLeft: Radius.circular(32),
          topRight: Radius.circular(32),
        ),
        boxShadow: <BoxShadow>[
          BoxShadow(
            color: AppTheme.getTextColor(context).withValues(alpha: 0.08),
            blurRadius: 20,
            offset: const Offset(0, -4),
          ),
        ],
      ),
      child: Padding(
        padding: const EdgeInsets.fromLTRB(24.0, 32.0, 24.0, 40.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: <Widget>[
            IconButton(
              icon: HugeIcon(
                icon: HugeIcons.strokeRoundedArrowLeft01,
                size: 20,
                color: AppTheme.getTextColor(context),
              ),
              onPressed: () => Navigator.of(context).pop(),
              padding: EdgeInsets.zero,
              alignment: Alignment.centerLeft,
            ),
            const SizedBox(height: 16),

            Text(
              isRegistration ? 'Verify Your Phone' : context.l10n.otpVerificationTitle,
              style: TextStyle(
                color: AppTheme.getTextColor(context),
                fontSize: 28,
                fontWeight: FontWeight.w700,
                letterSpacing: 0.2,
              ),
            ),
            const SizedBox(height: 12),

            Text(
              isRegistration
                  ? 'We have sent a 6-digit SMS verification code via SMS Ethiopia to complete your account registration:'
                  : context.l10n.otpVerificationSubtitle,
              style: TextStyle(
                color: AppTheme.getTextColor(context).withValues(alpha: 0.7),
                fontSize: 14,
                fontWeight: FontWeight.w400,
                height: 1.5,
              ),
            ),
            const SizedBox(height: 12),

            if (isRegistration && phone != null) ...[
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                decoration: BoxDecoration(
                  color: Theme.of(context).brightness == Brightness.dark
                      ? AppTheme.surfaceDark
                      : Colors.grey[100],
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(
                    color: AppTheme.getTextColor(context).withValues(alpha: 0.15),
                  ),
                ),
                child: Row(
                  children: [
                    Icon(
                      Icons.phone_android,
                      size: 20,
                      color: AppTheme.getTextColor(context),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        phoneMasked ?? phone!,
                        style: TextStyle(
                          color: AppTheme.getTextColor(context),
                          fontSize: 15,
                          fontWeight: FontWeight.w600,
                          letterSpacing: 1,
                        ),
                      ),
                    ),
                    if (onChangePhone != null)
                      TextButton(
                        onPressed: onChangePhone,
                        style: TextButton.styleFrom(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                          minimumSize: Size.zero,
                          tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                        ),
                        child: Text(
                          'Correct',
                          style: TextStyle(
                            color: Theme.of(context).primaryColor,
                            fontWeight: FontWeight.bold,
                            fontSize: 13,
                          ),
                        ),
                      ),
                  ],
                ),
              ),
            ] else if (email != null) ...[
              Text(
                email!,
                style: TextStyle(
                  color: AppTheme.getTextColor(context).withValues(alpha: 0.8),
                  fontSize: 14,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ],

            const SizedBox(height: 32),
            // OTP Input Fields
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: List.generate(
                6,
                (index) => SizedBox(
                  width: 48,
                  height: 64,
                  child: TextField(
                    controller: controllers[index],
                    focusNode: focusNodes[index],
                    textAlign: TextAlign.center,
                    keyboardType: TextInputType.number,
                    maxLength: 1,
                    style: TextStyle(
                      color: AppTheme.getTextColor(context),
                      fontSize: 24,
                      fontWeight: FontWeight.w700,
                      letterSpacing: 2,
                    ),
                    decoration: InputDecoration(
                      counterText: '',
                      filled: true,
                      fillColor: Theme.of(context).brightness == Brightness.dark
                          ? AppTheme.surfaceDark.withValues(alpha: 0.5)
                          : Colors.grey[50],
                      enabledBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(12),
                        borderSide: BorderSide(
                          color: AppTheme.getTextColor(
                            context,
                          ).withValues(alpha: 0.2),
                          width: 1,
                        ),
                      ),
                      focusedBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(12),
                        borderSide: BorderSide(
                          color: AppTheme.getTextColor(context),
                          width: 2,
                        ),
                      ),
                      contentPadding: EdgeInsets.zero,
                    ),
                    inputFormatters: <TextInputFormatter>[
                      FilteringTextInputFormatter.digitsOnly,
                    ],
                    onChanged: (String value) => onOtpChanged(index, value),
                  ),
                ),
              ),
            ),
            const SizedBox(height: 32),
            // Verify button
            SizedBox(
              width: double.infinity,
              height: 56,
              child: ElevatedButton(
                onPressed: isLoading ? null : onVerify,
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppTheme.getTextColor(context),
                  foregroundColor: AppTheme.getCardColor(context),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(16),
                  ),
                  elevation: 0,
                ),
                child: isLoading
                    ? const SizedBox(
                        width: 24,
                        height: 24,
                        child: CircularProgressIndicator(
                          color: Colors.white,
                          strokeWidth: 2,
                        ),
                      )
                    : Text(
                        context.l10n.verify,
                        style: const TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.w600,
                          letterSpacing: 0.3,
                        ),
                      ),
              ),
            ),
            const SizedBox(height: 24),
            // Resend code section
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: <Widget>[
                Text(
                  context.l10n.didntReceiveCode,
                  style: TextStyle(
                    color: AppTheme.getTextColor(context).withValues(alpha: 0.6),
                    fontSize: 14,
                    fontWeight: FontWeight.w400,
                  ),
                ),
                const SizedBox(width: 4),
                TextButton(
                  onPressed: canResend ? onResend : null,
                  style: TextButton.styleFrom(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 4,
                    ),
                    minimumSize: Size.zero,
                    tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                  ),
                  child: Text(
                    canResend
                        ? context.l10n.resendCode
                        : '${context.l10n.resendCode} (${resendCountdown}s)',
                    style: TextStyle(
                      color: canResend
                          ? AppTheme.getTextColor(context)
                          : AppTheme.getTextColor(context).withValues(alpha: 0.4),
                      fontSize: 14,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),
            // Back to Login link
            Center(
              child: TextButton(
                onPressed: () {
                  Navigator.of(context).pushAndRemoveUntil(
                    MaterialPageRoute(builder: (context) => const AuthScreen()),
                    (route) => false,
                  );
                },
                style: TextButton.styleFrom(
                  padding: EdgeInsets.zero,
                  minimumSize: Size.zero,
                  tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                ),
                child: Text(
                  context.l10n.backToLogin,
                  style: TextStyle(
                    color: AppTheme.getTextColor(context).withValues(alpha: 0.7),
                    fontSize: 14,
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

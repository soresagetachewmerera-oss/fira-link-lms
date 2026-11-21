import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';
import 'package:provider/provider.dart';
import 'package:toastification/toastification.dart';
import '../../widgets/auth/auth_gate.dart';
import '../../theme/app_theme.dart';
import '../../theme/fira_colors.dart';
import '../../l10n/app_localizations.dart';
import '../../providers/auth_provider.dart';
import '../../config/config.dart';
import '../../widgets/auth/auth_navigation.dart';
import 'email_sign_up_screen.dart';
import 'forgot_password_screen.dart';
import 'otp_verification_screen.dart';
import 'phone_sign_in_screen.dart';

class AuthScreen extends StatefulWidget {
  const AuthScreen({super.key});

  @override
  State<AuthScreen> createState() => _AuthScreenState();
}

class _AuthScreenState extends State<AuthScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  bool _isPasswordVisible = false;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final Size size = MediaQuery.of(context).size;
    return Scaffold(
      body: Stack(
        children: <Widget>[
          _BackgroundDecoration(size: size),

          SafeArea(
            child: SingleChildScrollView(
              child: Column(
                children: <Widget>[
                  const SizedBox(height: 24),
                  // Header with logo and branding - centered
                  _HeaderSection(),
                  const SizedBox(height: 16),
                  // Login card
                  _LoginCard(
                    tabController: _tabController,
                    isPasswordVisible: _isPasswordVisible,
                    onPasswordVisibilityToggle: () {
                      setState(() {
                        _isPasswordVisible = !_isPasswordVisible;
                      });
                    },
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
    final bool isDark = Theme.of(context).brightness == Brightness.dark;
    return Container(
      color: isDark ? AppTheme.surfaceDark : const Color(0xFFF9F7FA),
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

class _LoginCard extends StatelessWidget {
  const _LoginCard({
    required this.tabController,
    required this.isPasswordVisible,
    required this.onPasswordVisibilityToggle,
  });

  final TabController tabController;
  final bool isPasswordVisible;
  final VoidCallback onPasswordVisibilityToggle;

  @override
  Widget build(BuildContext context) {
    final fira = context.fira;
    final bool isDarkMode = Theme.of(context).brightness == Brightness.dark;

    return Container(
      decoration: BoxDecoration(
        color: fira.card,
        borderRadius: const BorderRadius.only(
          topLeft: Radius.circular(32),
          topRight: Radius.circular(32),
        ),
        boxShadow: <BoxShadow>[
          BoxShadow(
            color: fira.brandDeep.withValues(alpha: 0.06),
            blurRadius: 24,
            offset: const Offset(0, -4),
          ),
        ],
      ),
      child: Column(
        children: <Widget>[
          const SizedBox(height: 20),
          // Segmented brand tab bar
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 24.0),
            child: Container(
              height: 50,
              padding: const EdgeInsets.all(4),
              decoration: BoxDecoration(
                color: isDarkMode
                    ? AppTheme.surfaceDark
                    : const Color(0xFFF3E8FF).withValues(alpha: 0.5),
                borderRadius: BorderRadius.circular(16),
                border: Border.all(
                  color: isDarkMode
                      ? const Color(0xFF3B0764).withValues(alpha: 0.3)
                      : const Color(0xFFE9D5FF),
                  width: 1,
                ),
              ),
              child: TabBar(
                controller: tabController,
                indicator: BoxDecoration(
                  color: fira.brandPrimary,
                  borderRadius: BorderRadius.circular(12),
                  boxShadow: <BoxShadow>[
                    BoxShadow(
                      color: fira.brandPrimary.withValues(alpha: 0.25),
                      blurRadius: 8,
                      offset: const Offset(0, 2),
                    ),
                  ],
                ),
                indicatorSize: TabBarIndicatorSize.tab,
                isScrollable: false,
                padding: EdgeInsets.zero,
                labelPadding: EdgeInsets.zero,
                labelColor: Colors.white,
                unselectedLabelColor: isDarkMode
                    ? Colors.white.withValues(alpha: 0.7)
                    : fira.onSurface.withValues(alpha: 0.7),
                labelStyle: const TextStyle(
                  fontFamily: 'Montserrat',
                  fontSize: 15,
                  fontWeight: FontWeight.w600,
                  letterSpacing: 0.2,
                ),
                unselectedLabelStyle: const TextStyle(
                  fontFamily: 'Montserrat',
                  fontSize: 15,
                  fontWeight: FontWeight.w500,
                  letterSpacing: 0.2,
                ),
                dividerColor: Colors.transparent,
                tabs: <Widget>[
                  Tab(text: context.l10n.login),
                  Tab(text: context.l10n.signup),
                ],
              ),
            ),
          ),
          const SizedBox(height: 24),
          // Tab bar view with forms
          SizedBox(
            height: 640,
            child: TabBarView(
              controller: tabController,
              children: <Widget>[
                _LoginForm(
                  tabController: tabController,
                  isPasswordVisible: isPasswordVisible,
                  onPasswordVisibilityToggle: onPasswordVisibilityToggle,
                ),
                _SignUpForm(
                  tabController: tabController,
                  isPasswordVisible: isPasswordVisible,
                  onPasswordVisibilityToggle: onPasswordVisibilityToggle,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _LoginForm extends StatefulWidget {
  const _LoginForm({
    required this.tabController,
    required this.isPasswordVisible,
    required this.onPasswordVisibilityToggle,
  });

  final TabController tabController;
  final bool isPasswordVisible;
  final VoidCallback onPasswordVisibilityToggle;

  @override
  State<_LoginForm> createState() => _LoginFormState();
}

class _LoginFormState extends State<_LoginForm> {
  final TextEditingController _emailController = TextEditingController();
  final TextEditingController _passwordController = TextEditingController();

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _handleLogin() async {
    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final email = _emailController.text.trim();
    final password = _passwordController.text;

    if (email.isEmpty || password.isEmpty) {
      toastification.show(
        context: context,
        type: ToastificationType.warning,
        style: ToastificationStyle.fillColored,
        title: const Text('Invalid Input'),
        description: const Text('Please enter both email and password'),
        alignment: Alignment.bottomCenter,
        autoCloseDuration: const Duration(seconds: 3),
      );
      return;
    }

    final result = await authProvider.login(email, password);

    if (!mounted) return;

    if (result['success']) {
      if (result['otp_required'] == true) {
        toastification.show(
          context: context,
          type: ToastificationType.info,
          style: ToastificationStyle.fillColored,
          title: const Text('OTP Sent'),
          description: Text(result['message'] ?? 'Please verify your email'),
          alignment: Alignment.bottomCenter,
          autoCloseDuration: const Duration(seconds: 3),
        );

        Navigator.of(context).push(
          MaterialPageRoute(
            builder: (_) =>
                OtpVerificationScreen(email: result['email'] ?? email),
          ),
        );
      } else {
        toastification.show(
          context: context,
          type: ToastificationType.success,
          style: ToastificationStyle.fillColored,
          title: const Text('Login Successful'),
          description: Text(result['message'] ?? 'Welcome back!'),
          alignment: Alignment.bottomCenter,
          autoCloseDuration: const Duration(seconds: 3),
        );

        if (SignInResume.tryResume(context)) return;
        if (Navigator.of(context).canPop()) {
          Navigator.of(context).pop();
        } else {
          Navigator.of(context).pushReplacement(
            MaterialPageRoute(builder: (_) => AppConfig.getHomePage()),
          );
        }
      }
    } else {
      if (result['requires_phone_verification'] == true) {
        toastification.show(
          context: context,
          type: ToastificationType.info,
          style: ToastificationStyle.fillColored,
          title: const Text('Phone Verification Required'),
          description: Text(result['message'] ?? 'Please verify your phone number via SMS OTP'),
          alignment: Alignment.bottomCenter,
          autoCloseDuration: const Duration(seconds: 4),
        );

        Navigator.of(context).push(
          MaterialPageRoute(
            builder: (_) => OtpVerificationScreen(
              phone: result['phone'],
              phoneMasked: result['phone_masked'],
              email: email,
              isRegistration: true,
            ),
          ),
        );
        return;
      }

      // Flagged weak password: no token until a new password is chosen.
      if (result['code'] == 'PASSWORD_RESET_REQUIRED') {
        Navigator.of(context).push(MaterialPageRoute(
          builder: (_) => ForgotPasswordScreen(initialMessage: context.l10n.passwordResetRequired),
        ));
        return;
      }

      toastification.show(
        context: context,
        type: ToastificationType.error,
        style: ToastificationStyle.fillColored,
        title: const Text('Login Failed'),
        description: Text(result['code'] == 'INVALID_CREDENTIALS'
            ? context.l10n.invalidEmailOrPassword
            : (result['message'] ?? context.l10n.invalidEmailOrPassword)),
        alignment: Alignment.bottomCenter,
        autoCloseDuration: const Duration(seconds: 4),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final authProvider = Provider.of<AuthProvider>(context);
    final fira = context.fira;
    final bool isDarkMode = Theme.of(context).brightness == Brightness.dark;

    return SingleChildScrollView(
      padding: const EdgeInsets.fromLTRB(24.0, 8.0, 24.0, 0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          TextField(
            controller: _emailController,
            keyboardType: TextInputType.emailAddress,
            autofillHints: const [AutofillHints.email],
            textInputAction: TextInputAction.next,
            decoration: InputDecoration(
              labelText: context.l10n.email,
              hintText: 'name@company.com',
              filled: true,
              fillColor: isDarkMode
                  ? AppTheme.surfaceDark.withValues(alpha: 0.6)
                  : Colors.white,
              labelStyle: TextStyle(
                color: fira.mutedText,
                fontSize: 14,
                fontWeight: FontWeight.w500,
              ),
              floatingLabelStyle: TextStyle(
                color: fira.brandPrimary,
                fontSize: 14,
                fontWeight: FontWeight.w600,
              ),
              hintStyle: TextStyle(
                color: fira.mutedText.withValues(alpha: 0.5),
                fontSize: 15,
              ),
              enabledBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(16),
                borderSide: const BorderSide(
                  color: Color(0xFFE9D5FF),
                  width: 1,
                ),
              ),
              focusedBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(16),
                borderSide: BorderSide(
                  color: fira.brandPrimary,
                  width: 1.5,
                ),
              ),
              contentPadding: const EdgeInsets.symmetric(
                horizontal: 16,
                vertical: 16,
              ),
            ),
            style: TextStyle(
              color: fira.onSurface,
              fontSize: 15,
              fontWeight: FontWeight.w500,
            ),
          ),
          const SizedBox(height: 16),

          TextField(
            controller: _passwordController,
            obscureText: !widget.isPasswordVisible,
            autofillHints: const [AutofillHints.password],
            textInputAction: TextInputAction.done,
            onSubmitted: (_) => _handleLogin(),
            decoration: InputDecoration(
              labelText: context.l10n.password,
              hintText: '••••••••',
              filled: true,
              fillColor: isDarkMode
                  ? AppTheme.surfaceDark.withValues(alpha: 0.6)
                  : Colors.white,
              labelStyle: TextStyle(
                color: fira.mutedText,
                fontSize: 14,
                fontWeight: FontWeight.w500,
              ),
              floatingLabelStyle: TextStyle(
                color: fira.brandPrimary,
                fontSize: 14,
                fontWeight: FontWeight.w600,
              ),
              hintStyle: TextStyle(
                color: fira.mutedText.withValues(alpha: 0.5),
                fontSize: 15,
              ),
              enabledBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(16),
                borderSide: const BorderSide(
                  color: Color(0xFFE9D5FF),
                  width: 1,
                ),
              ),
              focusedBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(16),
                borderSide: BorderSide(
                  color: fira.brandPrimary,
                  width: 1.5,
                ),
              ),
              contentPadding: const EdgeInsets.symmetric(
                horizontal: 16,
                vertical: 16,
              ),
              suffixIcon: IconButton(
                icon: Icon(
                  widget.isPasswordVisible
                      ? Icons.visibility_off_outlined
                      : Icons.visibility_outlined,
                  color: fira.mutedText,
                  size: 20,
                ),
                onPressed: widget.onPasswordVisibilityToggle,
                tooltip: widget.isPasswordVisible ? context.l10n.hide : context.l10n.show,
              ),
            ),
            style: TextStyle(
              color: fira.onSurface,
              fontSize: 15,
              fontWeight: FontWeight.w500,
            ),
          ),
          const SizedBox(height: 8),
          // Forgot password link
          Align(
            alignment: Alignment.centerRight,
            child: TextButton(
              onPressed: () {
                Navigator.of(context).push(
                  MaterialPageRoute(
                    builder: (context) => const ForgotPasswordScreen(),
                  ),
                );
              },
              style: TextButton.styleFrom(
                padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 8),
                minimumSize: Size.zero,
                tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                foregroundColor: fira.brandPrimary,
              ),
              child: Text(
                context.l10n.forgotPassword,
                style: TextStyle(
                  color: fira.brandPrimary,
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
          ),
          const SizedBox(height: 20),

          SizedBox(
            width: double.infinity,
            height: 52,
            child: ElevatedButton(
              onPressed: authProvider.isLoading ? null : _handleLogin,
              style: ElevatedButton.styleFrom(
                backgroundColor: fira.brandPrimary,
                foregroundColor: Colors.white,
                disabledBackgroundColor: fira.brandPrimary.withValues(alpha: 0.6),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(16),
                ),
                elevation: 0,
              ),
              child: authProvider.isLoading
                  ? const SizedBox(
                      height: 22,
                      width: 22,
                      child: CircularProgressIndicator(
                        color: Colors.white,
                        strokeWidth: 2,
                      ),
                    )
                  : Text(
                      context.l10n.login,
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w700,
                        letterSpacing: 0.2,
                      ),
                    ),
            ),
          ),
          const SizedBox(height: 20),

          // Divider: --- or ---
          Row(
            children: [
              const Expanded(
                child: Divider(color: Color(0xFFE9D5FF), height: 1),
              ),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                child: Text(
                  context.l10n.orDivider,
                  style: TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w500,
                    color: fira.mutedText,
                  ),
                ),
              ),
              const Expanded(
                child: Divider(color: Color(0xFFE9D5FF), height: 1),
              ),
            ],
          ),
          const SizedBox(height: 16),

          // Secondary full-width button: Phone sign-in
          SizedBox(
            width: double.infinity,
            height: 52,
            child: OutlinedButton.icon(
              icon: Icon(
                Icons.phone_android_rounded,
                size: 20,
                color: fira.onSurface,
              ),
              label: Text(
                context.l10n.useMyPhoneInstead,
                style: TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.w600,
                  color: fira.onSurface,
                ),
              ),
              style: OutlinedButton.styleFrom(
                backgroundColor: isDarkMode ? AppTheme.surfaceDark : Colors.white,
                side: const BorderSide(color: Color(0xFFE9D5FF), width: 1),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(16),
                ),
              ),
              onPressed: () => Navigator.of(context).pushReplacement(
                MaterialPageRoute(builder: (_) => const PhoneSignInScreen()),
              ),
            ),
          ),
          const SizedBox(height: 12),

          // Switch tab to Sign Up
          Center(
            child: TextButton(
              onPressed: () => widget.tabController.animateTo(1),
              style: TextButton.styleFrom(
                foregroundColor: fira.brandPrimary,
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              ),
              child: Text(
                context.l10n.newToFiraCreateAccount,
                style: TextStyle(
                  color: fira.brandPrimary,
                  fontSize: 14,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
          ),
          const SizedBox(height: 4),

          // Explore as guest
          Center(
            child: TextButton(
              onPressed: () => exploreAsGuest(context),
              style: TextButton.styleFrom(
                foregroundColor: fira.mutedText,
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              ),
              child: Text(
                context.l10n.exploreAsGuest,
                style: TextStyle(
                  color: fira.mutedText,
                  fontSize: 14,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ),
          ),
          SizedBox(height: MediaQuery.of(context).padding.bottom + 16),
        ],
      ),
    );
  }
}

class _SignUpForm extends StatefulWidget {
  const _SignUpForm({
    required this.tabController,
    required this.isPasswordVisible,
    required this.onPasswordVisibilityToggle,
  });

  final TabController tabController;
  final bool isPasswordVisible;
  final VoidCallback onPasswordVisibilityToggle;

  @override
  State<_SignUpForm> createState() => _SignUpFormState();
}

class _SignUpFormState extends State<_SignUpForm> {
  final TextEditingController _firstNameController = TextEditingController();
  final TextEditingController _lastNameController = TextEditingController();
  final TextEditingController _emailController = TextEditingController();
  final TextEditingController _phoneController = TextEditingController();
  final TextEditingController _passwordController = TextEditingController();
  final TextEditingController _confirmPasswordController =
      TextEditingController();

  @override
  void dispose() {
    _firstNameController.dispose();
    _lastNameController.dispose();
    _emailController.dispose();
    _phoneController.dispose();
    _passwordController.dispose();
    _confirmPasswordController.dispose();
    super.dispose();
  }

  Future<void> _handleSignUp() async {
    final authProvider = Provider.of<AuthProvider>(context, listen: false);

    final firstName = _firstNameController.text.trim();
    final lastName = _lastNameController.text.trim();
    final email = _emailController.text.trim();
    final phone = _phoneController.text.trim();
    final password = _passwordController.text;
    final confirmPassword = _confirmPasswordController.text;

    // Basic validation
    if (firstName.isEmpty ||
        lastName.isEmpty ||
        email.isEmpty ||
        phone.isEmpty ||
        password.isEmpty ||
        confirmPassword.isEmpty) {
      toastification.show(
        context: context,
        type: ToastificationType.warning,
        title: const Text('Invalid Input'),
        description: const Text('Please fill all required fields, including your phone number'),
        autoCloseDuration: const Duration(seconds: 3),
      );
      return;
    }

    // Ethiopian phone number validation regex
    final phoneClean = phone.replaceAll(RegExp(r'[\s\-\(\)]'), '');
    final ethPhoneRegex = RegExp(r'^(?:\+251|251|0)?([79]\d{8})$');
    if (!ethPhoneRegex.hasMatch(phoneClean)) {
      toastification.show(
        context: context,
        type: ToastificationType.warning,
        title: const Text('Invalid Phone Number'),
        description: const Text('Please enter a valid Ethiopian mobile number (e.g. 0911234567 or 0711234567)'),
        autoCloseDuration: const Duration(seconds: 4),
      );
      return;
    }

    if (password != confirmPassword) {
      toastification.show(
        context: context,
        type: ToastificationType.warning,
        title: const Text('Password Mismatch'),
        description: const Text('Passwords do not match'),
        autoCloseDuration: const Duration(seconds: 3),
      );
      return;
    }

    final userData = {
      'first_name': firstName,
      'last_name': lastName,
      'email': email,
      'phone': phoneClean,
      'password': password,
      'password_confirmation': confirmPassword,
      'terms': true, // Assuming terms are accepted by signing up
    };

    final result = await authProvider.register(userData);

    if (!mounted) return;

    if (result['success']) {
      if (result['requires_phone_verification'] == true) {
        toastification.show(
          context: context,
          type: ToastificationType.info,
          title: const Text('Verification Required'),
          description: Text(result['message'] ?? 'SMS OTP code dispatched to your phone'),
          autoCloseDuration: const Duration(seconds: 4),
        );

        Navigator.of(context).push(
          MaterialPageRoute(
            builder: (_) => OtpVerificationScreen(
              phone: result['phone'] ?? phoneClean,
              phoneMasked: result['phone_masked'],
              email: email,
              isRegistration: true,
            ),
          ),
        );
      } else {
        toastification.show(
          context: context,
          type: ToastificationType.success,
          title: const Text('Registration Successful'),
          description: Text(result['message'] ?? 'Account created successfully'),
          autoCloseDuration: const Duration(seconds: 3),
        );

        if (SignInResume.tryResume(context)) return;
        if (Navigator.of(context).canPop()) {
          Navigator.of(context).pop();
        } else {
          Navigator.of(context).pushReplacement(
            MaterialPageRoute(builder: (_) => AppConfig.getHomePage()),
          );
        }
      }
    } else {
      String errorMessage = result['message'] ?? 'Registration failed';
      if (result['errors'] != null && result['errors'] is Map) {
        final Map errors = result['errors'];
        if (errors.isNotEmpty) {
          final firstVal = errors.values.first;
          if (firstVal is List && firstVal.isNotEmpty) {
            errorMessage = firstVal.first.toString();
          } else if (firstVal is String) {
            errorMessage = firstVal;
          }
        }
      }

      toastification.show(
        context: context,
        type: ToastificationType.error,
        title: const Text('Registration Failed'),
        description: Text(errorMessage),
        autoCloseDuration: const Duration(seconds: 4),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final authProvider = Provider.of<AuthProvider>(context);
    final fira = context.fira;
    final bool isDarkMode = Theme.of(context).brightness == Brightness.dark;

    return SingleChildScrollView(
      padding: const EdgeInsets.fromLTRB(24.0, 8.0, 24.0, 0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          // First Name & Last Name in a clean side-by-side row
          Row(
            children: <Widget>[
              Expanded(
                child: TextField(
                  controller: _firstNameController,
                  textInputAction: TextInputAction.next,
                  decoration: InputDecoration(
                    labelText: context.l10n.firstName,
                    hintText: 'John',
                    filled: true,
                    fillColor: isDarkMode
                        ? AppTheme.surfaceDark.withValues(alpha: 0.6)
                        : Colors.white,
                    labelStyle: TextStyle(
                      color: fira.mutedText,
                      fontSize: 14,
                      fontWeight: FontWeight.w500,
                    ),
                    floatingLabelStyle: TextStyle(
                      color: fira.brandPrimary,
                      fontSize: 14,
                      fontWeight: FontWeight.w600,
                    ),
                    hintStyle: TextStyle(
                      color: fira.mutedText.withValues(alpha: 0.5),
                      fontSize: 15,
                    ),
                    enabledBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(16),
                      borderSide: const BorderSide(
                        color: Color(0xFFE9D5FF),
                        width: 1,
                      ),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(16),
                      borderSide: BorderSide(
                        color: fira.brandPrimary,
                        width: 1.5,
                      ),
                    ),
                    contentPadding: const EdgeInsets.symmetric(
                      horizontal: 14,
                      vertical: 14,
                    ),
                  ),
                  style: TextStyle(
                    color: fira.onSurface,
                    fontSize: 15,
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: TextField(
                  controller: _lastNameController,
                  textInputAction: TextInputAction.next,
                  decoration: InputDecoration(
                    labelText: context.l10n.lastName,
                    hintText: 'Doe',
                    filled: true,
                    fillColor: isDarkMode
                        ? AppTheme.surfaceDark.withValues(alpha: 0.6)
                        : Colors.white,
                    labelStyle: TextStyle(
                      color: fira.mutedText,
                      fontSize: 14,
                      fontWeight: FontWeight.w500,
                    ),
                    floatingLabelStyle: TextStyle(
                      color: fira.brandPrimary,
                      fontSize: 14,
                      fontWeight: FontWeight.w600,
                    ),
                    hintStyle: TextStyle(
                      color: fira.mutedText.withValues(alpha: 0.5),
                      fontSize: 15,
                    ),
                    enabledBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(16),
                      borderSide: const BorderSide(
                        color: Color(0xFFE9D5FF),
                        width: 1,
                      ),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(16),
                      borderSide: BorderSide(
                        color: fira.brandPrimary,
                        width: 1.5,
                      ),
                    ),
                    contentPadding: const EdgeInsets.symmetric(
                      horizontal: 14,
                      vertical: 14,
                    ),
                  ),
                  style: TextStyle(
                    color: fira.onSurface,
                    fontSize: 15,
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),

          // Mandatory phone number with +251 badge
          TextField(
            controller: _phoneController,
            keyboardType: TextInputType.phone,
            textInputAction: TextInputAction.next,
            decoration: InputDecoration(
              labelText: 'Phone Number (Required for SMS OTP) *',
              hintText: '0911234567 or 0711234567',
              helperText: 'SMS verification code will be sent to this phone',
              helperMaxLines: 1,
              prefixIcon: Padding(
                padding: const EdgeInsets.only(left: 12, right: 8),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(
                      Icons.phone_android_rounded,
                      size: 20,
                      color: fira.brandPrimary,
                    ),
                    const SizedBox(width: 6),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                      decoration: BoxDecoration(
                        color: fira.brandTint,
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Text(
                        '+251',
                        style: TextStyle(
                          color: fira.brandPrimary,
                          fontWeight: FontWeight.bold,
                          fontSize: 13,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              filled: true,
              fillColor: isDarkMode
                  ? AppTheme.surfaceDark.withValues(alpha: 0.6)
                  : Colors.white,
              labelStyle: TextStyle(
                color: fira.mutedText,
                fontSize: 14,
                fontWeight: FontWeight.w500,
              ),
              floatingLabelStyle: TextStyle(
                color: fira.brandPrimary,
                fontSize: 14,
                fontWeight: FontWeight.w600,
              ),
              hintStyle: TextStyle(
                color: fira.mutedText.withValues(alpha: 0.5),
                fontSize: 15,
              ),
              helperStyle: TextStyle(
                color: fira.mutedText,
                fontSize: 12,
              ),
              enabledBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(16),
                borderSide: const BorderSide(
                  color: Color(0xFFE9D5FF),
                  width: 1,
                ),
              ),
              focusedBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(16),
                borderSide: BorderSide(
                  color: fira.brandPrimary,
                  width: 1.5,
                ),
              ),
              contentPadding: const EdgeInsets.symmetric(
                horizontal: 16,
                vertical: 14,
              ),
            ),
            style: TextStyle(
              color: fira.onSurface,
              fontSize: 15,
              fontWeight: FontWeight.w500,
            ),
          ),
          const SizedBox(height: 16),

          // Email Field
          TextField(
            controller: _emailController,
            keyboardType: TextInputType.emailAddress,
            autofillHints: const [AutofillHints.email],
            textInputAction: TextInputAction.next,
            decoration: InputDecoration(
              labelText: context.l10n.email,
              hintText: 'john@mail.com',
              filled: true,
              fillColor: isDarkMode
                  ? AppTheme.surfaceDark.withValues(alpha: 0.6)
                  : Colors.white,
              labelStyle: TextStyle(
                color: fira.mutedText,
                fontSize: 14,
                fontWeight: FontWeight.w500,
              ),
              floatingLabelStyle: TextStyle(
                color: fira.brandPrimary,
                fontSize: 14,
                fontWeight: FontWeight.w600,
              ),
              hintStyle: TextStyle(
                color: fira.mutedText.withValues(alpha: 0.5),
                fontSize: 15,
              ),
              enabledBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(16),
                borderSide: const BorderSide(
                  color: Color(0xFFE9D5FF),
                  width: 1,
                ),
              ),
              focusedBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(16),
                borderSide: BorderSide(
                  color: fira.brandPrimary,
                  width: 1.5,
                ),
              ),
              contentPadding: const EdgeInsets.symmetric(
                horizontal: 16,
                vertical: 14,
              ),
            ),
            style: TextStyle(
              color: fira.onSurface,
              fontSize: 15,
              fontWeight: FontWeight.w500,
            ),
          ),
          const SizedBox(height: 16),

          TextField(
            controller: _passwordController,
            obscureText: !widget.isPasswordVisible,
            autofillHints: const [AutofillHints.newPassword],
            textInputAction: TextInputAction.next,
            decoration: InputDecoration(
              labelText: context.l10n.password,
              hintText: '••••••••',
              filled: true,
              fillColor: isDarkMode
                  ? AppTheme.surfaceDark.withValues(alpha: 0.6)
                  : Colors.white,
              labelStyle: TextStyle(
                color: fira.mutedText,
                fontSize: 14,
                fontWeight: FontWeight.w500,
              ),
              floatingLabelStyle: TextStyle(
                color: fira.brandPrimary,
                fontSize: 14,
                fontWeight: FontWeight.w600,
              ),
              hintStyle: TextStyle(
                color: fira.mutedText.withValues(alpha: 0.5),
                fontSize: 15,
              ),
              enabledBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(16),
                borderSide: const BorderSide(
                  color: Color(0xFFE9D5FF),
                  width: 1,
                ),
              ),
              focusedBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(16),
                borderSide: BorderSide(
                  color: fira.brandPrimary,
                  width: 1.5,
                ),
              ),
              contentPadding: const EdgeInsets.symmetric(
                horizontal: 16,
                vertical: 16,
              ),
              suffixIcon: IconButton(
                icon: Icon(
                  widget.isPasswordVisible
                      ? Icons.visibility_off_outlined
                      : Icons.visibility_outlined,
                  color: fira.mutedText,
                  size: 20,
                ),
                onPressed: widget.onPasswordVisibilityToggle,
                tooltip: widget.isPasswordVisible ? context.l10n.hide : context.l10n.show,
              ),
            ),
            style: TextStyle(
              color: fira.onSurface,
              fontSize: 15,
              fontWeight: FontWeight.w500,
            ),
          ),
          const SizedBox(height: 16),

          TextField(
            controller: _confirmPasswordController,
            obscureText: !widget.isPasswordVisible,
            autofillHints: const [AutofillHints.newPassword],
            textInputAction: TextInputAction.done,
            onSubmitted: (_) => _handleSignUp(),
            decoration: InputDecoration(
              labelText: context.l10n.confirmPassword,
              hintText: '••••••••',
              filled: true,
              fillColor: isDarkMode
                  ? AppTheme.surfaceDark.withValues(alpha: 0.6)
                  : Colors.white,
              labelStyle: TextStyle(
                color: fira.mutedText,
                fontSize: 14,
                fontWeight: FontWeight.w500,
              ),
              floatingLabelStyle: TextStyle(
                color: fira.brandPrimary,
                fontSize: 14,
                fontWeight: FontWeight.w600,
              ),
              hintStyle: TextStyle(
                color: fira.mutedText.withValues(alpha: 0.5),
                fontSize: 15,
              ),
              enabledBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(16),
                borderSide: const BorderSide(
                  color: Color(0xFFE9D5FF),
                  width: 1,
                ),
              ),
              focusedBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(16),
                borderSide: BorderSide(
                  color: fira.brandPrimary,
                  width: 1.5,
                ),
              ),
              contentPadding: const EdgeInsets.symmetric(
                horizontal: 16,
                vertical: 16,
              ),
              suffixIcon: IconButton(
                icon: Icon(
                  widget.isPasswordVisible
                      ? Icons.visibility_off_outlined
                      : Icons.visibility_outlined,
                  color: fira.mutedText,
                  size: 20,
                ),
                onPressed: widget.onPasswordVisibilityToggle,
                tooltip: widget.isPasswordVisible ? context.l10n.hide : context.l10n.show,
              ),
            ),
            style: TextStyle(
              color: fira.onSurface,
              fontSize: 15,
              fontWeight: FontWeight.w500,
            ),
          ),
          const SizedBox(height: 24),

          SizedBox(
            width: double.infinity,
            height: 52,
            child: ElevatedButton(
              onPressed: authProvider.isLoading ? null : _handleSignUp,
              style: ElevatedButton.styleFrom(
                backgroundColor: fira.brandPrimary,
                foregroundColor: Colors.white,
                disabledBackgroundColor: fira.brandPrimary.withValues(alpha: 0.6),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(16),
                ),
                elevation: 0,
              ),
              child: authProvider.isLoading
                  ? const SizedBox(
                      height: 22,
                      width: 22,
                      child: CircularProgressIndicator(
                        color: Colors.white,
                        strokeWidth: 2,
                      ),
                    )
                  : Text(
                      context.l10n.signup,
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w700,
                        letterSpacing: 0.2,
                      ),
                    ),
            ),
          ),
          const SizedBox(height: 20),

          // Divider: --- or ---
          Row(
            children: [
              const Expanded(
                child: Divider(color: Color(0xFFE9D5FF), height: 1),
              ),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                child: Text(
                  context.l10n.orDivider,
                  style: TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w500,
                    color: fira.mutedText,
                  ),
                ),
              ),
              const Expanded(
                child: Divider(color: Color(0xFFE9D5FF), height: 1),
              ),
            ],
          ),
          const SizedBox(height: 16),

          // Switch back to Login
          Center(
            child: TextButton(
              onPressed: () => widget.tabController.animateTo(0),
              style: TextButton.styleFrom(
                foregroundColor: fira.brandPrimary,
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              ),
              child: Text(
                context.l10n.backToSignIn,
                style: TextStyle(
                  color: fira.brandPrimary,
                  fontSize: 14,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
          ),
          const SizedBox(height: 4),

          // International email sign-up (no Ethiopian phone)
          Center(
            child: TextButton(
              onPressed: () => Navigator.of(context).push(
                MaterialPageRoute(builder: (_) => const EmailSignUpScreen()),
              ),
              style: TextButton.styleFrom(
                foregroundColor: fira.mutedText,
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
              ),
              child: Text(
                context.l10n.createAccountWithEmail,
                style: TextStyle(
                  color: fira.mutedText,
                  fontSize: 13,
                  fontWeight: FontWeight.w500,
                ),
                textAlign: TextAlign.center,
              ),
            ),
          ),
          const SizedBox(height: 4),

          // Explore as guest
          Center(
            child: TextButton(
              onPressed: () => exploreAsGuest(context),
              style: TextButton.styleFrom(
                foregroundColor: fira.mutedText,
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              ),
              child: Text(
                context.l10n.exploreAsGuest,
                style: TextStyle(
                  color: fira.mutedText,
                  fontSize: 14,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ),
          ),
          SizedBox(height: MediaQuery.of(context).padding.bottom + 16),
        ],
      ),
    );
  }
}

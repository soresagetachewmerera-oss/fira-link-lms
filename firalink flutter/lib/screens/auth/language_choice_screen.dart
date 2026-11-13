import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/settings_provider.dart';
import '../onboarding/onboarding_screen.dart';

class LanguageChoiceScreen extends StatefulWidget {
  const LanguageChoiceScreen({super.key});

  @override
  State<LanguageChoiceScreen> createState() => _LanguageChoiceScreenState();
}

class _LanguageChoiceScreenState extends State<LanguageChoiceScreen> {
  String _selectedLang = 'en';

  @override
  Widget build(BuildContext context) {
    const primaryColor = Color(0xFF6D1A95);

    return Scaffold(
      backgroundColor: Colors.white,
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 24.0, vertical: 20.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const SizedBox(height: 32),
              Center(
                child: Container(
                  width: 80,
                  height: 80,
                  decoration: BoxDecoration(
                    color: primaryColor.withValues(alpha: 0.1),
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(
                    Icons.language,
                    color: primaryColor,
                    size: 40,
                  ),
                ),
              ),
              const SizedBox(height: 24),
              const Text(
                'Choose Your Language\nቋንቋ ይምረጡ',
                textAlign: TextAlign.center,
                style: TextStyle(
                  fontSize: 22,
                  fontWeight: FontWeight.bold,
                  color: Color(0xFF1E293B),
                  height: 1.3,
                ),
              ),
              const SizedBox(height: 8),
              const Text(
                'You can change this later in settings\nበማንኛውም ጊዜ በቅንብሮች መቀየር ይችላሉ',
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 13, color: Colors.black54),
              ),
              const SizedBox(height: 40),

              // English Option
              _buildLanguageCard(
                code: 'en',
                title: 'English',
                subtitle: 'International Trade Standard',
                nativeTitle: 'English (US/UK)',
                primaryColor: primaryColor,
              ),
              const SizedBox(height: 16),

              // Amharic Option
              _buildLanguageCard(
                code: 'am',
                title: 'አማርኛ',
                subtitle: 'የኢትዮጵያ ንግድ እና ግንኙነት',
                nativeTitle: 'Amharic (Ethiopia)',
                primaryColor: primaryColor,
              ),

              const Spacer(),

              ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: primaryColor,
                  foregroundColor: Colors.white,
                  minimumSize: const Size.fromHeight(52),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(16),
                  ),
                  elevation: 0,
                ),
                onPressed: () {
                  final settings = Provider.of<SettingsProvider>(context, listen: false);
                  settings.setLocale(Locale(_selectedLang));
                  Navigator.pushReplacement(
                    context,
                    MaterialPageRoute(builder: (_) => const OnboardingScreen()),
                  );
                },
                child: const Text(
                  'Continue / ቀጥል',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                ),
              ),
              const SizedBox(height: 16),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildLanguageCard({
    required String code,
    required String title,
    required String subtitle,
    required String nativeTitle,
    required Color primaryColor,
  }) {
    final isSelected = _selectedLang == code;

    return InkWell(
      onTap: () => setState(() => _selectedLang = code),
      borderRadius: BorderRadius.circular(18),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 18),
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
                    blurRadius: 12,
                    offset: const Offset(0, 4),
                  )
                ]
              : [],
        ),
        child: Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: isSelected ? primaryColor : const Color(0xFF1E293B),
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    subtitle,
                    style: TextStyle(
                      fontSize: 12,
                      color: Colors.grey.shade600,
                    ),
                  ),
                  Text(
                    nativeTitle,
                    style: TextStyle(
                      fontSize: 11,
                      color: isSelected ? primaryColor : Colors.grey.shade400,
                    ),
                  ),
                ],
              ),
            ),
            Container(
              width: 24,
              height: 24,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                border: Border.all(
                  color: isSelected ? primaryColor : Colors.grey.shade400,
                  width: 2,
                ),
                color: isSelected ? primaryColor : Colors.transparent,
              ),
              child: isSelected
                  ? const Icon(Icons.check, size: 16, color: Colors.white)
                  : null,
            ),
          ],
        ),
      ),
    );
  }
}


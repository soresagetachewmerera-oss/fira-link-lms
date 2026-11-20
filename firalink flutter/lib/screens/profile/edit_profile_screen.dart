import 'dart:typed_data';
import 'package:flutter/material.dart';
import 'package:hugeicons/hugeicons.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';

import '../../widgets/fira_error_state.dart';
import '../../services/api_exception.dart';
import '../../providers/auth_provider.dart';
import '../../services/profile_service.dart';
import '../../theme/app_theme.dart';
import '../../l10n/app_localizations.dart';

class EditProfileScreen extends StatefulWidget {
  const EditProfileScreen({super.key});

  @override
  State<EditProfileScreen> createState() => _EditProfileScreenState();
}

class _EditProfileScreenState extends State<EditProfileScreen> {
  final _formKey = GlobalKey<FormState>();
  final _profileService = ProfileService();
  final _picker = ImagePicker();

  late TextEditingController _firstNameController;
  late TextEditingController _lastNameController;
  late TextEditingController _emailController;
  late TextEditingController _phoneController;
  late bool _phoneLocked;
  late TextEditingController _bioController;

  Uint8List? _localPhotoBytes;
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    final user = Provider.of<AuthProvider>(context, listen: false).user;
    final nameParts = user?.name.split(' ') ?? [''];
    String firstName = nameParts.first;
    String lastName = nameParts.length > 1
        ? nameParts.sublist(1).join(' ')
        : '';

    _firstNameController = TextEditingController(text: firstName);
    _lastNameController = TextEditingController(text: lastName);
    _emailController = TextEditingController(text: user?.email ?? '');
    _phoneController = TextEditingController(text: user?.phone ?? '');
    _phoneLocked = (user?.phone ?? '').isNotEmpty;
    _bioController = TextEditingController(text: user?.bio ?? '');
  }

  Future<void> _pickImage() async {
    final messenger = ScaffoldMessenger.of(context);
    final textColor = AppTheme.getTextColor(context);
    final cardColor = AppTheme.getCardColor(context);
    final authProvider = Provider.of<AuthProvider>(context, listen: false);

    try {
      final XFile? image = await _picker.pickImage(
        source: ImageSource.gallery,
        maxWidth: 1600,
        maxHeight: 1600,
        imageQuality: 85,
      );
      if (image != null) {
        final bytes = await image.readAsBytes();
        setState(() {
          _localPhotoBytes = bytes;
          _isLoading = true;
        });

        // Upload photo via bytes across Web and Mobile
        await _profileService.uploadProfilePhotoBytes(
          bytes: bytes,
          filename: image.name,
        );

        // Refresh user data
        await authProvider.loadUser();

        if (mounted) {
          messenger.showSnackBar(
            SnackBar(
              content: Text(
                context.l10n.profilePhotoSaved,
                style: TextStyle(color: textColor),
              ),
              backgroundColor: cardColor,
              behavior: SnackBarBehavior.floating,
            ),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        messenger.showSnackBar(
          SnackBar(content: Text(FiraErrorText.message(context, e, isAction: true))),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  Future<void> _saveProfile() async {
    if (_formKey.currentState!.validate()) {
      setState(() => _isLoading = true);
      final messenger = ScaffoldMessenger.of(context);
      final navigator = Navigator.of(context);
      final textColor = AppTheme.getTextColor(context);
      final cardColor = AppTheme.getCardColor(context);
      final authProvider = Provider.of<AuthProvider>(context, listen: false);
      final l10n = context.l10n;

      try {
        final fullName =
            '${_firstNameController.text.trim()} ${_lastNameController.text.trim()}';

        await _profileService.updateProfile(
          name: fullName,
          phone: _phoneLocked ? null : _phoneController.text.trim(),
          bio: _bioController.text.trim(),
        );

        // Refresh user data in provider
        await authProvider.loadUser();

        if (mounted) {
          messenger.showSnackBar(
            SnackBar(
              content: Text(
                l10n.profileSaved,
                style: TextStyle(color: textColor),
              ),
              backgroundColor: cardColor,
              behavior: SnackBarBehavior.floating,
            ),
          );
          navigator.pop();
        }
      } catch (e) {
        if (mounted) {
          final message = e is ApiException && e.code == 'PHONE_CHANGE_NEEDS_CODE'
              ? l10n.phoneLockedHint
              : FiraErrorText.message(context, e, isAction: true);
          messenger.showSnackBar(SnackBar(content: Text(message)));
        }
      } finally {
        if (mounted) setState(() => _isLoading = false);
      }
    }
  }

  @override
  void dispose() {
    _firstNameController.dispose();
    _lastNameController.dispose();
    _emailController.dispose();
    _phoneController.dispose();
    _bioController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.getBackgroundColor(context),
      body: SafeArea(
        bottom: false,
        child: Column(
          children: <Widget>[
            // Header
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 8, 20, 16),
              child: Row(
                children: <Widget>[
                  IconButton(
                      tooltip: MaterialLocalizations.of(context).backButtonTooltip,
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
                      'Edit Profile',
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        color: AppTheme.getTextColor(context),
                        fontSize: 20,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ),
                  const SizedBox(width: 40),
                ],
              ),
            ),
            // Content
            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.symmetric(horizontal: 20),
                child: Form(
                  key: _formKey,
                  child: Column(
                    children: <Widget>[
                      const SizedBox(height: 16),
                      // Profile Picture
                      GestureDetector(
                        onTap: _pickImage,
                        child: Stack(
                          children: <Widget>[
                            Container(
                              width: 120,
                              height: 120,
                              decoration: BoxDecoration(
                                shape: BoxShape.circle,
                                border: Border.all(
                                  color: AppTheme.getTextColor(
                                    context,
                                  ).withValues(alpha: 0.2),
                                  width: 3,
                                ),
                              ),
                              child: ClipOval(child: _getProfileImage(context)),
                            ),
                            Positioned(
                              bottom: 0,
                              right: 0,
                              child: Container(
                                width: 36,
                                height: 36,
                                decoration: BoxDecoration(
                                  color: AppTheme.getPrimaryColor(context),
                                  shape: BoxShape.circle,
                                  border: Border.all(
                                    color: AppTheme.getCardColor(context),
                                    width: 3,
                                  ),
                                ),
                                child: Icon(
                                  Icons.camera_alt,
                                  size: 18,
                                  color: Colors.white,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 32),
                      // First Name
                      TextFormField(
                        controller: _firstNameController,
                        decoration: _inputDecoration(
                          context,
                          label: context.l10n.firstName,
                          hint: 'Enter your first name',
                        ),
                        validator: (value) {
                          if (value == null || value.isEmpty) {
                            return 'Please enter your first name';
                          }
                          return null;
                        },
                      ),
                      const SizedBox(height: 20),
                      // Last Name
                      TextFormField(
                        controller: _lastNameController,
                        decoration: _inputDecoration(
                          context,
                          label: context.l10n.lastName,
                          hint: 'Enter your last name',
                        ),
                        validator: (value) {
                          if (value == null || value.isEmpty) {
                            return 'Please enter your last name';
                          }
                          return null;
                        },
                      ),
                      const SizedBox(height: 20),
                      // Email
                      // Read-only: a sign-in channel, changed only with a code (and phone-only
                      // accounts have no email, so it is never required here).
                      TextFormField(
                        key: const Key('edit-profile-email'),
                        controller: _emailController,
                        readOnly: true,
                        keyboardType: TextInputType.emailAddress,
                        decoration: _inputDecoration(
                          context,
                          label: context.l10n.email,
                          hint: context.l10n.email,
                        ).copyWith(helperText: context.l10n.emailLockedHint, helperMaxLines: 3),
                      ),
                      const SizedBox(height: 20),
                      // Phone
                      // A first number can be added here; changing a number needs a code.
                      TextFormField(
                        key: const Key('edit-profile-phone'),
                        controller: _phoneController,
                        readOnly: _phoneLocked,
                        keyboardType: TextInputType.phone,
                        decoration: _inputDecoration(
                          context,
                          label: context.l10n.phoneNumber,
                          hint: context.l10n.phoneNumber,
                        ).copyWith(helperText: _phoneLocked ? context.l10n.phoneLockedHint : null, helperMaxLines: 3),
                      ),
                      const SizedBox(height: 20),
                      // Bio
                      TextFormField(
                        controller: _bioController,
                        maxLines: 4,
                        decoration: _inputDecoration(
                          context,
                          label: 'Bio',
                          hint: 'Tell us about yourself',
                        ),
                      ),
                      const SizedBox(height: 32),
                      // Save Button
                      SizedBox(
                        width: double.infinity,
                        child: ElevatedButton(
                          onPressed: _isLoading ? null : _saveProfile,
                          style: ElevatedButton.styleFrom(
                            backgroundColor: AppTheme.getPrimaryColor(context),
                            foregroundColor: Colors.white,
                            padding: const EdgeInsets.symmetric(vertical: 16),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(16),
                            ),
                            elevation: 0,
                          ),
                          child: _isLoading
                              ? SizedBox(
                                  height: 20,
                                  width: 20,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2,
                                    color: Colors.white,
                                  ),
                                )
                              : Text(
                                  'Save Changes',
                                  style: TextStyle(
                                    fontSize: 16,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                        ),
                      ),
                      const SizedBox(height: 40),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _getProfileImage(BuildContext context) {
    if (_localPhotoBytes != null) {
      return Image.memory(
        _localPhotoBytes!,
        fit: BoxFit.cover,
      );
    }
    final user = Provider.of<AuthProvider>(context).user;
    if (user?.profilePhotoUrl != null && user!.profilePhotoUrl!.isNotEmpty) {
      return Image.network(
        user.profilePhotoUrl!,
        key: ValueKey(user.profilePhoto),
        fit: BoxFit.cover,
        errorBuilder: (context, error, stackTrace) {
          return Image.asset(
            'assets/img/default-profile.png',
            fit: BoxFit.cover,
          );
        },
      );
    }
    return Image.asset('assets/img/default-profile.png', fit: BoxFit.cover);
  }

  InputDecoration _inputDecoration(
    BuildContext context, {
    required String label,
    required String hint,
  }) {
    return InputDecoration(
      labelText: label,
      hintText: hint,
      filled: true,
      fillColor: Theme.of(context).brightness == Brightness.dark
          ? AppTheme.surfaceDark.withValues(alpha: 0.5)
          : Colors.grey[50],
      labelStyle: TextStyle(
        color: AppTheme.getTextColor(context),
        fontSize: 14,
        fontWeight: FontWeight.w500,
      ),
      hintStyle: TextStyle(
        color: AppTheme.getTextColor(context).withValues(alpha: 0.5),
        fontSize: 16,
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(
          color: AppTheme.getTextColor(context).withValues(alpha: 0.2),
          width: 1,
        ),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: AppTheme.getTextColor(context), width: 2),
      ),
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
    );
  }
}

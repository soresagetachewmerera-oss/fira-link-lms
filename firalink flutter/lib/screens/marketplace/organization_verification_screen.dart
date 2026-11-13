import 'dart:convert';
import 'dart:io';
import 'package:file_picker/file_picker.dart';
import 'package:image_picker/image_picker.dart';
import 'package:flutter/material.dart';
import '../../services/marketplace_service.dart';

class OrganizationVerificationScreen extends StatefulWidget {
  final String? initialRole;

  const OrganizationVerificationScreen({super.key, this.initialRole});

  @override
  State<OrganizationVerificationScreen> createState() => _OrganizationVerificationScreenState();
}

class _OrganizationVerificationScreenState extends State<OrganizationVerificationScreen> {
  final MarketplaceService _marketplaceService = MarketplaceService();
  bool _isLoading = true;
  String _selectedRole = 'exporter';
  Map<String, dynamic>? _orgData;
  Map<String, dynamic>? _checklistData;

  final _tinController = TextEditingController();
  final _licenseController = TextEditingController();
  final _authorityController = TextEditingController();
  final _expiryController = TextEditingController();
  bool _isSubmitting = false;

  final Map<String, Map<String, dynamic>> _roleMeta = {
    'exporter': {
      'title': 'Licensed Coffee Exporter',
      'icon': Icons.directions_boat,
      'desc': 'ECTA-licensed export entity authorized to issue international FOB/CIF contracts.',
    },
    'supplier': {
      'title': 'Washing Station / Estate',
      'icon': Icons.agriculture,
      'desc': 'Wet/dry mill operator with certified Ethiopian origin traceability and processing.',
    },
    'buyer': {
      'title': 'International Buyer / Roaster',
      'icon': Icons.coffee,
      'desc': 'Commercial specialty green coffee importer, roaster, or wholesale distributor.',
    },
    'broker': {
      'title': 'Certified Trade Broker / Agent',
      'icon': Icons.handshake,
      'desc': 'Intermediary operating under notarized Letter of Representation (LoR).',
    },
  };

  @override
  void initState() {
    super.initState();
    if (widget.initialRole != null && _roleMeta.containsKey(widget.initialRole)) {
      _selectedRole = widget.initialRole!;
    }
    _loadVerificationStatus();
  }

  @override
  void dispose() {
    _tinController.dispose();
    _licenseController.dispose();
    _authorityController.dispose();
    _expiryController.dispose();
    super.dispose();
  }

  Future<void> _loadVerificationStatus() async {
    setState(() => _isLoading = true);
    final res = await _marketplaceService.getVerificationStatus();
    if (res.statusCode == 200 && mounted) {
      try {
        final decoded = jsonDecode(res.body);
        final data = decoded['data'] ?? {};
        setState(() {
          _orgData = data['organization'];
          _checklistData = data['checklist'];
          _updateFormFieldsForRole(_selectedRole);
          _isLoading = false;
        });
        return;
      } catch (_) {}
    }

    if (mounted) {
      setState(() => _isLoading = false);
    }
  }

  void _updateFormFieldsForRole(String role) {
    final roleData = _checklistData?[role];
    final vData = roleData?['data'];
    if (vData != null) {
      _tinController.text = vData['tin_number'] ?? '';
      _licenseController.text = vData['license_number'] ?? '';
      _authorityController.text = vData['issuing_authority'] ?? (role == 'exporter' ? 'ECTA' : 'Ministry of Trade');
      _expiryController.text = vData['license_expires_at'] ?? '';
    } else {
      _tinController.clear();
      _licenseController.clear();
      _authorityController.text = role == 'exporter' ? 'ECTA' : 'Ministry of Trade';
      _expiryController.clear();
    }
  }

  Future<void> _submitDossier() async {
    if (_tinController.text.trim().isEmpty && _licenseController.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Please enter at least your TIN or License number.'),
          backgroundColor: Colors.redAccent,
        ),
      );
      return;
    }

    setState(() => _isSubmitting = true);
    final payload = {
      'target_role': _selectedRole,
      'tin_number': _tinController.text.trim(),
      'license_number': _licenseController.text.trim(),
      'issuing_authority': _authorityController.text.trim(),
      if (_expiryController.text.trim().isNotEmpty) 'license_expires_at': _expiryController.text.trim(),
    };

    final res = await _marketplaceService.submitVerificationDetails(payload);
    setState(() => _isSubmitting = false);

    if (mounted) {
      if (res.statusCode == 200 || res.statusCode == 201) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('${_roleMeta[_selectedRole]!['title']} dossier submitted for audit.'),
            backgroundColor: Colors.green.shade700,
          ),
        );
        _loadVerificationStatus();
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Submission failed. Please check your inputs.'),
            backgroundColor: Colors.redAccent,
          ),
        );
      }
    }
  }

  void _showDocumentUploadDialog(String docType, String docLabel) {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Upload $docLabel',
                style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 8),
              const Text(
                'Files are vaulted with SHA-256 integrity hash on secure non-public storage. Formats: PDF, JPG, PNG (Max 10MB).',
                style: TextStyle(fontSize: 12, color: Colors.black54),
              ),
              const SizedBox(height: 20),
              ListTile(
                leading: Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: const Color(0xFF6D1A95).withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: const Icon(Icons.camera_alt_outlined, color: Color(0xFF6D1A95)),
                ),
                title: const Text('Capture with Camera', style: TextStyle(fontWeight: FontWeight.w600)),
                subtitle: const Text('Take a photo of physical license or ID', style: TextStyle(fontSize: 12)),
                onTap: () async {
                  Navigator.pop(ctx);
                  final picker = ImagePicker();
                  final picked = await picker.pickImage(source: ImageSource.camera, imageQuality: 85);
                  if (picked != null) {
                    final bytes = await picked.readAsBytes();
                    _performDocumentUpload(docType, docLabel, bytes, picked.name);
                  }
                },
              ),
              const Divider(height: 1),
              ListTile(
                leading: Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: const Color(0xFF6D1A95).withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: const Icon(Icons.file_present_outlined, color: Color(0xFF6D1A95)),
                ),
                title: const Text('Browse Files or PDF', style: TextStyle(fontWeight: FontWeight.w600)),
                subtitle: const Text('Select scanned certificate or official document', style: TextStyle(fontSize: 12)),
                onTap: () async {
                  Navigator.pop(ctx);
                  final result = await FilePicker.platform.pickFiles(
                    type: FileType.custom,
                    allowedExtensions: ['pdf', 'jpg', 'jpeg', 'png'],
                    withData: true,
                  );
                  if (result != null && result.files.isNotEmpty) {
                    final file = result.files.first;
                    final bytes = file.bytes ?? (file.path != null ? await File(file.path!).readAsBytes() : null);
                    if (bytes != null) {
                      _performDocumentUpload(docType, docLabel, bytes, file.name);
                    }
                  }
                },
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _performDocumentUpload(
    String docType,
    String docLabel,
    List<int> fileBytes,
    String fileName,
  ) async {
    setState(() => _isLoading = true);
    try {
      final res = await _marketplaceService.uploadVerificationDocument(
        targetRole: _selectedRole,
        documentType: docType,
        fileBytes: fileBytes,
        filename: fileName,
      );
      if (mounted) {
        if (res.statusCode == 200 || res.statusCode == 201) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('$docLabel securely vaulted and hashed.'),
              backgroundColor: Colors.green.shade700,
            ),
          );
          _loadVerificationStatus();
        } else {
          final decoded = jsonDecode(res.body);
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(decoded['message'] ?? 'Upload failed. Please try again.'),
              backgroundColor: Colors.redAccent,
            ),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Upload error: $e'),
            backgroundColor: Colors.redAccent,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    const brandPurple = Color(0xFF6D1A95);
    final roleInfo = _roleMeta[_selectedRole]!;
    final roleData = _checklistData?[_selectedRole];
    final status = (roleData?['status'] ?? 'unsubmitted').toString().toLowerCase();

    Color statusColor;
    switch (status) {
      case 'approved':
        statusColor = Colors.green.shade600;
        break;
      case 'pending_review':
        statusColor = Colors.amber.shade700;
        break;
      case 'under_scrutiny':
        statusColor = Colors.purple.shade700;
        break;
      case 'rejected':
        statusColor = Colors.redAccent;
        break;
      case 'expired':
        statusColor = Colors.orange.shade800;
        break;
      default:
        statusColor = Colors.grey.shade600;
    }

    return Scaffold(
      backgroundColor: Colors.grey.shade50,
      appBar: AppBar(
        title: const Text('Compliance & Verification Vault', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
        backgroundColor: brandPurple,
        foregroundColor: Colors.white,
        elevation: 0,
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: brandPurple))
          : RefreshIndicator(
              onRefresh: _loadVerificationStatus,
              color: brandPurple,
              child: SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Organization Header Card
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        gradient: const LinearGradient(
                          colors: [Color(0xFF1E072A), Color(0xFF6D1A95)],
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                        ),
                        borderRadius: BorderRadius.circular(16),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withValues(alpha: 0.1),
                            blurRadius: 10,
                            offset: const Offset(0, 4),
                          ),
                        ],
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                decoration: BoxDecoration(
                                  color: Colors.white.withValues(alpha: 0.2),
                                  borderRadius: BorderRadius.circular(6),
                                ),
                                child: const Row(
                                  children: [
                                    Icon(Icons.security, color: Colors.white, size: 12),
                                    SizedBox(width: 4),
                                    Text('TRUST IS NEVER FOR SALE', style: TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold)),
                                  ],
                                ),
                              ),
                              const Spacer(),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                decoration: BoxDecoration(
                                  color: (_orgData?['is_verified'] == true) ? Colors.green.shade600 : Colors.amber.shade700,
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: Text(
                                  (_orgData?['is_verified'] == true) ? 'VERIFIED ENTITY' : 'UNVERIFIED',
                                  style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 12),
                          Text(
                            _orgData?['name'] ?? 'Organization',
                            style: const TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.bold),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            'Jurisdiction: ${_orgData?['country'] ?? 'Ethiopia'} • Role-Based Legal Audit',
                            style: TextStyle(color: Colors.purple.shade100, fontSize: 12),
                          ),
                        ],
                      ),
                    ),

                    const SizedBox(height: 20),

                    // Role Selector Tabs
                    const Text('Select Trading Role to Verify', style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Colors.black87)),
                    const SizedBox(height: 10),
                    SingleChildScrollView(
                      scrollDirection: Axis.horizontal,
                      child: Row(
                        children: _roleMeta.entries.map((entry) {
                          final isSelected = _selectedRole == entry.key;
                          return Padding(
                            padding: const EdgeInsets.only(right: 8),
                            child: ChoiceChip(
                              avatar: Icon(
                                entry.value['icon'] as IconData,
                                size: 16,
                                color: isSelected ? Colors.white : Colors.grey.shade700,
                              ),
                              label: Text(entry.key.toUpperCase()),
                              selected: isSelected,
                              selectedColor: brandPurple,
                              labelStyle: TextStyle(
                                color: isSelected ? Colors.white : Colors.grey.shade800,
                                fontWeight: FontWeight.bold,
                                fontSize: 11,
                              ),
                              onSelected: (val) {
                                if (val) {
                                  setState(() {
                                    _selectedRole = entry.key;
                                    _updateFormFieldsForRole(entry.key);
                                  });
                                }
                              },
                            ),
                          );
                        }).toList(),
                      ),
                    ),

                    const SizedBox(height: 16),

                    // Active Role Status Banner
                    Container(
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: Colors.grey.shade200),
                      ),
                      child: Row(
                        children: [
                          Icon(roleInfo['icon'] as IconData, color: brandPurple, size: 28),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(roleInfo['title'] as String, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                                Text(roleInfo['desc'] as String, style: TextStyle(fontSize: 11, color: Colors.grey.shade600)),
                              ],
                            ),
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                            decoration: BoxDecoration(
                              color: statusColor.withValues(alpha: 0.15),
                              borderRadius: BorderRadius.circular(8),
                              border: Border.all(color: statusColor),
                            ),
                            child: Text(
                              status.replaceAll('_', ' ').toUpperCase(),
                              style: TextStyle(color: statusColor, fontSize: 10, fontWeight: FontWeight.bold),
                            ),
                          ),
                        ],
                      ),
                    ),

                    const SizedBox(height: 20),

                    // License & Identity Credentials Form
                    const Text('License & Tax Credentials', style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Colors.black87)),
                    const SizedBox(height: 8),
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: Colors.grey.shade200),
                      ),
                      child: Column(
                        children: [
                          TextField(
                            controller: _tinController,
                            decoration: const InputDecoration(
                              labelText: 'Tax Identification Number (TIN)',
                              labelStyle: TextStyle(fontSize: 13),
                              prefixIcon: Icon(Icons.numbers, size: 18),
                              border: OutlineInputBorder(),
                              contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                            ),
                          ),
                          const SizedBox(height: 12),
                          TextField(
                            controller: _licenseController,
                            decoration: InputDecoration(
                              labelText: _selectedRole == 'exporter' ? 'ECTA Export License No.' : 'Operating License / Reg No.',
                              labelStyle: const TextStyle(fontSize: 13),
                              prefixIcon: const Icon(Icons.badge, size: 18),
                              border: const OutlineInputBorder(),
                              contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                            ),
                          ),
                          const SizedBox(height: 12),
                          TextField(
                            controller: _authorityController,
                            decoration: const InputDecoration(
                              labelText: 'Issuing Authority (e.g. ECTA, MoTI)',
                              labelStyle: TextStyle(fontSize: 13),
                              prefixIcon: Icon(Icons.account_balance, size: 18),
                              border: OutlineInputBorder(),
                              contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                            ),
                          ),
                          const SizedBox(height: 12),
                          TextField(
                            controller: _expiryController,
                            decoration: const InputDecoration(
                              labelText: 'License Expiry Date (YYYY-MM-DD)',
                              labelStyle: TextStyle(fontSize: 13),
                              prefixIcon: Icon(Icons.event, size: 18),
                              border: OutlineInputBorder(),
                              contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                            ),
                          ),
                        ],
                      ),
                    ),

                    const SizedBox(height: 20),

                    // Required Legal Documents Checklist
                    const Text('Required Compliance Documents', style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Colors.black87)),
                    const SizedBox(height: 8),

                    Builder(
                      builder: (ctx) {
                        final requiredDocs = (roleData?['required_documents'] as List<dynamic>?) ?? [];
                        if (requiredDocs.isEmpty) {
                          return const Text('No documents specified for this role.', style: TextStyle(fontSize: 12, color: Colors.grey));
                        }

                        final vData = roleData?['data'];
                        final uploadedDocs = (vData?['documents'] as List<dynamic>?) ?? [];

                        return Column(
                          children: requiredDocs.map((req) {
                            final type = req['type']?.toString() ?? '';
                            final label = req['label']?.toString() ?? '';
                            final matchingDoc = uploadedDocs.firstWhere(
                              (d) => d['document_type'] == type && d['is_valid'] == true,
                              orElse: () => null,
                            );

                            final hasUploaded = matchingDoc != null;

                            return Container(
                              margin: const EdgeInsets.only(bottom: 10),
                              padding: const EdgeInsets.all(12),
                              decoration: BoxDecoration(
                                color: Colors.white,
                                borderRadius: BorderRadius.circular(12),
                                border: Border.all(color: hasUploaded ? Colors.green.shade200 : Colors.grey.shade200),
                              ),
                              child: Row(
                                children: [
                                  Container(
                                    width: 32,
                                    height: 32,
                                    decoration: BoxDecoration(
                                      color: hasUploaded ? Colors.green.shade50 : Colors.grey.shade100,
                                      borderRadius: BorderRadius.circular(8),
                                    ),
                                    child: Icon(
                                      hasUploaded ? Icons.check_circle : Icons.upload_file,
                                      color: hasUploaded ? Colors.green.shade700 : Colors.grey.shade400,
                                      size: 18,
                                    ),
                                  ),
                                  const SizedBox(width: 12),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Text(label, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                                        Text(
                                          hasUploaded ? '${matchingDoc['original_filename']} (${matchingDoc['file_size_kb']} KB)' : 'Required for regulatory approval',
                                          style: TextStyle(fontSize: 11, color: hasUploaded ? Colors.grey.shade600 : Colors.amber.shade800),
                                        ),
                                      ],
                                    ),
                                  ),
                                  TextButton.icon(
                                    style: TextButton.styleFrom(
                                      foregroundColor: brandPurple,
                                      textStyle: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold),
                                    ),
                                    onPressed: () => _showDocumentUploadDialog(type, label),
                                    icon: Icon(hasUploaded ? Icons.refresh : Icons.cloud_upload, size: 14),
                                    label: Text(hasUploaded ? 'Replace' : 'Upload'),
                                  ),
                                ],
                              ),
                            );
                          }).toList(),
                        );
                      },
                    ),

                    const SizedBox(height: 24),

                    // Submit Action Button
                    SizedBox(
                      width: double.infinity,
                      height: 48,
                      child: ElevatedButton.icon(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: brandPurple,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                          elevation: 2,
                        ),
                        onPressed: _isSubmitting ? null : _submitDossier,
                        icon: _isSubmitting
                            ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                            : const Icon(Icons.send, color: Colors.white, size: 18),
                        label: Text(
                          _isSubmitting ? 'Submitting Dossier...' : 'Submit ${_roleMeta[_selectedRole]!['title']} Dossier for Audit',
                          style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 13),
                        ),
                      ),
                    ),
                    const SizedBox(height: 30),
                  ],
                ),
              ),
            ),
    );
  }
}


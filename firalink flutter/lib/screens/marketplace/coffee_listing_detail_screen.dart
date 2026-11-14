import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:provider/provider.dart';
import '../../providers/auth_provider.dart';
import '../../services/api_exception.dart';
import '../../services/marketplace_service.dart';
import '../../l10n/app_localizations.dart';
import '../../theme/app_theme.dart';
import '../../theme/fira_colors.dart';
import '../../widgets/fira_error_state.dart';
import '../../widgets/workspace_status_pill.dart';
import 'create_rfq_screen.dart';
import 'widgets/lot_evidence_summary_card.dart';
import 'widgets/verification_prompt_dialog.dart';

class CoffeeListingDetailScreen extends StatefulWidget {
  final dynamic idOrSlug;

  const CoffeeListingDetailScreen({super.key, required this.idOrSlug});

  @override
  State<CoffeeListingDetailScreen> createState() => _CoffeeListingDetailScreenState();
}

class _CoffeeListingDetailScreenState extends State<CoffeeListingDetailScreen> {
  final MarketplaceService _marketplaceService = MarketplaceService();

  dynamic _listing;
  dynamic _benchmark;
  Map<String, dynamic>? _evidence;
  bool _isSaved = false;
  bool _isLoading = true;
  Object? _loadError;

  @override
  void initState() {
    super.initState();
    _fetchDetail();
  }

  Future<void> _fetchDetail() async {
    setState(() {
      _isLoading = true;
      _loadError = null;
    });
    try {
      final res = await _marketplaceService.getListingDetail(widget.idOrSlug);
      if (res.statusCode == 404) {
        // A real "not found": the lot was removed or never existed.
        if (mounted) setState(() => _isLoading = false);
        return;
      }
      final data = jsonDecode(requireSuccess(res).body);
      if (mounted) {
        setState(() {
          _listing = data['data']['listing'];
          _benchmark = data['data']['official_benchmark'];
          _evidence = data['data']['evidence'] is Map<String, dynamic> ? data['data']['evidence'] : null;
          _isSaved = data['data']['is_saved'] ?? false;
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _loadError = e;
          _isLoading = false;
        });
      }
    }
  }

  Future<void> _toggleSave() async {
    if (_listing == null) return;
    final auth = context.read<AuthProvider>();
    if (!auth.isAuthenticated) {
      VerificationPromptDialog.showAuthRequired(
        context,
        actionDescription: 'bookmark lots to your trade wishlist',
      );
      return;
    }

    try {
      final res = await _marketplaceService.toggleSaveListing(_listing['id']);
      if (res.statusCode == 200 && mounted) {
        setState(() => _isSaved = !_isSaved);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(_isSaved ? 'Lot saved to favorites' : 'Removed from favorites'),
            backgroundColor: AppTheme.primary,
            duration: const Duration(seconds: 2),
          ),
        );
      } else if (mounted) {
        showFiraActionError(context, ApiException.fromResponse(res));
      }
    } catch (e) {
      if (mounted) showFiraActionError(context, e);
    }
  }

  void _showSampleDialog() {
    final auth = context.read<AuthProvider>();
    if (!auth.isAuthenticated) {
      VerificationPromptDialog.showAuthRequired(
        context,
        actionDescription: 'request direct green coffee samples from exporters',
      );
      return;
    }

    final addressCtrl = TextEditingController();
    final courierCtrl = TextEditingController();
    final messageCtrl = TextEditingController(text: 'We request a 200g-300g green coffee sample for quality evaluation.');
    String? validationError;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Container(
        padding: EdgeInsets.only(
          left: 20,
          right: 20,
          top: 20,
          bottom: MediaQuery.of(ctx).viewInsets.bottom + 20,
        ),
        decoration: BoxDecoration(
          color: Theme.of(ctx).scaffoldBackgroundColor,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
        ),
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text('Request Coffee Sample', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                  IconButton(icon: const Icon(Icons.close), onPressed: () => Navigator.pop(ctx)),
                ],
              ),
              const SizedBox(height: 8),
              const Text('Provide your delivery destination. The exporter will courier sample parcel upon approval.',
                  style: TextStyle(fontSize: 12, color: Colors.grey)),
              const SizedBox(height: 16),
              TextField(
                controller: addressCtrl,
                maxLines: 2,
                decoration: InputDecoration(
                  labelText: 'Delivery Address *',
                  hintText: 'Company, street, city, postal code, country',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: courierCtrl,
                decoration: InputDecoration(
                  labelText: 'Courier Account (DHL / FedEx)',
                  hintText: 'Optional freight account number',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: messageCtrl,
                maxLines: 2,
                decoration: InputDecoration(
                  labelText: 'Notes for Exporter',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
              const SizedBox(height: 18),
              StatefulBuilder(
                builder: (dialogCtx, setDialogState) {
                  return Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      if (validationError != null)
                        Padding(
                          padding: const EdgeInsets.only(bottom: 8.0),
                          child: Text(
                            validationError ?? '',
                            style: const TextStyle(color: Colors.red, fontSize: 12, fontWeight: FontWeight.bold),
                          ),
                        ),
                      SizedBox(
                        width: double.infinity,
                        child: ElevatedButton(
                          onPressed: () async {
                            if (addressCtrl.text.trim().isEmpty) {
                              setDialogState(() {
                                validationError = 'Please enter a valid shipping destination.';
                              });
                              return;
                            }
                            Navigator.pop(ctx);
                            final http.Response res;
                            try {
                              res = await _marketplaceService.submitInquiry(_listing['id'], {
                                'inquiry_type': 'sample_request',
                                'subject': 'Sample Request for ${_listing['title']}',
                                'message': messageCtrl.text.trim(),
                                'shipping_address': addressCtrl.text.trim(),
                                'courier_account': courierCtrl.text.trim(),
                              });
                            } catch (e) {
                              if (mounted) showFiraActionError(context, e);
                              return;
                            }
                            if (res.statusCode == 200 || res.statusCode == 201) {
                              if (mounted) {
                                ScaffoldMessenger.of(context).showSnackBar(
                                  const SnackBar(content: Text('Sample request dispatched to exporter!'), backgroundColor: Colors.green),
                                );
                              }
                            } else if (res.statusCode == 401) {
                              if (mounted) {
                                VerificationPromptDialog.showAuthRequired(
                                  context,
                                  actionDescription: 'request green coffee samples',
                                );
                              }
                            } else if (res.statusCode == 403) {
                              if (mounted) {
                                try {
                                  final data = jsonDecode(res.body);
                                  VerificationPromptDialog.showVerificationRequired(
                                    context,
                                    role: 'buyer',
                                    status: data['verification_status'],
                                    message: data['message'],
                                  );
                                } catch (_) {
                                  VerificationPromptDialog.showVerificationRequired(
                                    context,
                                    role: 'buyer',
                                  );
                                }
                              }
                            } else {
                              if (mounted) {
                                ScaffoldMessenger.of(context).showSnackBar(
                                  const SnackBar(content: Text('Failed to submit sample request. Please try again.'), backgroundColor: Colors.red),
                                );
                              }
                            }
                          },
                          style: ElevatedButton.styleFrom(
                            backgroundColor: AppTheme.primary,
                            foregroundColor: Colors.white,
                            padding: const EdgeInsets.symmetric(vertical: 14),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                          ),
                          child: const Text('Submit Request', style: TextStyle(fontWeight: FontWeight.bold)),
                        ),
                      ),
                    ],
                  );
                },
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _showInquiryDialog() {
    final auth = context.read<AuthProvider>();
    if (!auth.isAuthenticated) {
      VerificationPromptDialog.showAuthRequired(
        context,
        actionDescription: 'send trade inquiries to Ethiopian exporters',
      );
      return;
    }

    final subjectCtrl = TextEditingController(text: 'Commercial Inquiry for ${_listing['title']}');
    final messageCtrl = TextEditingController();
    final bagsCtrl = TextEditingController();
    String inquiryType = 'price_quote';
    String? validationError;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => StatefulBuilder(
        builder: (dialogCtx, setDialogState) {
          return Container(
            padding: EdgeInsets.only(
              left: 20,
              right: 20,
              top: 20,
              bottom: MediaQuery.of(ctx).viewInsets.bottom + 20,
            ),
            decoration: BoxDecoration(
              color: Theme.of(ctx).scaffoldBackgroundColor,
              borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
            ),
            child: SingleChildScrollView(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text('Contact Exporter', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                      IconButton(icon: const Icon(Icons.close), onPressed: () => Navigator.pop(ctx)),
                    ],
                  ),
                  const SizedBox(height: 8),
                  const Text('Send a direct commercial message to the licensed exporter of this coffee lot.',
                      style: TextStyle(fontSize: 12, color: Colors.grey)),
                  const SizedBox(height: 16),
                  DropdownButtonFormField<String>(
                    initialValue: inquiryType,
                    decoration: InputDecoration(
                      labelText: 'Inquiry Purpose',
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                    items: const [
                      DropdownMenuItem(value: 'price_quote', child: Text('FOB / CIF Price Quotation')),
                      DropdownMenuItem(value: 'general', child: Text('General Product Inquiry')),
                      DropdownMenuItem(value: 'visit_request', child: Text('Origin Washing Station Visit')),
                    ],
                    onChanged: (val) {
                      if (val != null) setDialogState(() => inquiryType = val);
                    },
                  ),
                  const SizedBox(height: 12),
                  TextField(
                    controller: subjectCtrl,
                    decoration: InputDecoration(
                      labelText: 'Subject *',
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                  ),
                  const SizedBox(height: 12),
                  TextField(
                    controller: bagsCtrl,
                    keyboardType: TextInputType.number,
                    decoration: InputDecoration(
                      labelText: 'Target Volume (Bags, 60kg each)',
                      hintText: 'e.g. 50',
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                  ),
                  const SizedBox(height: 12),
                  TextField(
                    controller: messageCtrl,
                    maxLines: 3,
                    decoration: InputDecoration(
                      labelText: 'Commercial Specifications / Inquiry *',
                      hintText: 'Include target destination port, delivery timeline, or cupping queries...',
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                  ),
                  const SizedBox(height: 18),
                  if (validationError != null)
                    Padding(
                      padding: const EdgeInsets.only(bottom: 8.0),
                      child: Text(
                        validationError ?? '',
                        style: const TextStyle(color: Colors.red, fontSize: 12, fontWeight: FontWeight.bold),
                      ),
                    ),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: () async {
                        if (messageCtrl.text.trim().isEmpty || subjectCtrl.text.trim().isEmpty) {
                          setDialogState(() {
                            validationError = 'Please provide both subject and message.';
                          });
                          return;
                        }
                        Navigator.pop(ctx);
                        final http.Response res;
                        try {
                          res = await _marketplaceService.submitInquiry(_listing['id'], {
                            'inquiry_type': inquiryType,
                            'subject': subjectCtrl.text.trim(),
                            'message': messageCtrl.text.trim(),
                            'quantity_interested_bags': int.tryParse(bagsCtrl.text.trim()),
                          });
                        } catch (e) {
                          if (mounted) showFiraActionError(context, e);
                          return;
                        }
                        if (res.statusCode == 200 || res.statusCode == 201) {
                          if (mounted) {
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(content: Text('Inquiry delivered directly to exporter!'), backgroundColor: Colors.green),
                            );
                          }
                        } else if (res.statusCode == 401) {
                          if (mounted) {
                            VerificationPromptDialog.showAuthRequired(
                              context,
                              actionDescription: 'send inquiries to exporters',
                            );
                          }
                        } else if (res.statusCode == 403) {
                          if (mounted) {
                            try {
                              final data = jsonDecode(res.body);
                              VerificationPromptDialog.showVerificationRequired(
                                context,
                                role: 'buyer',
                                status: data['verification_status'],
                                message: data['message'],
                              );
                            } catch (_) {
                              VerificationPromptDialog.showVerificationRequired(
                                context,
                                role: 'buyer',
                              );
                            }
                          }
                        } else {
                          if (mounted) {
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(content: Text('Failed to deliver inquiry. Please try again.'), backgroundColor: Colors.red),
                            );
                          }
                        }
                      },
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppTheme.primary,
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                      ),
                      child: const Text('Send Inquiry', style: TextStyle(fontWeight: FontWeight.bold)),
                    ),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    if (_isLoading) {
      return const Scaffold(
        body: Center(child: CircularProgressIndicator(color: AppTheme.primary)),
      );
    }

    if (_loadError != null) {
      return Scaffold(
        appBar: AppBar(),
        body: FiraErrorState(error: _loadError, onRetry: _fetchDetail),
      );
    }

    if (_listing == null) {
      return Scaffold(
        appBar: AppBar(),
        body: const Center(child: Text('Listing lot not found')),
      );
    }

    final title = _listing['title'] ?? 'Ethiopian Specialty Lot';
    final origin = _listing['origin_region'] ?? 'Ethiopia';
    final subRegion = _listing['sub_region'];
    final station = _listing['washing_station'];
    final process = _listing['processing_method']?.toString().replaceAll('_', ' ').toUpperCase() ?? 'WASHED';
    final grade = _listing['grade']?.toString().replaceAll('_', ' ').toUpperCase() ?? 'G1';
    final cuppingScore = _listing['cupping_score'];
    final price = _listing['indicative_price_usd_per_lb'] ?? '0.00';
    final primaryImageUrl = _listing['primary_image']?['url'];
    final tastingNotes = _listing['tasting_notes'] as List<dynamic>? ?? [];
    final sensoryAttributes = _listing['sensory_attributes'] as Map<String, dynamic>? ?? {};
    final seller = _listing['seller'];
    final sellerProfile = seller?['seller_profile'];
    final org = _listing['organization'];
    final companyName = (org?['name'] ?? sellerProfile?['company_name'] ?? seller?['name'] ?? '').toString();
    final tier = int.tryParse('${org?['verification_tier'] ?? ''}');
    final role = (org?['business_role'] ?? org?['business_type'])?.toString();
    final license = sellerProfile?['export_license_no'];

    return Scaffold(
      body: CustomScrollView(
        slivers: [
          // Collapsible Image App Bar
          SliverAppBar(
            expandedHeight: 280,
            pinned: true,
            leading: IconButton(
              icon: Container(
                padding: const EdgeInsets.all(6),
                decoration: BoxDecoration(color: Colors.black45, shape: BoxShape.circle),
                child: const Icon(Icons.arrow_back, color: Colors.white, size: 20),
              ),
              onPressed: () => Navigator.pop(context),
            ),
            actions: [
              IconButton(
                icon: Container(
                  padding: const EdgeInsets.all(6),
                  decoration: BoxDecoration(color: Colors.black45, shape: BoxShape.circle),
                  child: Icon(
                    _isSaved ? Icons.bookmark : Icons.bookmark_border,
                    color: _isSaved ? AppTheme.primary : Colors.white,
                    size: 20,
                  ),
                ),
                onPressed: _toggleSave,
              ),
            ],
            flexibleSpace: FlexibleSpaceBar(
              background: Stack(
                fit: StackFit.expand,
                children: [
                  primaryImageUrl != null
                      ? Image.network(
                          primaryImageUrl,
                          fit: BoxFit.cover,
                          errorBuilder: (context, error, stackTrace) => Container(
                            color: Colors.purple.shade900,
                            child: const Center(
                              child: Icon(Icons.coffee, size: 64, color: Colors.white30),
                            ),
                          ),
                        )
                      : Container(color: Colors.purple.shade900),
                  Container(
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        begin: Alignment.topCenter,
                        end: Alignment.bottomCenter,
                        colors: [Colors.black38, Colors.transparent, Colors.black87],
                      ),
                    ),
                  ),
                  Positioned(
                    bottom: 16,
                    left: 16,
                    right: 16,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                              decoration: BoxDecoration(
                                color: Colors.white24,
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: Text(
                                origin,
                                style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 12),
                              ),
                            ),
                            const SizedBox(width: 8),
                            if (cuppingScore != null)
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                decoration: BoxDecoration(
                                  color: AppTheme.primary,
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                child: Text(
                                  '★ $cuppingScore SCA',
                                  style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 12),
                                ),
                              ),
                          ],
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),

          // Body Content
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.all(16.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Title
                  Text(
                    title,
                    style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w800),
                  ),
                  const SizedBox(height: 8),

                  // Process & Grade Badges
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                        decoration: BoxDecoration(
                          color: AppTheme.primary.withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Text(
                          process,
                          style: const TextStyle(color: AppTheme.primary, fontWeight: FontWeight.bold, fontSize: 12),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                        decoration: BoxDecoration(
                          color: Colors.grey.withValues(alpha: 0.15),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Text(
                          grade,
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12),
                        ),
                      ),
                    ],
                  ),

                  const SizedBox(height: 16),

                  // Official ECTA Benchmark Card
                  if (_benchmark != null)
                    Container(
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        color: Color(0xFF10B981).withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: Color(0xFF10B981).withValues(alpha: 0.3)),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.check_circle, color: Color(0xFF10B981), size: 22),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Text(
                                  'ECTA Daily Export Price Compliant',
                                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF10B981)),
                                ),
                                Text(
                                  _benchmark['regulatory_note'] ?? 'Complies with official minimums.',
                                  style: const TextStyle(fontSize: 11, color: Colors.grey),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),

                  const SizedBox(height: 20),

                  // Tasting Notes
                  if (tastingNotes.isNotEmpty) ...[
                    const Text('Cupping Notes', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                    const SizedBox(height: 8),
                    Wrap(
                      spacing: 8,
                      runSpacing: 6,
                      children: tastingNotes.map((note) {
                        return Chip(
                          label: Text(note.toString(), style: const TextStyle(fontSize: 12, color: AppTheme.primary)),
                          backgroundColor: AppTheme.primary.withValues(alpha: 0.08),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                          side: BorderSide.none,
                        );
                      }).toList(),
                    ),
                    const SizedBox(height: 20),
                  ],

                  // Technical Specs Grid
                  const Text('Technical Export Specs', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                  const SizedBox(height: 10),
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: isDark ? AppTheme.surfaceDark : Colors.grey.shade50,
                      borderRadius: BorderRadius.circular(18),
                      border: Border.all(color: isDark ? Colors.white10 : Colors.grey.shade200),
                    ),
                    child: Column(
                      children: [
                        _specRow('Region', origin),
                        if (subRegion != null) _specRow('Sub-Region', subRegion),
                        if (station != null) _specRow('Washing Station', station),
                        _specRow('Elevation', '${_listing['altitude_min'] ?? 1900}m - ${_listing['altitude_max'] ?? 2200}m'),
                        _specRow('Screen Size', _listing['screen_size'] ?? 'Screen 14+'),
                        _specRow('Moisture Content', '${_listing['moisture_content'] ?? 10.5}%'),
                        _specRow('Harvest Crop Year', _listing['crop_year'] ?? '2025/2026'),
                        _specRow('Packaging', _listing['bag_type']?.toString().replaceAll('_', ' ').toUpperCase() ?? 'GRAINPRO JUTE'),
                        _specRow('Port of Loading', '${_listing['port_of_loading']} (${_listing['incoterm']})'),
                      ],
                    ),
                  ),

                  const SizedBox(height: 20),

                  // SCA Sensory Breakdown
                  if (sensoryAttributes.isNotEmpty) ...[
                    const Text('SCA Sensory Profile', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                    const SizedBox(height: 10),
                    ...sensoryAttributes.entries.map((entry) {
                      final val = (entry.value as num?)?.toDouble() ?? 8.0;
                      return Padding(
                        padding: const EdgeInsets.only(bottom: 8.0),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Text(entry.key.toUpperCase(), style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600)),
                                Text('$val / 10', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: AppTheme.primary)),
                              ],
                            ),
                            const SizedBox(height: 4),
                            ClipRRect(
                              borderRadius: BorderRadius.circular(4),
                              child: LinearProgressIndicator(
                                value: val / 10.0,
                                minHeight: 6,
                                backgroundColor: isDark ? Colors.white10 : Colors.grey.shade200,
                                valueColor: const AlwaysStoppedAnimation<Color>(AppTheme.primary),
                              ),
                            ),
                          ],
                        ),
                      );
                    }),
                    const SizedBox(height: 20),
                  ],

                  // Exporter Information Card
                  const Text('Producer & Exporter', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                  const SizedBox(height: 10),
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: isDark ? AppTheme.surfaceDark : Colors.white,
                      borderRadius: BorderRadius.circular(18),
                      border: Border.all(color: isDark ? Colors.white10 : Colors.grey.shade200),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Container(
                              padding: const EdgeInsets.all(10),
                              decoration: BoxDecoration(
                                color: AppTheme.primary.withValues(alpha: 0.1),
                                shape: BoxShape.circle,
                              ),
                              child: const Icon(Icons.business, color: AppTheme.primary, size: 22),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(companyName, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                                  if (role != null && role.isNotEmpty)
                                    Text(WorkspaceLabels.businessRole(context.l10n, role), style: TextStyle(fontSize: 12, color: context.fira.mutedText)),
                                  if (tier != null && tier > 0)
                                    Text(
                                      context.l10n.translate('sellerVerifiedTier', {'tier': tier}),
                                      key: const Key('detail-tier'),
                                      style: TextStyle(fontSize: 12, color: context.fira.success, fontWeight: FontWeight.w600),
                                    ),
                                  if (license != null)
                                    Text('ECTA License: $license', style: const TextStyle(fontSize: 11, color: Colors.grey)),
                                ],
                              ),
                            ),
                            const Icon(Icons.verified, color: Colors.green, size: 20),
                          ],
                        ),
                      ],
                    ),
                  ),

                  if (_evidence != null) ...[
                    const SizedBox(height: 16),
                    LotEvidenceSummaryCard(evidence: _evidence!),
                  ],

                  const SizedBox(height: 16),
                  FilledButton.tonalIcon(
                    key: const Key('lot-request-quote'),
                    onPressed: () {
                      if (!context.read<AuthProvider>().isAuthenticated) {
                        VerificationPromptDialog.showAuthRequired(context, actionDescription: context.l10n.exporterRequestQuote);
                        return;
                      }
                      Navigator.of(context).push(MaterialPageRoute(
                        builder: (_) => CreateRfqScreen(prefillListingId: (_listing['id'] as num?)?.toInt()),
                      ));
                    },
                    icon: const Icon(Icons.request_quote_outlined),
                    label: Text(context.l10n.exporterRequestQuote),
                  ),

                  const SizedBox(height: 100), // Spacing for bottom bar
                ],
              ),
            ),
          ),
        ],
      ),

      // Bottom Action Bar
      bottomSheet: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
        decoration: BoxDecoration(
          color: isDark ? AppTheme.surfaceDark : Colors.white,
          boxShadow: [
            BoxShadow(color: Colors.black.withValues(alpha: 0.08), blurRadius: 10, offset: const Offset(0, -4)),
          ],
        ),
        child: SafeArea(
          child: Row(
            children: [
              Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('Indicative FOB', style: TextStyle(fontSize: 10, color: Colors.grey)),
                  Text('\$$price / lb', style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w900)),
                ],
              ),
              const SizedBox(width: 12),
              OutlinedButton.icon(
                onPressed: _showInquiryDialog,
                style: OutlinedButton.styleFrom(
                  foregroundColor: isDark ? Colors.white : Colors.black87,
                  side: BorderSide(color: isDark ? Colors.white24 : Colors.grey.shade400),
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 12),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                ),
                icon: const Icon(Icons.chat_bubble_outline, size: 16),
                label: const Text('Inquire', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: ElevatedButton.icon(
                  onPressed: _showSampleDialog,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppTheme.primary,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                  ),
                  icon: const Icon(Icons.coffee, size: 16),
                  label: const Text('Sample', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _specRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6.0),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: const TextStyle(fontSize: 12, color: Colors.grey)),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              value,
              textAlign: TextAlign.end,
              style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold),
            ),
          ),
        ],
      ),
    );
  }
}

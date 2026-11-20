import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:open_filex/open_filex.dart';
import 'package:provider/provider.dart';
import 'package:share_plus/share_plus.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../providers/auth_provider.dart';
import '../../services/export_docs_service.dart';
import '../../theme/app_theme.dart';
import '../../l10n/app_localizations.dart';
import '../../theme/fira_colors.dart';
import '../../widgets/fira_error_state.dart';
import 'export_doc_editor_screen.dart';
import 'widgets/verification_prompt_dialog.dart';

class ExportDocsStudioScreen extends StatefulWidget {
  const ExportDocsStudioScreen({super.key});

  @override
  State<ExportDocsStudioScreen> createState() => _ExportDocsStudioScreenState();
}

class _ExportDocsStudioScreenState extends State<ExportDocsStudioScreen> with SingleTickerProviderStateMixin {
  final ExportDocsService _service = ExportDocsService();
  late TabController _tabController;

  // Templates Tab State
  List<dynamic> _templates = [];
  Map<String, dynamic>? _trialInfo;
  bool _isLoadingTemplates = true;
  String _selectedCategory = 'all';
  final TextEditingController _searchController = TextEditingController();

  // Shipments Tab State
  List<dynamic> _shipments = [];
  bool _isLoadingShipments = true;

  // Documents Tab State
  List<dynamic> _documents = [];
  bool _isLoadingDocuments = true;

  // Trade Calculator State
  final TextEditingController _calcBagsController = TextEditingController(text: '320');
  final TextEditingController _calcPriceController = TextEditingController(text: '2.6000');
  Map<String, dynamic>? _calculationResult;

  final Map<String, String> _categories = {
    'all': 'All Templates (30)',
    'commercial': 'Commercial & Financial',
    'shipping': 'Shipping & Freight',
    'coffee_specific': 'Coffee & Commodity',
    'banking': 'Banking & Payment',
    'declarations': 'Declarations & Compliance',
    'operations': 'Trade Operations',
  };

  String _getCategoryTitle(String key) {
    switch (key) {
      case 'all':
        return context.l10n.allTemplates;
      case 'commercial':
        return context.l10n.commercialFinancial;
      case 'shipping':
        return context.l10n.shippingFreight;
      case 'coffee_specific':
        return context.l10n.coffeeCommodity;
      case 'banking':
        return context.l10n.bankingPayment;
      case 'declarations':
        return context.l10n.declarationsCompliance;
      case 'operations':
        return context.l10n.tradeOperations;
      default:
        return _categories[key] ?? key;
    }
  }

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 4, vsync: this);
    _tabController.addListener(() {
      if (_tabController.indexIsChanging) {
        if (_tabController.index == 1 && _shipments.isEmpty) _fetchShipments();
        if (_tabController.index == 3 && _documents.isEmpty) _fetchDocuments();
      }
    });

    _fetchTemplates();
    _runCalculator();
  }

  @override
  void dispose() {
    _tabController.dispose();
    _searchController.dispose();
    _calcBagsController.dispose();
    _calcPriceController.dispose();
    super.dispose();
  }

  Future<void> _fetchTemplates() async {
    setState(() => _isLoadingTemplates = true);
    try {
      final res = await _service.getTemplates(
        category: _selectedCategory,
        search: _searchController.text.trim(),
      );
      if (res.statusCode == 200 && mounted) {
        final data = jsonDecode(res.body);
        setState(() {
          _templates = data['data'] ?? [];
          _trialInfo = data['trial_info'];
          _isLoadingTemplates = false;
        });
      } else {
        if (mounted) setState(() => _isLoadingTemplates = false);
      }
    } catch (_) {
      if (mounted) setState(() => _isLoadingTemplates = false);
    }
  }

  Future<void> _fetchShipments() async {
    setState(() => _isLoadingShipments = true);
    try {
      final res = await _service.getShipments();
      if (res.statusCode == 200 && mounted) {
        final data = jsonDecode(res.body);
        final raw = data['data'];
        setState(() {
          if (raw is List) {
            _shipments = raw;
          } else if (raw is Map && raw['data'] is List) {
            _shipments = raw['data'];
          }
          _isLoadingShipments = false;
        });
      } else {
        if (mounted) setState(() => _isLoadingShipments = false);
      }
    } catch (_) {
      if (mounted) setState(() => _isLoadingShipments = false);
    }
  }

  Future<void> _fetchDocuments() async {
    setState(() => _isLoadingDocuments = true);
    try {
      final res = await _service.getDocuments();
      if (res.statusCode == 200 && mounted) {
        final data = jsonDecode(res.body);
        final raw = data['data'];
        setState(() {
          if (raw is List) {
            _documents = raw;
          } else if (raw is Map && raw['data'] is List) {
            _documents = raw['data'];
          }
          _isLoadingDocuments = false;
        });
      } else {
        if (mounted) setState(() => _isLoadingDocuments = false);
      }
    } catch (_) {
      if (mounted) setState(() => _isLoadingDocuments = false);
    }
  }

  Future<void> _runCalculator() async {
    final bags = int.tryParse(_calcBagsController.text.trim()) ?? 320;
    final price = double.tryParse(_calcPriceController.text.trim()) ?? 2.60;

    try {
      final res = await _service.calculate({
        'total_bags': bags,
        'bag_weight_kg': 60.0,
        'tare_weight_per_bag_kg': 0.80,
        'unit_price_usd_per_lb': price,
      });

      if (res.statusCode == 200 && mounted) {
        final data = jsonDecode(res.body);
        setState(() {
          _calculationResult = data['combined'];
        });
      }
    } catch (_) {}
  }

  Future<void> _openEditor({
    int? documentId,
    String? templateCode,
    String? templateTitle,
    bool? isFree,
  }) async {
    final auth = context.read<AuthProvider>();
    if (!auth.isAuthenticated) {
      VerificationPromptDialog.showAuthRequired(
        context,
        actionDescription: 'customize, create, and export professional trade documents',
      );
      return;
    }

    final isTemplateFree = isFree ?? false;
    final isPro = _trialInfo?['is_pro'] == true;
    final trialRemaining = _trialInfo?['trial_remaining'] ?? 3;
    final trialExpired = _trialInfo?['trial_expired'] == true || trialRemaining <= 0;

    if (!isTemplateFree && !isPro && trialExpired) {
      _showPaywallModal(context, templateCode: templateCode, templateTitle: templateTitle);
      return;
    }

    final result = await Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => ExportDocEditorScreen(
          documentId: documentId,
          templateCode: templateCode,
          templateTitle: templateTitle,
        ),
      ),
    );
    if (result == true) {
      _fetchDocuments();
      _fetchTemplates();
    }
  }

  void _showPaywallModal(BuildContext context, {String? templateCode, String? templateTitle}) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    int selectedBillingIndex = 0;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: isDark ? const Color(0xFF1E1E2C) : Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
      ),
      builder: (ctx) {
        return StatefulBuilder(
          builder: (ctx, setModalState) {
            return SafeArea(
              child: SingleChildScrollView(
                padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 20),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Container(
                      width: 44,
                      height: 5,
                      decoration: BoxDecoration(
                        color: Colors.grey.withValues(alpha: 0.3),
                        borderRadius: BorderRadius.circular(3),
                      ),
                    ),
                    const SizedBox(height: 18),
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        gradient: const LinearGradient(
                          colors: [Color(0xFF6D1A95), Color(0xFF9C27B0)],
                        ),
                        shape: BoxShape.circle,
                        boxShadow: [
                          BoxShadow(
                            color: const Color(0xFF6D1A95).withValues(alpha: 0.35),
                            blurRadius: 16,
                            offset: const Offset(0, 6),
                          ),
                        ],
                      ),
                      child: const Icon(Icons.workspace_premium, color: Colors.amber, size: 36),
                    ),
                    const SizedBox(height: 16),
                    Text(
                      context.l10n.exportDocsStudioPro,
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        fontSize: 21,
                        fontWeight: FontWeight.bold,
                        letterSpacing: -0.3,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      templateTitle != null
                          ? '[$templateTitle] is a verified Pro export standard.'
                          : 'You have used all 3 free trial documents.',
                      textAlign: TextAlign.center,
                      style: const TextStyle(fontSize: 13, color: Colors.grey, height: 1.4),
                    ),
                    const SizedBox(height: 18),
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: isDark ? Colors.black26 : const Color(0xFFF9F5FC),
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: const Color(0xFF6D1A95).withValues(alpha: 0.15)),
                      ),
                      child: Column(
                        children: [
                          _buildProBullet('Unlimited access to all 30 Ethiopian & International Trade Templates'),
                          _buildProBullet('EUDR smallholder GPS coordinates & polygon traceability exports'),
                          _buildProBullet('ECTA CLU & SCA 100-point coffee cupping quality grading reports'),
                          _buildProBullet('Bank presentation schedules (URC 522) & Letter of Credit audit'),
                          _buildProBullet('High-resolution, watermark-free PDF & Excel exports'),
                          _buildProBullet('Fira AI trade copilot & discrepancy checker'),
                        ],
                      ),
                    ),
                    const SizedBox(height: 20),
                    Row(
                      children: [
                        Expanded(
                          child: InkWell(
                            onTap: () => setModalState(() => selectedBillingIndex = 0),
                            borderRadius: BorderRadius.circular(14),
                            child: Container(
                              padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 12),
                              decoration: BoxDecoration(
                                borderRadius: BorderRadius.circular(14),
                                border: Border.all(
                                  color: selectedBillingIndex == 0 ? const Color(0xFF6D1A95) : Colors.grey.shade300,
                                  width: selectedBillingIndex == 0 ? 2 : 1,
                                ),
                                color: selectedBillingIndex == 0
                                    ? const Color(0xFF6D1A95).withValues(alpha: 0.08)
                                    : Colors.transparent,
                              ),
                              child: Column(
                                children: [
                                  Text(context.l10n.monthlySubscription, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                                  const SizedBox(height: 4),
                                  const Text('1,500 ETB', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF6D1A95))),
                                  Text('/month (~\\\$25)', style: TextStyle(fontSize: 10, color: Colors.grey.shade600)),
                                ],
                              ),
                            ),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: InkWell(
                            onTap: () => setModalState(() => selectedBillingIndex = 1),
                            borderRadius: BorderRadius.circular(14),
                            child: Stack(
                              clipBehavior: Clip.none,
                              children: [
                                Container(
                                  width: double.infinity,
                                  padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 12),
                                  decoration: BoxDecoration(
                                    borderRadius: BorderRadius.circular(14),
                                    border: Border.all(
                                      color: selectedBillingIndex == 1 ? const Color(0xFF6D1A95) : Colors.grey.shade300,
                                      width: selectedBillingIndex == 1 ? 2 : 1,
                                    ),
                                    color: selectedBillingIndex == 1
                                        ? const Color(0xFF6D1A95).withValues(alpha: 0.08)
                                        : Colors.transparent,
                                  ),
                                  child: Column(
                                    children: [
                                      Text(context.l10n.annualSubscription, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                                      const SizedBox(height: 4),
                                      const Text('14,900 ETB', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF6D1A95))),
                                      Text('/year (~\\\$240)', style: TextStyle(fontSize: 10, color: Colors.grey.shade600)),
                                    ],
                                  ),
                                ),
                                Positioned(
                                  top: -10,
                                  right: 12,
                                  child: Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                                    decoration: BoxDecoration(
                                      color: Colors.amber.shade700,
                                      borderRadius: BorderRadius.circular(10),
                                    ),
                                    child: const Text('SAVE 17%', style: TextStyle(color: Colors.white, fontSize: 9, fontWeight: FontWeight.bold)),
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 22),
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xFF6D1A95),
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(vertical: 15),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                          elevation: 3,
                        ),
                        onPressed: () {
                          Navigator.pop(ctx);
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(
                              backgroundColor: Color(0xFF6D1A95),
                              content: Text('Redirecting to Chapa / Telebirr secure subscription checkout...'),
                            ),
                          );
                        },
                        child: Text(
                          selectedBillingIndex == 0 ? 'Subscribe Now — 1,500 ETB / Mo' : 'Subscribe Annually — 14,900 ETB / Yr',
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                        ),
                      ),
                    ),
                    const SizedBox(height: 10),
                    TextButton(
                      onPressed: () => Navigator.pop(ctx),
                      child: const Text('Maybe Later', style: TextStyle(color: Colors.grey)),
                    ),
                  ],
                ),
              ),
            );
          },
        );
      },
    );
  }

  Widget _buildProBullet(String text) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4.0),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Icon(Icons.check_circle, color: Color(0xFF2E7D32), size: 16),
          const SizedBox(width: 8),
          Expanded(
            child: Text(text, style: const TextStyle(fontSize: 12, height: 1.35)),
          ),
        ],
      ),
    );
  }

  Future<void> _downloadAndOpenFile(int docId, String format, String filename) async {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => const Center(
        child: Card(
          child: Padding(
            padding: EdgeInsets.all(20.0),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                CircularProgressIndicator(color: Color(0xFF6D1A95)),
                SizedBox(width: 16),
                Text('Downloading export document...', style: TextStyle(fontWeight: FontWeight.bold)),
              ],
            ),
          ),
        ),
      ),
    );

    try {
      final path = await _service.downloadFile(docId, format, filename);
      if (mounted) Navigator.of(context).pop();

      if (path != null && mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            backgroundColor: Colors.green,
            content: Text('$format downloaded: $filename'),
            action: SnackBarAction(
              label: 'Share',
              textColor: Colors.amber,
              onPressed: () => Share.shareXFiles([XFile(path)], text: 'Export Document $filename'),
            ),
          ),
        );
        await OpenFilex.open(path);
      } else {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Failed to download $format.')),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        Navigator.of(context).pop();
        showFiraActionError(context, e);
      }
    }
  }

  Future<void> _launchWebStudio([int? docId]) async {
    final url = Uri.parse(_service.getWebStudioUrl(docId));
    if (await canLaunchUrl(url)) {
      await launchUrl(url, mode: LaunchMode.externalApplication);
    } else {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Could not open $url')),
        );
      }
    }
  }

  Future<void> _generateAllForShipment(int shipmentId, String refNo) async {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => const Center(
        child: Card(
          child: Padding(
            padding: EdgeInsets.all(20.0),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                CircularProgressIndicator(color: Color(0xFF6D1A95)),
                SizedBox(width: 16),
                Text('Generating Export Suite (PDF + Excel)...', style: TextStyle(fontWeight: FontWeight.bold)),
              ],
            ),
          ),
        ),
      ),
    );

    try {
      final res = await _service.generateAllDocuments(shipmentId);
      if (mounted) Navigator.of(context).pop(); // Dismiss loading

      if (res.statusCode == 200 && mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            backgroundColor: Colors.green,
            content: Text('Export Document Suite generated successfully for $refNo!'),
          ),
        );
        _fetchDocuments();
        _tabController.animateTo(3); // Jump to My Documents tab
      } else {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Failed to generate document bundle.')),
          );
        }
      }
    } catch (_) {
      if (mounted) {
        Navigator.of(context).pop();
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Connection error while generating.')),
        );
      }
    }
  }

  void _showAiAdvisorDialog() {
    final promptController = TextEditingController();
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Container(
        padding: EdgeInsets.only(
          bottom: MediaQuery.of(ctx).viewInsets.bottom + 20,
          left: 20,
          right: 20,
          top: 20,
        ),
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: const Color(0xFF6D1A95).withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: const Icon(Icons.auto_awesome, color: Color(0xFF6D1A95), size: 20),
                ),
                const SizedBox(width: 12),
                const Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Fira AI Trade Advisor', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                    Text('Ethiopian Export Documentation Intelligence', style: TextStyle(color: Colors.grey, fontSize: 11)),
                  ],
                ),
              ],
            ),
            const SizedBox(height: 16),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.amber.shade50,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: Colors.amber.shade200),
              ),
              child: const Text(
                'Notice: Official ECTA quality inspections, Phytosanitary certificates, and NBE foreign exchange permits must be issued by government authorities. Fira AI assists in preparing exporter-issued documents.',
                style: TextStyle(fontSize: 11, color: Colors.brown, height: 1.4),
              ),
            ),
            const SizedBox(height: 16),
            const Text('Ask anything regarding export compliance, ICO marks, or CAD payment:', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
            const SizedBox(height: 8),
            TextField(
              controller: promptController,
              decoration: InputDecoration(
                hintText: 'e.g. What is the standard ICO mark format for Sidama coffee?',
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
              ),
            ),
            const SizedBox(height: 12),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF6D1A95),
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 12),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                ),
                icon: const Icon(Icons.send, size: 16),
                label: const Text('Consult Fira AI'),
                onPressed: () async {
                  final text = promptController.text.trim();
                  if (text.isEmpty) return;
                  Navigator.pop(ctx);

                  showDialog(
                    context: context,
                    builder: (c) => FutureBuilder(
                      future: _service.aiAssist(
                        action: 'compliance',
                        templateCode: 'EXP-CI-01',
                        payload: {'commodity': 'Ethiopia Arabica Coffee'},
                        userPrompt: text,
                      ),
                      builder: (c, snap) {
                        if (snap.connectionState == ConnectionState.waiting) {
                          return const Center(child: CircularProgressIndicator(color: Color(0xFF6D1A95)));
                        }
                        if (snap.hasError || snap.data?.statusCode != 200) {
                          return AlertDialog(
                            title: const Text('AI Advisor'),
                            content: const Text('Unable to reach AI intelligence. Please verify network.'),
                            actions: [TextButton(onPressed: () => Navigator.pop(c), child: const Text('Close'))],
                          );
                        }
                        final data = jsonDecode(snap.data!.body);
                        final advice = data['advice'] ?? 'No advice returned.';

                        return AlertDialog(
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                          title: const Row(
                            children: [
                              Icon(Icons.auto_awesome, color: Color(0xFF6D1A95)),
                              SizedBox(width: 8),
                              Text('Fira AI Guidance', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                            ],
                          ),
                          content: SingleChildScrollView(
                            child: Text(advice, style: const TextStyle(fontSize: 12.5, height: 1.45)),
                          ),
                          actions: [
                            TextButton(onPressed: () => Navigator.pop(c), child: const Text('OK', style: TextStyle(color: Color(0xFF6D1A95), fontWeight: FontWeight.bold))),
                          ],
                        );
                      },
                    ),
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Scaffold(
      backgroundColor: AppTheme.getBackgroundColor(context),
      appBar: AppBar(
        backgroundColor: const Color(0xFF6D1A95),
        foregroundColor: Colors.white,
        elevation: 0,
        title: const Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Fira Export Docs', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 17)),
            Text('Enterprise Export Documentation Studio', style: TextStyle(fontSize: 11, color: Colors.white70)),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.auto_awesome, color: Colors.amber),
            tooltip: 'Fira AI Trade Advisor',
            onPressed: _showAiAdvisorDialog,
          ),
          IconButton(
            icon: const Icon(Icons.post_add_rounded, color: Colors.white),
            tooltip: 'New Document in Mobile',
            onPressed: () => _openEditor(),
          ),
        ],
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: Colors.amber,
          indicatorWeight: 3,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white60,
          labelStyle: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12),
          tabs: [
            Tab(icon: const Icon(Icons.table_chart_outlined, size: 18), text: context.l10n.templates),
            Tab(icon: const Icon(Icons.local_shipping_outlined, size: 18), text: context.l10n.shipments),
            Tab(icon: const Icon(Icons.calculate_outlined, size: 18), text: context.l10n.tradeCalculator),
            Tab(icon: const Icon(Icons.folder_shared_outlined, size: 18), text: context.l10n.myDocuments),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildTemplatesTab(context, isDark),
          _buildShipmentsTab(context, isDark),
          _buildCalculatorTab(context, isDark),
          _buildDocumentsTab(context, isDark),
        ],
      ),
    );
  }

  // ==========================================
  // TAB 1: 30-TEMPLATE CATALOG
  // ==========================================
  Widget _buildSubscriptionBanner() {
    final auth = context.watch<AuthProvider>();
    if (!auth.isAuthenticated) {
      return Container(
        margin: const EdgeInsets.fromLTRB(16, 12, 16, 0),
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: const Color(0xFF6D1A95).withValues(alpha: 0.08),
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: const Color(0xFF6D1A95).withValues(alpha: 0.2)),
        ),
        child: Row(
          children: [
            const Icon(Icons.shield_outlined, color: Color(0xFF6D1A95), size: 26),
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('Visitor Mode: 30 Trade Templates', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12.5)),
                  const SizedBox(height: 2),
                  Text('Sign in or register to customize documents & claim your 3-document Free Trial.', style: TextStyle(fontSize: 11, color: Colors.grey.shade700)),
                ],
              ),
            ),
            const SizedBox(width: 8),
            ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF6D1A95),
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                minimumSize: Size.zero,
                tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
              ),
              onPressed: () => VerificationPromptDialog.showAuthRequired(
                context,
                actionDescription: 'sign in and activate your 3-document Free Trial',
              ),
              child: const Text('Sign In', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
            ),
          ],
        ),
      );
    }

    final isPro = _trialInfo?['is_pro'] == true;
    if (isPro) {
      // A paid plan, not a verification: no verified tick and no success green (auth gating B-6).
      final fira = context.fira;
      return Container(
        key: const Key('export-docs-pro-banner'),
        margin: const EdgeInsets.fromLTRB(16, 12, 16, 0),
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: fira.brandTint,
          borderRadius: BorderRadius.circular(14),
        ),
        child: Row(
          children: [
            Icon(Icons.workspace_premium_outlined, color: fira.brandPrimary, size: 24),
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(context.l10n.exportDocsProActiveTitle, style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12.5, color: fira.onSurface)),
                  Text(context.l10n.exportDocsProActiveBody, style: TextStyle(fontSize: 11, color: fira.mutedText)),
                ],
              ),
            ),
          ],
        ),
      );
    }

    final remaining = _trialInfo?['trial_remaining'] ?? 3;
    final used = _trialInfo?['docs_created'] ?? 0;

    return Container(
      margin: const EdgeInsets.fromLTRB(16, 12, 16, 0),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [Colors.amber.withValues(alpha: 0.16), Colors.orange.withValues(alpha: 0.06)],
        ),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: Colors.amber.shade700.withValues(alpha: 0.35)),
      ),
      child: Row(
        children: [
          Icon(Icons.workspace_premium, color: Colors.amber.shade800, size: 26),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    const Text('Free Trial Active', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12.5)),
                    const SizedBox(width: 6),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1.5),
                      decoration: BoxDecoration(
                        color: Colors.amber.shade800,
                        borderRadius: BorderRadius.circular(4),
                      ),
                      child: Text(
                        '$remaining exports left',
                        style: const TextStyle(color: Colors.white, fontSize: 9.5, fontWeight: FontWeight.bold),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 2),
                Text('$used of 3 trial documents used. Upgrade to Pro for unlimited exports.', style: TextStyle(fontSize: 11, color: Colors.grey.shade800)),
              ],
            ),
          ),
          const SizedBox(width: 8),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.amber.shade800,
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
              minimumSize: Size.zero,
              tapTargetSize: MaterialTapTargetSize.shrinkWrap,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
            ),
            onPressed: () => _showPaywallModal(context),
            child: const Text('Upgrade', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );
  }

  Widget _buildTemplatesTab(BuildContext context, bool isDark) {
    return Column(
      children: [
        // Search & Category Bar
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
          color: isDark ? AppTheme.surfaceDark : Colors.white,
          child: Column(
            children: [
              TextField(
                controller: _searchController,
                onSubmitted: (_) => _fetchTemplates(),
                decoration: InputDecoration(
                  hintText: 'Search templates (e.g. Invoice, ICO, Packing)...',
                  prefixIcon: const Icon(Icons.search, size: 20, color: Color(0xFF6D1A95)),
                  suffixIcon: _searchController.text.isNotEmpty
                      ? IconButton(
                            tooltip: 'Clear',
                            icon: const Icon(Icons.clear, size: 18),
                          onPressed: () {
                            _searchController.clear();
                            _fetchTemplates();
                          },
                        )
                      : null,
                  filled: true,
                  fillColor: isDark ? Colors.grey.shade900 : Colors.grey.shade100,
                  contentPadding: const EdgeInsets.symmetric(vertical: 10),
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
                ),
              ),
              const SizedBox(height: 10),
              SingleChildScrollView(
                scrollDirection: Axis.horizontal,
                child: Row(
                  children: _categories.entries.map((entry) {
                    final isSelected = _selectedCategory == entry.key;
                    return Padding(
                      padding: const EdgeInsets.only(right: 8.0),
                      child: ChoiceChip(
                        label: Text(_getCategoryTitle(entry.key), style: TextStyle(fontSize: 11, fontWeight: isSelected ? FontWeight.bold : FontWeight.normal)),
                        selected: isSelected,
                        selectedColor: const Color(0xFF6D1A95),
                        labelStyle: TextStyle(color: isSelected ? Colors.white : Colors.black87),
                        onSelected: (_) {
                          setState(() => _selectedCategory = entry.key);
                          _fetchTemplates();
                        },
                      ),
                    );
                  }).toList(),
                ),
              ),
            ],
          ),
        ),

        // Visitor / Free Trial Banner
        _buildSubscriptionBanner(),

        // Templates List
        Expanded(
          child: _isLoadingTemplates
              ? const Center(child: CircularProgressIndicator(color: Color(0xFF6D1A95)))
              : _templates.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.folder_open, size: 48, color: Colors.grey.shade400),
                          const SizedBox(height: 8),
                          const Text('No templates found', style: TextStyle(color: Colors.grey)),
                        ],
                      ),
                    )
                  : RefreshIndicator(
                      onRefresh: _fetchTemplates,
                      color: const Color(0xFF6D1A95),
                      child: ListView.separated(
                        padding: const EdgeInsets.all(16),
                        itemCount: _templates.length,
                        separatorBuilder: (c, i) => const SizedBox(height: 12),
                        itemBuilder: (context, index) {
                          final tmpl = _templates[index];
                          final code = tmpl['code'] ?? tmpl['template_code'] ?? '';
                          final name = tmpl['name'] ?? '';
                          final desc = tmpl['description'] ?? '';
                          final isFree = tmpl['is_free'] == true;

                          return Card(
                            elevation: 0,
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(16),
                              side: BorderSide(
                                color: isFree ? Colors.grey.shade200 : Colors.amber.shade300,
                                width: isFree ? 1.0 : 1.4,
                              ),
                            ),
                            child: InkWell(
                              borderRadius: BorderRadius.circular(16),
                              onTap: () => _openEditor(templateCode: code, templateTitle: name, isFree: isFree),
                              child: Padding(
                                padding: const EdgeInsets.all(16.0),
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Row(
                                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                      children: [
                                        Row(
                                          children: [
                                            Container(
                                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                              decoration: BoxDecoration(
                                                color: const Color(0xFF6D1A95).withValues(alpha: 0.1),
                                                borderRadius: BorderRadius.circular(6),
                                              ),
                                              child: Text(code, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 11, color: Color(0xFF6D1A95), fontFamily: 'monospace')),
                                            ),
                                            const SizedBox(width: 8),
                                            Container(
                                              padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2.5),
                                              decoration: BoxDecoration(
                                                color: isFree ? Colors.green.withValues(alpha: 0.12) : Colors.amber.withValues(alpha: 0.2),
                                                borderRadius: BorderRadius.circular(6),
                                                border: Border.all(
                                                  color: isFree ? Colors.green.withValues(alpha: 0.4) : Colors.amber.shade700.withValues(alpha: 0.5),
                                                ),
                                              ),
                                              child: Row(
                                                mainAxisSize: MainAxisSize.min,
                                                children: [
                                                  if (!isFree) ...[
                                                    Icon(Icons.workspace_premium, size: 11, color: Colors.amber.shade900),
                                                    const SizedBox(width: 2),
                                                  ],
                                                  Text(
                                                    isFree ? 'FREE' : 'PRO',
                                                    style: TextStyle(
                                                      fontSize: 9.5,
                                                      fontWeight: FontWeight.bold,
                                                      color: isFree ? Colors.green.shade800 : Colors.amber.shade900,
                                                    ),
                                                  ),
                                                ],
                                              ),
                                            ),
                                          ],
                                        ),
                                        const Row(
                                          children: [
                                            Chip(label: Text('A4 PDF', style: TextStyle(fontSize: 9, fontWeight: FontWeight.bold)), visualDensity: VisualDensity.compact, padding: EdgeInsets.zero),
                                            SizedBox(width: 4),
                                            Chip(label: Text('XLSX', style: TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: Colors.green)), visualDensity: VisualDensity.compact, padding: EdgeInsets.zero),
                                          ],
                                        ),
                                      ],
                                    ),
                                    const SizedBox(height: 8),
                                    Text(name, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                                    const SizedBox(height: 4),
                                    Text(desc, style: TextStyle(fontSize: 12, color: Colors.grey.shade600, height: 1.35)),
                                    const SizedBox(height: 12),
                                    Row(
                                      mainAxisAlignment: MainAxisAlignment.end,
                                      children: [
                                        OutlinedButton.icon(
                                          style: OutlinedButton.styleFrom(
                                            foregroundColor: const Color(0xFF6D1A95),
                                            side: const BorderSide(color: Color(0xFF6D1A95)),
                                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                          ),
                                          icon: const Icon(Icons.edit_note, size: 16),
                                          label: const Text('Customize', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                                          onPressed: () => _openEditor(templateCode: code, templateTitle: name, isFree: isFree),
                                        ),
                                        const SizedBox(width: 8),
                                        ElevatedButton.icon(
                                          style: ElevatedButton.styleFrom(
                                            backgroundColor: isFree ? const Color(0xFF6D1A95) : Colors.amber.shade800,
                                            foregroundColor: Colors.white,
                                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                          ),
                                          icon: Icon(isFree ? Icons.auto_awesome : Icons.lock_open, size: 14, color: Colors.white),
                                          label: Text(isFree ? 'Fira AI Copilot' : 'Unlock Pro', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                                          onPressed: () => _openEditor(templateCode: code, templateTitle: name, isFree: isFree),
                                        ),
                                      ],
                                    ),
                                  ],
                                ),
                              ),
                            ),
                          );
                        },
                      ),
                    ),
        ),
      ],
    );
  }

  // ==========================================
  // TAB 2: CANONICAL SHIPMENTS ("ENTER ONCE")
  // ==========================================
  Widget _buildShipmentsTab(BuildContext context, bool isDark) {
    return _isLoadingShipments
        ? const Center(child: CircularProgressIndicator(color: Color(0xFF6D1A95)))
        : _shipments.isEmpty
            ? Center(
                child: Padding(
                  padding: const EdgeInsets.all(24.0),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.local_shipping, size: 48, color: Colors.purple.shade200),
                      const SizedBox(height: 12),
                      const Text('No canonical shipments yet', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                      const SizedBox(height: 6),
                      const Text(
                        'Create a shipment once on the web studio to generate Invoices, Packing Lists, and Weight Statements simultaneously without repetitive data entry.',
                        textAlign: TextAlign.center,
                        style: TextStyle(color: Colors.grey, fontSize: 12),
                      ),
                      const SizedBox(height: 16),
                      ElevatedButton.icon(
                        style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF6D1A95), foregroundColor: Colors.white),
                        icon: const Icon(Icons.open_in_browser, size: 18),
                        label: const Text('Create Shipment on Web'),
                        onPressed: () => _launchWebStudio(),
                      ),
                    ],
                  ),
                ),
              )
            : RefreshIndicator(
                onRefresh: _fetchShipments,
                color: const Color(0xFF6D1A95),
                child: ListView.separated(
                  padding: const EdgeInsets.all(16),
                  itemCount: _shipments.length,
                  separatorBuilder: (c, i) => const SizedBox(height: 12),
                  itemBuilder: (context, index) {
                    final shp = _shipments[index];
                    final refNo = shp['shipment_reference'] ?? shp['reference_number'] ?? 'SHP';
                    final buyer = shp['buyer_name'] ?? 'Buyer N/A';
                    final commodity = shp['commodity'] ?? 'Ethiopia Coffee';
                    final bags = shp['total_bags'] ?? 320;
                    final netKg = shp['total_net_weight_kg'] ?? 19200;
                    final netLbs = shp['total_net_weight_lbs'] ?? 42328.75;
                    final valUsd = shp['total_value_usd'] ?? 0;
                    final ico = shp['ico_number'] ?? 'N/A';
                    final container = shp['container_number'] ?? 'N/A';
                    final shpId = shp['id'];

                    return Card(
                      elevation: 0,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16),
                        side: BorderSide(color: Colors.grey.shade200),
                      ),
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Text(refNo, style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF6D1A95), fontFamily: 'monospace')),
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                                  decoration: BoxDecoration(color: Colors.green.shade50, borderRadius: BorderRadius.circular(6)),
                                  child: Text('\$$valUsd USD', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 11, color: Colors.green.shade800)),
                                ),
                              ],
                            ),
                            const SizedBox(height: 6),
                            Text(commodity, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                            Text('Buyer: $buyer', style: const TextStyle(fontSize: 12, color: Colors.grey)),
                            const SizedBox(height: 2),
                            Text('Container: $container • Net: $netLbs LBS', style: TextStyle(fontSize: 11, color: Colors.grey.shade600)),
                            const Divider(height: 20),
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    const Text('BAGS / NET KG', style: TextStyle(fontSize: 10, color: Colors.grey, fontWeight: FontWeight.bold)),
                                    Text('$bags Bags / $netKg KG', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                                  ],
                                ),
                                Column(
                                  crossAxisAlignment: CrossAxisAlignment.end,
                                  children: [
                                    const Text('ICO MARK', style: TextStyle(fontSize: 10, color: Colors.grey, fontWeight: FontWeight.bold)),
                                    Text(ico, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12, fontFamily: 'monospace')),
                                  ],
                                ),
                              ],
                            ),
                            const SizedBox(height: 12),
                            SizedBox(
                              width: double.infinity,
                              child: ElevatedButton.icon(
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: const Color(0xFF6D1A95),
                                  foregroundColor: Colors.white,
                                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                ),
                                icon: const Icon(Icons.auto_awesome, size: 16, color: Colors.amber),
                                label: const Text('1-Click Generate All Export Docs', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                                onPressed: () => _generateAllForShipment(shpId, refNo),
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

  // ==========================================
  // TAB 3: DETERMINISTIC TRADE CALCULATOR
  // ==========================================
  Widget _buildCalculatorTab(BuildContext context, bool isDark) {
    final netKg = _calculationResult?['net_weight_kg'] ?? 19200.0;
    final tareKg = _calculationResult?['tare_total_kg'] ?? 256.0;
    final grossKg = _calculationResult?['gross_weight_kg'] ?? 19456.0;
    final netLbs = _calculationResult?['net_weight_lbs'] ?? 42328.75;
    final totalUsd = _calculationResult?['total_value_usd'] ?? 110054.75;
    final words = _calculationResult?['total_amount_in_words'] ?? 'ONE HUNDRED TEN THOUSAND FIFTY-FOUR UNITED STATES DOLLARS AND SEVENTY-FIVE CENTS';

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              gradient: const LinearGradient(colors: [Color(0xFF4A0E66), Color(0xFF6D1A95)]),
              borderRadius: BorderRadius.circular(16),
            ),
            child: const Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Icon(Icons.verified, color: Colors.amber, size: 18),
                    SizedBox(width: 8),
                    Text('Deterministic Trade Math', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 15)),
                  ],
                ),
                SizedBox(height: 6),
                Text(
                  'Zero math hallucination. Complies with international coffee conventions: 1 KG = 2.20462262 LBS, 60kg standard jute net weights, and 0.8kg tare deduction.',
                  style: TextStyle(color: Colors.white70, fontSize: 11.5, height: 1.4),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),

          Card(
            elevation: 0,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16), side: BorderSide(color: Colors.grey.shade200)),
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('Enter Parameters', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF6D1A95))),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        child: TextField(
                          controller: _calcBagsController,
                          keyboardType: TextInputType.number,
                          decoration: InputDecoration(
                            labelText: 'Bags (60kg)',
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                            contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                          ),
                          onChanged: (_) => _runCalculator(),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: TextField(
                          controller: _calcPriceController,
                          keyboardType: const TextInputType.numberWithOptions(decimal: true),
                          decoration: InputDecoration(
                            labelText: 'Price USD / LB',
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                            contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                          ),
                          onChanged: (_) => _runCalculator(),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),

          const SizedBox(height: 16),

          // Output Cards
          Card(
            elevation: 0,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16), side: BorderSide(color: Colors.grey.shade200)),
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                children: [
                  _calcRow('Net Weight (KG):', '$netKg KG', isBold: true, color: const Color(0xFF6D1A95)),
                  const Divider(),
                  _calcRow('Tare Deduction (0.8kg):', '$tareKg KG', color: Colors.amber.shade800),
                  const Divider(),
                  _calcRow('Gross Weight (KG):', '$grossKg KG'),
                  const Divider(),
                  _calcRow('Net Weight (LBS):', '$netLbs LBS', isBold: true, color: Colors.green.shade700),
                  const Divider(),
                  _calcRow('Total USD Value:', '\$$totalUsd USD', isBold: true, color: const Color(0xFF6D1A95), size: 16),
                  const SizedBox(height: 12),
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: const Color(0xFF6D1A95).withValues(alpha: 0.05),
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: const Color(0xFF6D1A95).withValues(alpha: 0.2)),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('LEGAL AMOUNT IN WORDS:', style: TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: Color(0xFF6D1A95))),
                        const SizedBox(height: 3),
                        Text(words, style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, height: 1.3)),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _calcRow(String label, String value, {bool isBold = false, Color? color, double size = 13}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: const TextStyle(fontSize: 12, color: Colors.grey)),
          Text(value, style: TextStyle(fontSize: size, fontWeight: isBold ? FontWeight.bold : FontWeight.normal, color: color, fontFamily: 'monospace')),
        ],
      ),
    );
  }

  // ==========================================
  // TAB 4: MY GENERATED DOCUMENTS
  // ==========================================
  Widget _buildDocumentsTab(BuildContext context, bool isDark) {
    return _isLoadingDocuments
        ? const Center(child: CircularProgressIndicator(color: Color(0xFF6D1A95)))
        : _documents.isEmpty
            ? Center(
                child: Padding(
                  padding: const EdgeInsets.all(24.0),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.description_outlined, size: 48, color: Colors.grey.shade400),
                      const SizedBox(height: 12),
                      const Text('No documents generated yet', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                      const SizedBox(height: 6),
                      const Text('Select any template from the Catalog or generate a full suite from Shipments.', textAlign: TextAlign.center, style: TextStyle(color: Colors.grey, fontSize: 12)),
                    ],
                  ),
                ),
              )
            : RefreshIndicator(
                onRefresh: _fetchDocuments,
                color: const Color(0xFF6D1A95),
                child: ListView.separated(
                  padding: const EdgeInsets.all(16),
                  itemCount: _documents.length,
                  separatorBuilder: (c, i) => const SizedBox(height: 12),
                  itemBuilder: (context, index) {
                    final doc = _documents[index];
                    final docId = doc['id'];
                    final docNumber = doc['document_number'] ?? 'DOC';
                    final title = doc['title'] ?? 'Export Document';
                    final status = doc['status'] ?? 'draft';
                    final isFinalized = status == 'finalized';

                    return Card(
                      elevation: 0,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16), side: BorderSide(color: Colors.grey.shade200)),
                      child: InkWell(
                        borderRadius: BorderRadius.circular(16),
                        onTap: () => _openEditor(documentId: docId, templateTitle: title),
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Text(docNumber, style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF6D1A95), fontFamily: 'monospace', fontSize: 12)),
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                                    decoration: BoxDecoration(
                                      color: isFinalized ? Colors.green.shade50 : Colors.amber.shade50,
                                      borderRadius: BorderRadius.circular(6),
                                    ),
                                    child: Text(status.toUpperCase(), style: TextStyle(fontWeight: FontWeight.bold, fontSize: 10, color: isFinalized ? Colors.green.shade800 : Colors.amber.shade900)),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 6),
                              Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                              const SizedBox(height: 12),
                              Row(
                                children: [
                                  Expanded(
                                    child: OutlinedButton.icon(
                                      style: OutlinedButton.styleFrom(
                                        foregroundColor: Colors.red.shade700,
                                        side: BorderSide(color: Colors.red.shade200),
                                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                                      ),
                                      icon: const Icon(Icons.picture_as_pdf, size: 16),
                                      label: const Text('PDF', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                                      onPressed: () => _downloadAndOpenFile(docId, 'pdf', '$docNumber.pdf'),
                                    ),
                                  ),
                                  const SizedBox(width: 8),
                                  Expanded(
                                    child: OutlinedButton.icon(
                                      style: OutlinedButton.styleFrom(
                                        foregroundColor: Colors.green.shade700,
                                        side: BorderSide(color: Colors.green.shade200),
                                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                                      ),
                                      icon: const Icon(Icons.table_view, size: 16),
                                      label: const Text('Excel', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                                      onPressed: () => _downloadAndOpenFile(docId, 'xlsx', '$docNumber.xlsx'),
                                    ),
                                  ),
                                  const SizedBox(width: 8),
                                  IconButton(
                                    icon: const Icon(Icons.edit_note, size: 22, color: Color(0xFF6D1A95)),
                                    tooltip: 'Edit & Preview in Mobile',
                                    onPressed: () => _openEditor(documentId: docId, templateTitle: title),
                                  ),
                                ],
                              ),
                            ],
                          ),
                        ),
                      ),
                    );
                  },
                ),
              );
  }
}

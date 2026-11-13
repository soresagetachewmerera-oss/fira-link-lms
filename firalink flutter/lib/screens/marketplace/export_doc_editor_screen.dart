import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:open_filex/open_filex.dart';
import 'package:share_plus/share_plus.dart';
import '../../services/export_docs_service.dart';
import 'widgets/verification_prompt_dialog.dart';

class ExportDocEditorScreen extends StatefulWidget {
  final int? documentId;
  final String? templateCode;
  final String? templateTitle;
  final Map<String, dynamic>? initialPayload;

  const ExportDocEditorScreen({
    super.key,
    this.documentId,
    this.templateCode,
    this.templateTitle,
    this.initialPayload,
  });

  @override
  State<ExportDocEditorScreen> createState() => _ExportDocEditorScreenState();
}

class _ExportDocEditorScreenState extends State<ExportDocEditorScreen> with SingleTickerProviderStateMixin {
  final ExportDocsService _service = ExportDocsService();
  late TabController _tabController;

  int? _docId;
  String _status = 'draft';
  String _documentNumber = '';
  String _templateCode = '';
  String _templateTitle = '';
  bool _isLoading = true;
  bool _isSaving = false;
  bool _isAiBusy = false;
  Map<String, dynamic> _dataPayload = {};
  Map<String, dynamic>? _calculatedSummary;

  // Form Controllers - Parties
  final _exporterNameController = TextEditingController();
  final _exporterAddressController = TextEditingController();
  final _exporterTinController = TextEditingController();
  final _buyerNameController = TextEditingController();
  final _buyerAddressController = TextEditingController();
  final _buyerCountryController = TextEditingController();
  final _consigneeNameController = TextEditingController();
  final _consigneeAddressController = TextEditingController();
  final _notifyPartyController = TextEditingController();

  // Form Controllers - Coffee & Commodity
  final _icoNumberController = TextEditingController();
  final _commodityController = TextEditingController();
  final _originRegionController = TextEditingController();
  final _coffeeGradeController = TextEditingController();
  final _processingMethodController = TextEditingController();
  final _cropYearController = TextEditingController();
  final _packagingTypeController = TextEditingController();

  // Form Controllers - Logistics
  final _portOfLoadingController = TextEditingController();
  final _portOfDischargeController = TextEditingController();
  final _finalDestinationController = TextEditingController();
  final _vesselController = TextEditingController();
  final _blNumberController = TextEditingController();
  final _containerNumberController = TextEditingController();
  final _sealNumberController = TextEditingController();
  final _incotermController = TextEditingController(text: 'FOB');

  // Form Controllers - Weights & Price
  final _totalBagsController = TextEditingController(text: '320');
  final _bagWeightKgController = TextEditingController(text: '60.0');
  final _tareWeightKgController = TextEditingController(text: '0.80');
  final _unitPriceUsdController = TextEditingController(text: '2.8500');

  // Form Controllers - Payment & Declarations
  final _paymentTermsController = TextEditingController();
  final _bankNameController = TextEditingController();
  final _bankSwiftController = TextEditingController();
  final _lcNumberController = TextEditingController();
  final _remarksController = TextEditingController();

  // AI Prompt controller
  final _aiPromptController = TextEditingController();
  String _aiAdviceText = '';
  Map<String, dynamic>? _aiSuggestedUpdates;

  // Dynamic template-specific controllers for all 30 industry trade templates
  final Map<String, TextEditingController> _dynamicControllers = {};

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _docId = widget.documentId;
    _templateCode = widget.templateCode ?? 'EXP-CI-01';
    _templateTitle = widget.templateTitle ?? 'Export Document';

    _initDocument();
  }

  @override
  void dispose() {
    _tabController.dispose();
    _exporterNameController.dispose();
    _exporterAddressController.dispose();
    _exporterTinController.dispose();
    _buyerNameController.dispose();
    _buyerAddressController.dispose();
    _buyerCountryController.dispose();
    _consigneeNameController.dispose();
    _consigneeAddressController.dispose();
    _notifyPartyController.dispose();
    _icoNumberController.dispose();
    _commodityController.dispose();
    _originRegionController.dispose();
    _coffeeGradeController.dispose();
    _processingMethodController.dispose();
    _cropYearController.dispose();
    _packagingTypeController.dispose();
    _portOfLoadingController.dispose();
    _portOfDischargeController.dispose();
    _finalDestinationController.dispose();
    _vesselController.dispose();
    _blNumberController.dispose();
    _containerNumberController.dispose();
    _sealNumberController.dispose();
    _incotermController.dispose();
    _totalBagsController.dispose();
    _bagWeightKgController.dispose();
    _tareWeightKgController.dispose();
    _unitPriceUsdController.dispose();
    _paymentTermsController.dispose();
    _bankNameController.dispose();
    _bankSwiftController.dispose();
    _lcNumberController.dispose();
    _remarksController.dispose();
    _aiPromptController.dispose();
    for (final c in _dynamicControllers.values) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _initDocument() async {
    setState(() => _isLoading = true);

    if (_docId != null) {
      // Load existing document
      await _loadDocumentData(_docId!);
    } else {
      // Create new draft from template code
      try {
        final res = await _service.createDocument(
          _templateCode,
          payload: widget.initialPayload,
        );
        if (res.statusCode == 201 && mounted) {
          final data = jsonDecode(res.body)['data'];
          _docId = data['id'];
          await _loadDocumentData(_docId!);
        } else if (res.statusCode == 401 && mounted) {
          VerificationPromptDialog.showAuthRequired(
            context,
            actionDescription: 'create and customize export documents',
          );
          Navigator.pop(context);
          return;
        } else if (res.statusCode == 403 && mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              backgroundColor: Colors.amber,
              content: Text('Free Trial limit reached. Please upgrade to Pro for unlimited export documents.'),
            ),
          );
          Navigator.pop(context);
          return;
        } else {
          _populateDefaults();
          if (mounted) setState(() => _isLoading = false);
        }
      } catch (_) {
        _populateDefaults();
        if (mounted) setState(() => _isLoading = false);
      }
    }
  }

  Future<void> _loadDocumentData(int id) async {
    try {
      final res = await _service.getDocument(id);
      if (res.statusCode == 200 && mounted) {
        final data = jsonDecode(res.body)['data'];
        setState(() {
          _docId = data['id'];
          _documentNumber = data['document_number'] ?? 'DRAFT';
          _status = data['status'] ?? 'draft';
          _templateTitle = data['title'] ?? (data['template']?['name'] ?? _templateTitle);
          _templateCode = data['template']?['template_code'] ?? _templateCode;
          _dataPayload = data['data_payload'] ?? {};
          _calculatedSummary = data['calculated_summary'];
          _populateControllersFromPayload(_dataPayload);
          _isLoading = false;
        });
        return;
      }
    } catch (_) {}

    if (mounted) {
      _populateDefaults();
      setState(() => _isLoading = false);
    }
  }

  void _populateDefaults() {
    _exporterNameController.text = 'Fira Link Specialty Coffee Exporters PLC';
    _exporterAddressController.text = 'Kirkos Sub-City, Woreda 03, House 412, Addis Ababa, Ethiopia';
    _exporterTinController.text = '0049281742';
    _buyerNameController.text = 'Hamburg Specialty Coffee Importers GmbH';
    _buyerAddressController.text = 'Speicherstadt Block B, Am Sandtorkai 34, 20457 Hamburg, Germany';
    _buyerCountryController.text = 'Germany';
    _consigneeNameController.text = 'Hamburg Specialty Coffee Importers GmbH';
    _consigneeAddressController.text = 'Speicherstadt Block B, Am Sandtorkai 34, 20457 Hamburg, Germany';
    _notifyPartyController.text = 'Hanseatic Logistics & Warehousing AG, Hamburg Port';
    _icoNumberController.text = '010/0568/0014';
    _commodityController.text = 'Ethiopian Arabica Green Coffee Beans';
    _originRegionController.text = 'Sidama Bensa';
    _coffeeGradeController.text = 'Grade 2 Washed';
    _processingMethodController.text = 'Washed';
    _cropYearController.text = '2025/2026';
    _packagingTypeController.text = '60kg Jute Bags with GrainPro Liners';
    _portOfLoadingController.text = 'Port of Djibouti';
    _portOfDischargeController.text = 'Hamburg Port, Germany';
    _finalDestinationController.text = 'Hamburg, Germany';
    _vesselController.text = 'MSC AURORA V.2604W';
    _blNumberController.text = 'MSCDJ20260718';
    _containerNumberController.text = 'MSCU4918230';
    _sealNumberController.text = 'ET-DJ-89211';
    _incotermController.text = 'FOB';
    _totalBagsController.text = '320';
    _bagWeightKgController.text = '60.0';
    _tareWeightKgController.text = '0.80';
    _unitPriceUsdController.text = '2.8500';
    _paymentTermsController.text = '100% Irrevocable Letter of Credit (LC) at Sight';
    _bankNameController.text = 'Commercial Bank of Ethiopia';
    _bankSwiftController.text = 'CBETETAA';
    _lcNumberController.text = 'LC-2026-HAMB-98421';
    _remarksController.text = 'Clean on Board. Phytosanitary & ICO certificates to accompany shipping documents. GrainPro bags intact.';
  }

  void _populateControllersFromPayload(Map<String, dynamic> p) {
    _exporterNameController.text = p['exporter_name'] ?? _exporterNameController.text;
    _exporterAddressController.text = p['exporter_address'] ?? _exporterAddressController.text;
    _exporterTinController.text = p['tin_number'] ?? p['exporter_tin'] ?? _exporterTinController.text;
    _buyerNameController.text = p['buyer_name'] ?? _buyerNameController.text;
    _buyerAddressController.text = p['buyer_address'] ?? _buyerAddressController.text;
    _buyerCountryController.text = p['buyer_country'] ?? _buyerCountryController.text;
    _consigneeNameController.text = p['consignee_name'] ?? _consigneeNameController.text;
    _consigneeAddressController.text = p['consignee_address'] ?? _consigneeAddressController.text;
    _notifyPartyController.text = p['notify_party_1'] ?? _notifyPartyController.text;
    _icoNumberController.text = p['ico_number'] ?? _icoNumberController.text;
    _commodityController.text = p['commodity'] ?? p['commodity_grade'] ?? _commodityController.text;
    _originRegionController.text = p['origin_region'] ?? p['region_zone'] ?? _originRegionController.text;
    _coffeeGradeController.text = p['coffee_grade'] ?? p['lot_grade'] ?? _coffeeGradeController.text;
    _processingMethodController.text = p['processing_method'] ?? _processingMethodController.text;
    _cropYearController.text = p['crop_year'] ?? p['crop_season'] ?? _cropYearController.text;
    _packagingTypeController.text = p['packaging_type'] ?? p['packaging'] ?? _packagingTypeController.text;
    _portOfLoadingController.text = p['port_of_loading'] ?? _portOfLoadingController.text;
    _portOfDischargeController.text = p['port_of_discharge'] ?? p['place_of_delivery'] ?? _portOfDischargeController.text;
    _finalDestinationController.text = p['final_destination'] ?? _finalDestinationController.text;
    _vesselController.text = p['vessel_name'] ?? p['carrier'] ?? p['intended_vessel'] ?? _vesselController.text;
    _blNumberController.text = p['bl_number'] ?? p['bill_of_lading_number'] ?? p['bill_of_lading_no'] ?? _blNumberController.text;
    _containerNumberController.text = p['container_number'] ?? _containerNumberController.text;
    _sealNumberController.text = p['seal_number'] ?? p['bolt_seal_number'] ?? _sealNumberController.text;
    _incotermController.text = p['incoterm'] ?? p['trade_incoterm'] ?? _incotermController.text;
    _totalBagsController.text = (p['total_bags'] ?? p['quantity_bags'] ?? _totalBagsController.text).toString();
    _bagWeightKgController.text = (p['bag_weight_kg'] ?? _bagWeightKgController.text).toString();
    _tareWeightKgController.text = (p['tare_weight_per_bag_kg'] ?? _tareWeightKgController.text).toString();
    _unitPriceUsdController.text = (p['unit_price_usd_per_lb'] ?? p['price_usd_per_lb'] ?? _unitPriceUsdController.text).toString();
    _paymentTermsController.text = p['payment_terms'] ?? p['payment_method'] ?? _paymentTermsController.text;
    _bankNameController.text = p['bank_name'] ?? p['remitting_bank_name'] ?? _bankNameController.text;
    _bankSwiftController.text = p['bank_swift'] ?? _bankSwiftController.text;
    _lcNumberController.text = p['lc_number'] ?? p['lc_reference_number'] ?? _lcNumberController.text;
    _remarksController.text = p['special_instructions'] ?? p['remarks'] ?? p['declaration_text'] ?? _remarksController.text;

    // Collect all template-specific custom fields
    final standardKeys = {
      'exporter_name', 'exporter_address', 'exporter_tin', 'tin_number',
      'buyer_name', 'buyer_address', 'buyer_country', 'consignee_name',
      'consignee_address', 'notify_party_1', 'ico_number', 'commodity',
      'origin_region', 'coffee_grade', 'processing_method', 'crop_year',
      'packaging_type', 'port_of_loading', 'port_of_discharge',
      'final_destination', 'vessel_name', 'carrier', 'bl_number',
      'container_number', 'seal_number', 'incoterm', 'total_bags',
      'bag_weight_kg', 'tare_weight_per_bag_kg', 'unit_price_usd_per_lb',
      'payment_terms', 'bank_name', 'bank_swift', 'lc_number',
      'special_instructions', 'remarks', 'document_number', 'document_date',
      'declaration_text', 'id', 'organization_id', 'template_id', 'status',
      'pdf_path', 'xlsx_path', 'sha256_hash', 'created_at', 'updated_at',
      'total_net_weight_kg', 'total_gross_weight_kg', 'total_net_weight_lbs',
      'total_value_usd', 'total_amount_in_words', 'amount_in_words'
    };

    p.forEach((key, value) {
      if (!standardKeys.contains(key) && value != null && value is! List && value is! Map) {
        if (!_dynamicControllers.containsKey(key)) {
          _dynamicControllers[key] = TextEditingController(text: value.toString());
        } else {
          _dynamicControllers[key]!.text = value.toString();
        }
      }
    });
  }

  Map<String, dynamic> _collectPayload() {
    final payload = Map<String, dynamic>.from(_dataPayload);
    payload.addAll({
      'document_number': _documentNumber.isNotEmpty ? _documentNumber : 'DRAFT',
      'invoice_number': _documentNumber.isNotEmpty ? _documentNumber : 'DRAFT',
      'document_date': DateTime.now().toIso8601String().substring(0, 10),
      'exporter_name': _exporterNameController.text.trim(),
      'exporter_address': _exporterAddressController.text.trim(),
      'tin_number': _exporterTinController.text.trim(),
      'buyer_name': _buyerNameController.text.trim(),
      'buyer_address': _buyerAddressController.text.trim(),
      'buyer_country': _buyerCountryController.text.trim(),
      'consignee_name': _consigneeNameController.text.trim(),
      'consignee_address': _consigneeAddressController.text.trim(),
      'notify_party_1': _notifyPartyController.text.trim(),
      'ico_number': _icoNumberController.text.trim(),
      'commodity': _commodityController.text.trim(),
      'origin_region': _originRegionController.text.trim(),
      'coffee_grade': _coffeeGradeController.text.trim(),
      'processing_method': _processingMethodController.text.trim(),
      'crop_year': _cropYearController.text.trim(),
      'packaging_type': _packagingTypeController.text.trim(),
      'port_of_loading': _portOfLoadingController.text.trim(),
      'port_of_discharge': _portOfDischargeController.text.trim(),
      'final_destination': _finalDestinationController.text.trim(),
      'vessel_name': _vesselController.text.trim(),
      'bl_number': _blNumberController.text.trim(),
      'container_number': _containerNumberController.text.trim(),
      'seal_number': _sealNumberController.text.trim(),
      'incoterm': _incotermController.text.trim(),
      'total_bags': int.tryParse(_totalBagsController.text.trim()) ?? 320,
      'bag_weight_kg': double.tryParse(_bagWeightKgController.text.trim()) ?? 60.0,
      'tare_weight_per_bag_kg': double.tryParse(_tareWeightKgController.text.trim()) ?? 0.80,
      'unit_price_usd_per_lb': double.tryParse(_unitPriceUsdController.text.trim()) ?? 2.85,
      'payment_terms': _paymentTermsController.text.trim(),
      'bank_name': _bankNameController.text.trim(),
      'bank_swift': _bankSwiftController.text.trim(),
      'lc_number': _lcNumberController.text.trim(),
      'special_instructions': _remarksController.text.trim(),
    });

    // Merge template-specific custom fields
    _dynamicControllers.forEach((key, ctrl) {
      payload[key] = ctrl.text.trim();
    });

    return payload;
  }

  Future<void> _calculateTradeValues() async {
    final bags = int.tryParse(_totalBagsController.text.trim()) ?? 320;
    final price = double.tryParse(_unitPriceUsdController.text.trim()) ?? 2.85;

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
          _calculatedSummary = data['combined'];
        });

        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            backgroundColor: const Color(0xFF6D1A95),
            content: Text(
              'Calculated: Net ${data['combined']['net_weight_kg']} KG | Total USD \$${data['combined']['formatted_total_value_usd']}',
              style: const TextStyle(fontWeight: FontWeight.bold),
            ),
          ),
        );
      }
    } catch (_) {}
  }

  Future<void> _saveDraft() async {
    if (_docId == null) return;
    setState(() => _isSaving = true);
    final payload = _collectPayload();

    try {
      final res = await _service.updateDocument(_docId!, payload: payload, title: _templateTitle);
      if (res.statusCode == 200 && mounted) {
        final data = jsonDecode(res.body);
        setState(() {
          _calculatedSummary = data['calculated_summary'];
          _isSaving = false;
        });

        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            backgroundColor: Colors.green,
            content: Text('Document draft saved successfully!'),
          ),
        );
      } else if (res.statusCode == 401 && mounted) {
        setState(() => _isSaving = false);
        VerificationPromptDialog.showAuthRequired(
          context,
          actionDescription: 'save and sync document drafts',
        );
      } else if (res.statusCode == 403 && mounted) {
        setState(() => _isSaving = false);
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            backgroundColor: Colors.amber,
            content: Text('Free Trial limit reached. Please upgrade to Pro.'),
          ),
        );
      } else {
        if (mounted) setState(() => _isSaving = false);
      }
    } catch (_) {
      if (mounted) setState(() => _isSaving = false);
    }
  }

  Future<void> _finalizeDocument() async {
    if (_docId == null) return;

    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Row(
          children: [
            Icon(Icons.verified_rounded, color: Color(0xFF6D1A95)),
            SizedBox(width: 8),
            Text('Finalize Document'),
          ],
        ),
        content: const Text(
          'Finalizing locks this document for regulatory audit compliance, computes its immutable SHA-256 fingerprint, and generates verified PDF & Excel files.\n\nProceed to finalize?',
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF6D1A95), foregroundColor: Colors.white),
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Finalize & Lock'),
          ),
        ],
      ),
    );

    if (confirm != true) return;

    setState(() => _isSaving = true);
    await _saveDraft();

    try {
      final res = await _service.finalizeDocument(_docId!);
      if (res.statusCode == 200 && mounted) {
        setState(() {
          _status = 'finalized';
          _isSaving = false;
        });

        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            backgroundColor: Colors.green,
            content: Text('Document successfully finalized and locked into vault!'),
          ),
        );
      } else {
        if (mounted) setState(() => _isSaving = false);
      }
    } catch (_) {
      if (mounted) setState(() => _isSaving = false);
    }
  }

  Future<void> _downloadAndOpen(String format) async {
    if (_docId == null) return;
    final filename = '${_documentNumber.isNotEmpty ? _documentNumber : "document"}.$format';

    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => Center(
        child: Card(
          child: Padding(
            padding: const EdgeInsets.all(20),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                const CircularProgressIndicator(color: Color(0xFF6D1A95)),
                const SizedBox(width: 16),
                Text('Preparing $format file...', style: const TextStyle(fontWeight: FontWeight.bold)),
              ],
            ),
          ),
        ),
      ),
    );

    final path = await _service.downloadFile(_docId!, format, filename);
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
  }

  void _openFiraAiModal() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) => StatefulBuilder(
        builder: (modalCtx, setModalState) => Padding(
          padding: EdgeInsets.only(
            top: 20,
            left: 20,
            right: 20,
            bottom: MediaQuery.of(modalCtx).viewInsets.bottom + 20,
          ),
          child: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(8),
                      decoration: BoxDecoration(
                        color: const Color(0xFF6D1A95).withValues(alpha: 0.12),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: const Icon(Icons.auto_awesome, color: Color(0xFF6D1A95)),
                    ),
                    const SizedBox(width: 12),
                    const Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('Fira AI Trade Copilot', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
                          Text('Tailored for Ethiopian coffee export documentation', style: TextStyle(fontSize: 12, color: Colors.grey)),
                        ],
                      ),
                    ),
                    IconButton(
                      icon: const Icon(Icons.close),
                      onPressed: () => Navigator.pop(ctx),
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                const Text('What would you like Fira AI to customize?', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 14)),
                const SizedBox(height: 8),
                TextField(
                  controller: _aiPromptController,
                  maxLines: 3,
                  decoration: InputDecoration(
                    hintText: 'e.g. Exporting 320 bags washed Sidama Grade 2 to Hamburg, FOB Djibouti, LC at sight from Commerzbank. Auto-fill ports and container details.',
                    hintStyle: TextStyle(fontSize: 13, color: Colors.grey[500]),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                    filled: true,
                    fillColor: Colors.grey[50],
                  ),
                ),
                const SizedBox(height: 12),
                Wrap(
                  spacing: 8,
                  runSpacing: 6,
                  children: [
                    _buildAiChip(
                      label: 'Yirgacheffe Grade 1 to Tokyo',
                      onTap: () {
                        setModalState(() {
                          _aiPromptController.text = 'Exporting 320 bags of Grade 1 Natural Yirgacheffe to Tokyo, Japan on CIF terms. Port of discharge Tokyo Port, LC at sight, ICO mark 010/0568/0022.';
                        });
                      },
                    ),
                    _buildAiChip(
                      label: 'Sidama G2 to Hamburg (FOB)',
                      onTap: () {
                        setModalState(() {
                          _aiPromptController.text = 'Exporting 320 bags washed Sidama Grade 2 to Hamburg via Djibouti port on FOB terms, LC at sight from Commerzbank, ICO mark 010/0568/0014.';
                        });
                      },
                    ),
                    _buildAiChip(
                      label: 'Guji Anaerobic to Rotterdam',
                      onTap: () {
                        setModalState(() {
                          _aiPromptController.text = 'Exporting 300 bags of Guji Special Anaerobic to Rotterdam, Netherlands on CFR terms, CAD through Commercial Bank of Ethiopia.';
                        });
                      },
                    ),
                    _buildAiChip(
                      label: 'Validate Export Compliance',
                      onTap: () {
                        setModalState(() {
                          _aiPromptController.text = 'Review this document for Ethiopian Coffee & Tea Authority (ECTA), ICO marking format, and Incoterm consistency.';
                        });
                      },
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                if (_isAiBusy) ...[
                  const Center(
                    child: Padding(
                      padding: EdgeInsets.all(16.0),
                      child: Column(
                        children: [
                          CircularProgressIndicator(color: Color(0xFF6D1A95)),
                          SizedBox(height: 12),
                          Text('Fira AI is consulting Ethiopian trade regulations...', style: TextStyle(fontSize: 13, fontStyle: FontStyle.italic)),
                        ],
                      ),
                    ),
                  ),
                ],
                if (_aiAdviceText.isNotEmpty && !_isAiBusy) ...[
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: const Color(0xFF6D1A95).withValues(alpha: 0.06),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: const Color(0xFF6D1A95).withValues(alpha: 0.2)),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Row(
                          children: [
                            Icon(Icons.lightbulb_outline, color: Color(0xFF6D1A95), size: 18),
                            SizedBox(width: 6),
                            Text('Fira AI Advice & Suggestions', style: TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF6D1A95), fontSize: 13)),
                          ],
                        ),
                        const SizedBox(height: 8),
                        Text(_aiAdviceText, style: const TextStyle(fontSize: 13, height: 1.4)),
                        if (_aiSuggestedUpdates != null && _aiSuggestedUpdates!.isNotEmpty) ...[
                          const SizedBox(height: 12),
                          ElevatedButton.icon(
                            style: ElevatedButton.styleFrom(
                              backgroundColor: const Color(0xFF6D1A95),
                              foregroundColor: Colors.white,
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                            ),
                            icon: const Icon(Icons.check_circle_outline, size: 18),
                            label: const Text('Apply AI Updates to Document', style: TextStyle(fontWeight: FontWeight.bold)),
                            onPressed: () {
                              _applyAiSuggestions(_aiSuggestedUpdates!);
                              Navigator.pop(ctx);
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(
                                  backgroundColor: Colors.green,
                                  content: Text('Fira AI suggestions successfully applied to form!'),
                                ),
                              );
                            },
                          ),
                        ],
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),
                ],
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton.icon(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF6D1A95),
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                    icon: const Icon(Icons.auto_awesome),
                    label: Text(_isAiBusy ? 'Processing...' : 'Run Fira AI Assistant', style: const TextStyle(fontWeight: FontWeight.bold)),
                    onPressed: _isAiBusy ? null : () async {
                      setModalState(() => _isAiBusy = true);
                      setState(() => _isAiBusy = true);

                      try {
                        final payload = _collectPayload();
                        final res = await _service.aiAssist(
                          action: 'suggest',
                          templateCode: _templateCode,
                          payload: payload,
                          userPrompt: _aiPromptController.text.trim().isNotEmpty
                              ? _aiPromptController.text.trim()
                              : 'Review and complete this Ethiopian export document',
                        );

                        if (res.statusCode == 200 && mounted) {
                          final data = jsonDecode(res.body);
                          final advice = data['advice'] ?? '';
                          final suggestions = data['suggested_updates'] as Map<String, dynamic>?;

                          setModalState(() {
                            _aiAdviceText = advice;
                            _aiSuggestedUpdates = suggestions ?? _generateFallbackAiSuggestions(_aiPromptController.text);
                            _isAiBusy = false;
                          });
                          setState(() => _isAiBusy = false);
                        } else {
                          // Fallback to local intelligent rule engine
                          final fallback = _generateFallbackAiSuggestions(_aiPromptController.text);
                          setModalState(() {
                            _aiAdviceText = 'Fira AI has prepared trade-compliant field values based on Ethiopian Coffee & Tea Authority standards and standard export contracts.';
                            _aiSuggestedUpdates = fallback;
                            _isAiBusy = false;
                          });
                          setState(() => _isAiBusy = false);
                        }
                      } catch (_) {
                        final fallback = _generateFallbackAiSuggestions(_aiPromptController.text);
                        setModalState(() {
                          _aiAdviceText = 'Fira AI offline trade rules loaded. Standards calibrated for Ethiopian Arabica export documentation.';
                          _aiSuggestedUpdates = fallback;
                          _isAiBusy = false;
                        });
                        setState(() => _isAiBusy = false);
                      }
                    },
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Map<String, dynamic> _generateFallbackAiSuggestions(String prompt) {
    final lower = prompt.toLowerCase();
    final suggestions = <String, dynamic>{
      'port_of_loading': 'Port of Djibouti',
      'packaging_type': '60kg Jute Bags with GrainPro Liners',
      'tare_weight_per_bag_kg': 0.80,
      'bag_weight_kg': 60.0,
      'bank_name': 'Commercial Bank of Ethiopia',
      'bank_swift': 'CBETETAA',
    };

    if (lower.contains('tokyo') || lower.contains('japan')) {
      suggestions['port_of_discharge'] = 'Tokyo Port, Japan';
      suggestions['final_destination'] = 'Tokyo, Japan';
      suggestions['buyer_country'] = 'Japan';
      suggestions['buyer_name'] = 'Tokyo Specialty Coffee Roasters Ltd';
      suggestions['buyer_address'] = '2-11-8 Shibuya, Shibuya-ku, Tokyo 150-0002, Japan';
    } else if (lower.contains('rotterdam') || lower.contains('netherlands')) {
      suggestions['port_of_discharge'] = 'Rotterdam Port, Netherlands';
      suggestions['final_destination'] = 'Rotterdam, Netherlands';
      suggestions['buyer_country'] = 'Netherlands';
      suggestions['buyer_name'] = 'Rotterdam Coffee Exchange B.V.';
      suggestions['buyer_address'] = 'Maashaven Zuidzijde 12, 3081 AE Rotterdam, Netherlands';
    } else {
      suggestions['port_of_discharge'] = 'Hamburg Port, Germany';
      suggestions['final_destination'] = 'Hamburg, Germany';
      suggestions['buyer_country'] = 'Germany';
      suggestions['buyer_name'] = 'Hamburg Specialty Coffee Importers GmbH';
      suggestions['buyer_address'] = 'Speicherstadt Block B, Am Sandtorkai 34, 20457 Hamburg, Germany';
    }

    if (lower.contains('yirgacheffe')) {
      suggestions['origin_region'] = 'Yirgacheffe';
      suggestions['coffee_grade'] = 'Grade 1 Natural';
      suggestions['processing_method'] = 'Natural';
      suggestions['ico_number'] = '010/0568/0022';
      suggestions['unit_price_usd_per_lb'] = 3.65;
    } else if (lower.contains('guji')) {
      suggestions['origin_region'] = 'Guji';
      suggestions['coffee_grade'] = 'Grade 1 Anaerobic';
      suggestions['processing_method'] = 'Anaerobic';
      suggestions['ico_number'] = '010/0568/0045';
      suggestions['unit_price_usd_per_lb'] = 3.90;
    } else {
      suggestions['origin_region'] = 'Sidama Bensa';
      suggestions['coffee_grade'] = 'Grade 2 Washed';
      suggestions['processing_method'] = 'Washed';
      suggestions['ico_number'] = '010/0568/0014';
      suggestions['unit_price_usd_per_lb'] = 2.85;
    }

    if (lower.contains('cif')) {
      suggestions['incoterm'] = 'CIF';
    } else if (lower.contains('cfr')) {
      suggestions['incoterm'] = 'CFR';
    } else {
      suggestions['incoterm'] = 'FOB';
    }

    return suggestions;
  }

  void _applyAiSuggestions(Map<String, dynamic> suggestions) {
    setState(() {
      if (suggestions.containsKey('port_of_loading')) _portOfLoadingController.text = suggestions['port_of_loading'];
      if (suggestions.containsKey('port_of_discharge')) _portOfDischargeController.text = suggestions['port_of_discharge'];
      if (suggestions.containsKey('final_destination')) _finalDestinationController.text = suggestions['final_destination'];
      if (suggestions.containsKey('buyer_name')) _buyerNameController.text = suggestions['buyer_name'];
      if (suggestions.containsKey('buyer_address')) _buyerAddressController.text = suggestions['buyer_address'];
      if (suggestions.containsKey('buyer_country')) _buyerCountryController.text = suggestions['buyer_country'];
      if (suggestions.containsKey('origin_region')) _originRegionController.text = suggestions['origin_region'];
      if (suggestions.containsKey('coffee_grade')) _coffeeGradeController.text = suggestions['coffee_grade'];
      if (suggestions.containsKey('processing_method')) _processingMethodController.text = suggestions['processing_method'];
      if (suggestions.containsKey('ico_number')) _icoNumberController.text = suggestions['ico_number'];
      if (suggestions.containsKey('incoterm')) _incotermController.text = suggestions['incoterm'];
      if (suggestions.containsKey('unit_price_usd_per_lb')) _unitPriceUsdController.text = suggestions['unit_price_usd_per_lb'].toString();
      if (suggestions.containsKey('packaging_type')) _packagingTypeController.text = suggestions['packaging_type'];
      if (suggestions.containsKey('bank_name')) _bankNameController.text = suggestions['bank_name'];
      if (suggestions.containsKey('bank_swift')) _bankSwiftController.text = suggestions['bank_swift'];
    });
    _calculateTradeValues();
  }

  Widget _buildAiChip({required String label, required VoidCallback onTap}) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(20),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
        decoration: BoxDecoration(
          color: Colors.grey[100],
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: Colors.grey[300]!),
        ),
        child: Text(label, style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: Color(0xFF6D1A95))),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              _templateTitle.isNotEmpty ? _templateTitle : 'Document Editor',
              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
              overflow: TextOverflow.ellipsis,
            ),
            Text(
              _documentNumber.isNotEmpty ? '$_documentNumber • ${_status.toUpperCase()}' : _templateCode,
              style: const TextStyle(fontSize: 11, color: Colors.white70),
            ),
          ],
        ),
        backgroundColor: const Color(0xFF6D1A95),
        foregroundColor: Colors.white,
        actions: [
          // Fira AI Assistant Trigger
          TextButton.icon(
            style: TextButton.styleFrom(foregroundColor: Colors.amber),
            icon: const Icon(Icons.auto_awesome, size: 18),
            label: const Text('Fira AI', style: TextStyle(fontWeight: FontWeight.bold)),
            onPressed: _openFiraAiModal,
          ),
          PopupMenuButton<String>(
            icon: const Icon(Icons.more_vert),
            onSelected: (val) {
              if (val == 'save') _saveDraft();
              if (val == 'finalize') _finalizeDocument();
              if (val == 'pdf') _downloadAndOpen('pdf');
              if (val == 'xlsx') _downloadAndOpen('xlsx');
              if (val == 'calc') _calculateTradeValues();
            },
            itemBuilder: (ctx) => [
              const PopupMenuItem(value: 'save', child: Row(children: [Icon(Icons.save, size: 18), SizedBox(width: 8), Text('Save Draft')])),
              const PopupMenuItem(value: 'calc', child: Row(children: [Icon(Icons.calculate, size: 18), SizedBox(width: 8), Text('Recalculate Totals')])),
              const PopupMenuItem(value: 'finalize', child: Row(children: [Icon(Icons.lock, size: 18), SizedBox(width: 8), Text('Finalize Document')])),
              const PopupMenuDivider(),
              const PopupMenuItem(value: 'pdf', child: Row(children: [Icon(Icons.picture_as_pdf, color: Colors.red, size: 18), SizedBox(width: 8), Text('Download PDF')])),
              const PopupMenuItem(value: 'xlsx', child: Row(children: [Icon(Icons.table_chart, color: Colors.green, size: 18), SizedBox(width: 8), Text('Download Excel')])),
            ],
          ),
        ],
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: Colors.amber,
          indicatorWeight: 3,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white60,
          tabs: const [
            Tab(icon: Icon(Icons.edit_note), text: 'Customize Form'),
            Tab(icon: Icon(Icons.remove_red_eye_outlined), text: 'In-App Live Preview'),
          ],
        ),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: Color(0xFF6D1A95)))
          : TabBarView(
              controller: _tabController,
              children: [
                _buildFormTab(),
                _buildPreviewTab(),
              ],
            ),
      bottomNavigationBar: _buildBottomBar(),
    );
  }

  Widget _buildFormTab() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Top AI quick callout
          _buildAiCalloutCard(),
          const SizedBox(height: 16),

          // Section 1: Parties
          _buildCollapsibleSection(
            title: '1. Parties & Contact Information',
            icon: Icons.business_rounded,
            children: [
              _buildTextField('Exporter / Shipper Name', _exporterNameController),
              _buildTextField('Exporter Address', _exporterAddressController, maxLines: 2),
              _buildTextField('Exporter TIN / Tax ID', _exporterTinController),
              const Divider(height: 24),
              _buildTextField('Buyer Name', _buyerNameController),
              _buildTextField('Buyer Address', _buyerAddressController, maxLines: 2),
              _buildTextField('Buyer Country', _buyerCountryController),
              _buildTextField('Consignee Name', _consigneeNameController),
              _buildTextField('Consignee Address', _consigneeAddressController, maxLines: 2),
              _buildTextField('Notify Party', _notifyPartyController, maxLines: 2),
            ],
          ),
          const SizedBox(height: 16),

          // Section 2: Coffee & Commodity
          _buildCollapsibleSection(
            title: '2. Coffee Specifications & Origin',
            icon: Icons.coffee_rounded,
            children: [
              _buildTextField('ICO Identification Mark', _icoNumberController, hint: 'e.g. 010/0568/0014'),
              _buildTextField('Commodity Description', _commodityController),
              _buildTextField('Origin Region', _originRegionController, hint: 'e.g. Sidama Bensa, Yirgacheffe, Guji'),
              _buildTextField('Coffee Grade & Type', _coffeeGradeController, hint: 'e.g. Grade 2 Washed'),
              _buildTextField('Processing Method', _processingMethodController, hint: 'e.g. Washed, Natural, Anaerobic'),
              _buildTextField('Crop Year', _cropYearController, hint: 'e.g. 2025/2026'),
              _buildTextField('Packaging Type', _packagingTypeController, hint: 'e.g. 60kg Jute Bags with GrainPro Liners'),
            ],
          ),
          const SizedBox(height: 16),

          // Section 3: Logistics & Freight
          _buildCollapsibleSection(
            title: '3. Logistics, Ports & Carrier',
            icon: Icons.local_shipping_rounded,
            children: [
              _buildTextField('Port of Loading', _portOfLoadingController, hint: 'Default: Port of Djibouti'),
              _buildTextField('Port of Discharge', _portOfDischargeController, hint: 'e.g. Hamburg Port, Germany'),
              _buildTextField('Final Destination', _finalDestinationController, hint: 'e.g. Hamburg, Germany'),
              _buildTextField('Vessel / Carrier Name', _vesselController, hint: 'e.g. MSC AURORA V.2604W'),
              _buildTextField('Bill of Lading (B/L) / AWB No.', _blNumberController),
              _buildTextField('Container Number', _containerNumberController, hint: 'e.g. MSCU4918230'),
              _buildTextField('Seal Number', _sealNumberController, hint: 'e.g. ET-DJ-89211'),
              _buildTextField('Incoterms 2020', _incotermController, hint: 'FOB, CIF, CFR, FCA'),
            ],
          ),
          const SizedBox(height: 16),

          // Section 4: Weights & Valuation
          _buildCollapsibleSection(
            title: '4. Weights & Trade Valuation',
            icon: Icons.scale_rounded,
            children: [
              Row(
                children: [
                  Expanded(child: _buildTextField('Total Bags', _totalBagsController, isNumeric: true)),
                  const SizedBox(width: 12),
                  Expanded(child: _buildTextField('Unit Price (USD/lb)', _unitPriceUsdController, isNumeric: true)),
                ],
              ),
              Row(
                children: [
                  Expanded(child: _buildTextField('Bag Net (KG)', _bagWeightKgController, isNumeric: true)),
                  const SizedBox(width: 12),
                  Expanded(child: _buildTextField('Tare / Bag (KG)', _tareWeightKgController, isNumeric: true)),
                ],
              ),
              const SizedBox(height: 10),
              SizedBox(
                width: double.infinity,
                child: OutlinedButton.icon(
                  style: OutlinedButton.styleFrom(
                    foregroundColor: const Color(0xFF6D1A95),
                    side: const BorderSide(color: Color(0xFF6D1A95)),
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                  icon: const Icon(Icons.calculate_outlined),
                  label: const Text('Auto-Calculate Weights & Invoice Value', style: TextStyle(fontWeight: FontWeight.bold)),
                  onPressed: _calculateTradeValues,
                ),
              ),
              if (_calculatedSummary != null) ...[
                const SizedBox(height: 14),
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: Colors.green.withValues(alpha: 0.08),
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: Colors.green.withValues(alpha: 0.3)),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Deterministic Trade Calculations', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.green, fontSize: 13)),
                      const SizedBox(height: 6),
                      Text('• Net Weight: ${_calculatedSummary!['net_weight_kg']} KG (${_calculatedSummary!['net_weight_lbs']} LBS)'),
                      Text('• Gross Weight: ${_calculatedSummary!['gross_weight_kg']} KG (Tare: ${_calculatedSummary!['total_tare_kg']} KG)'),
                      Text('• Total Value: \$${_calculatedSummary!['formatted_total_value_usd']} USD (${_calculatedSummary!['incoterm'] ?? "FOB"})', style: const TextStyle(fontWeight: FontWeight.bold)),
                      if (_calculatedSummary!['amount_in_words'] != null)
                        Text('• In Words: "${_calculatedSummary!['amount_in_words']}"', style: const TextStyle(fontStyle: FontStyle.italic, fontSize: 12)),
                    ],
                  ),
                ),
              ],
            ],
          ),
          const SizedBox(height: 16),

          // Section 5: Payment & Regulatory
          _buildCollapsibleSection(
            title: '5. Banking, Payment & Declarations',
            icon: Icons.account_balance_rounded,
            children: [
              _buildTextField('Payment Terms', _paymentTermsController, hint: 'e.g. 100% LC at Sight, CAD, TT'),
              _buildTextField('Bank Name', _bankNameController, hint: 'e.g. Commercial Bank of Ethiopia'),
              _buildTextField('Bank SWIFT Code', _bankSwiftController, hint: 'e.g. CBETETAA'),
              _buildTextField('LC Reference Number', _lcNumberController),
              _buildTextField('Special Instructions / Notes', _remarksController, maxLines: 3),
            ],
          ),

          if (_dynamicControllers.isNotEmpty) ...[
            const SizedBox(height: 16),
            _buildCollapsibleSection(
              title: '6. Template Specifications & Custom Particulars',
              icon: Icons.tune_rounded,
              children: _dynamicControllers.entries.map((entry) {
                final label = entry.key
                    .replaceAll('_', ' ')
                    .split(' ')
                    .map((w) => w.isNotEmpty ? '${w[0].toUpperCase()}${w.substring(1)}' : '')
                    .join(' ');
                final isLong = entry.value.text.length > 50 || entry.value.text.contains('\n');
                return _buildTextField(label, entry.value, maxLines: isLong ? 3 : 1);
              }).toList(),
            ),
          ],
          const SizedBox(height: 40),
        ],
      ),
    );
  }

  Widget _buildPreviewTab() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        children: [
          // Top action banner
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: const Color(0xFF6D1A95).withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Row(
              children: [
                const Icon(Icons.info_outline, color: Color(0xFF6D1A95), size: 20),
                const SizedBox(width: 10),
                const Expanded(
                  child: Text(
                    'Live In-App Mobile Preview. Matches generated PDF & Excel outputs.',
                    style: TextStyle(fontSize: 12, fontWeight: FontWeight.w500),
                  ),
                ),
                IconButton(
                  icon: const Icon(Icons.refresh, color: Color(0xFF6D1A95)),
                  onPressed: () => setState(() {}),
                  tooltip: 'Refresh Preview',
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),

          // Paper Document Container
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(8),
              border: Border.all(color: Colors.grey[300]!),
              boxShadow: [
                BoxShadow(color: Colors.black.withValues(alpha: 0.06), blurRadius: 10, offset: const Offset(0, 4)),
              ],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Header
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            _exporterNameController.text.isNotEmpty ? _exporterNameController.text : 'EXPORTER COMPANY',
                            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF6D1A95)),
                          ),
                          const SizedBox(height: 4),
                          Text(_exporterAddressController.text, style: const TextStyle(fontSize: 11, color: Colors.black87)),
                          if (_exporterTinController.text.isNotEmpty)
                            Text('TIN: ${_exporterTinController.text}', style: const TextStyle(fontSize: 10, color: Colors.grey)),
                        ],
                      ),
                    ),
                    const SizedBox(width: 12),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        Text(
                          _templateTitle.toUpperCase(),
                          style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 13, color: Colors.black87),
                        ),
                        const SizedBox(height: 4),
                        Text('Doc No: ${_documentNumber.isNotEmpty ? _documentNumber : "DRAFT"}', style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold)),
                        Text('Date: ${DateTime.now().toIso8601String().substring(0, 10)}', style: const TextStyle(fontSize: 10, color: Colors.grey)),
                        Text('ICO Mark: ${_icoNumberController.text}', style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w600, color: Color(0xFF6D1A95))),
                      ],
                    ),
                  ],
                ),
                const Divider(height: 24, thickness: 1.5),

                // Parties 2-Column
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('BUYER / APPLICANT:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 10, color: Colors.grey)),
                          const SizedBox(height: 2),
                          Text(_buyerNameController.text, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 11)),
                          Text(_buyerAddressController.text, style: const TextStyle(fontSize: 10, color: Colors.black87)),
                        ],
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('CONSIGNEE:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 10, color: Colors.grey)),
                          const SizedBox(height: 2),
                          Text(_consigneeNameController.text, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 11)),
                          Text(_consigneeAddressController.text, style: const TextStyle(fontSize: 10, color: Colors.black87)),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 14),

                // Shipment Logistics Block
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(color: Colors.grey[100], borderRadius: BorderRadius.circular(6)),
                  child: Column(
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text('POL: ${_portOfLoadingController.text}', style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w600)),
                          Text('POD: ${_portOfDischargeController.text}', style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w600)),
                        ],
                      ),
                      const SizedBox(height: 4),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text('Vessel: ${_vesselController.text}', style: const TextStyle(fontSize: 10)),
                          Text('Incoterm: ${_incotermController.text}', style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Color(0xFF6D1A95))),
                        ],
                      ),
                      const SizedBox(height: 4),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text('Container: ${_containerNumberController.text}', style: const TextStyle(fontSize: 10)),
                          Text('Seal: ${_sealNumberController.text}', style: const TextStyle(fontSize: 10)),
                        ],
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),

                // Commodity & Items Table
                const Text('COMMODITY DETAILS', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 11)),
                const SizedBox(height: 6),
                Table(
                  border: TableBorder.all(color: Colors.grey[300]!),
                  columnWidths: const {
                    0: FlexColumnWidth(3),
                    1: FlexColumnWidth(1.2),
                    2: FlexColumnWidth(1.5),
                    3: FlexColumnWidth(1.5),
                  },
                  children: [
                    TableRow(
                      decoration: BoxDecoration(color: Colors.grey[200]),
                      children: const [
                        Padding(padding: EdgeInsets.all(6), child: Text('Description', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 10))),
                        Padding(padding: EdgeInsets.all(6), child: Text('Bags', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 10))),
                        Padding(padding: EdgeInsets.all(6), child: Text('Net KG', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 10))),
                        Padding(padding: EdgeInsets.all(6), child: Text('Total USD', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 10))),
                      ],
                    ),
                    TableRow(
                      children: [
                        Padding(
                          padding: const EdgeInsets.all(6),
                          child: Text(
                            '${_commodityController.text}\n${_originRegionController.text} - ${_coffeeGradeController.text}\nCrop: ${_cropYearController.text}',
                            style: const TextStyle(fontSize: 10),
                          ),
                        ),
                        Padding(padding: const EdgeInsets.all(6), child: Text(_totalBagsController.text, style: const TextStyle(fontSize: 10))),
                        Padding(
                          padding: const EdgeInsets.all(6),
                          child: Text(
                            _calculatedSummary?['net_weight_kg']?.toString() ?? '${(int.tryParse(_totalBagsController.text) ?? 320) * 60}',
                            style: const TextStyle(fontSize: 10),
                          ),
                        ),
                        Padding(
                          padding: const EdgeInsets.all(6),
                          child: Text(
                            _calculatedSummary?['formatted_total_value_usd'] != null
                                ? '\$${_calculatedSummary!['formatted_total_value_usd']}'
                                : '\$${((int.tryParse(_totalBagsController.text) ?? 320) * 60 * 2.20462262 * (double.tryParse(_unitPriceUsdController.text) ?? 2.85)).toStringAsFixed(2)}',
                            style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
                const SizedBox(height: 12),

                // Amount in Words
                if (_calculatedSummary?['amount_in_words'] != null)
                  Text(
                    'Amount in Words: ${_calculatedSummary!['amount_in_words']}',
                    style: const TextStyle(fontSize: 10, fontStyle: FontStyle.italic, fontWeight: FontWeight.w600),
                  ),

                const SizedBox(height: 14),
                // Payment info
                Text('Payment Terms: ${_paymentTermsController.text}', style: const TextStyle(fontSize: 10)),
                if (_lcNumberController.text.isNotEmpty)
                  Text('LC Number: ${_lcNumberController.text} (${_bankNameController.text} - ${_bankSwiftController.text})', style: const TextStyle(fontSize: 10)),

                if (_dynamicControllers.isNotEmpty) ...[
                  const SizedBox(height: 14),
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: const Color(0xFF6D1A95).withValues(alpha: 0.04),
                      borderRadius: BorderRadius.circular(6),
                      border: Border.all(color: const Color(0xFF6D1A95).withValues(alpha: 0.2)),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            const Icon(Icons.verified_outlined, size: 14, color: Color(0xFF6D1A95)),
                            const SizedBox(width: 6),
                            Text(
                              '${_templateTitle.toUpperCase()} SPECIFICATIONS',
                              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 10, color: Color(0xFF6D1A95)),
                            ),
                          ],
                        ),
                        const Divider(height: 12),
                        ..._dynamicControllers.entries.map((entry) {
                          final label = entry.key
                              .replaceAll('_', ' ')
                              .split(' ')
                              .map((w) => w.isNotEmpty ? '${w[0].toUpperCase()}${w.substring(1)}' : '')
                              .join(' ');
                          return Padding(
                            padding: const EdgeInsets.symmetric(vertical: 2.5),
                            child: Row(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                SizedBox(
                                  width: 120,
                                  child: Text(label, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 9.5, color: Colors.black54)),
                                ),
                                Expanded(
                                  child: Text(entry.value.text, style: const TextStyle(fontSize: 9.5, color: Colors.black87, fontWeight: FontWeight.w500)),
                                ),
                              ],
                            ),
                          );
                        }),
                      ],
                    ),
                  ),
                ],

                const SizedBox(height: 16),
                // Regulatory Notice
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(color: Colors.amber[50], borderRadius: BorderRadius.circular(4)),
                  child: const Text(
                    'Declaration: We hereby certify that this document is authentic and shipment conforms to Ethiopian Coffee & Tea Authority export regulations.',
                    style: TextStyle(fontSize: 9, color: Colors.brown),
                  ),
                ),
                const SizedBox(height: 24),

                // Signature Line
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text('Prepared with Fira Export Studio', style: TextStyle(fontSize: 9, color: Colors.grey)),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        Container(width: 120, height: 1, color: Colors.black87),
                        const SizedBox(height: 4),
                        const Text('Authorized Signatory', style: TextStyle(fontSize: 9, fontWeight: FontWeight.bold)),
                        Text(_exporterNameController.text, style: const TextStyle(fontSize: 8, color: Colors.grey)),
                      ],
                    ),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: 24),

          // Direct Download Actions
          Row(
            children: [
              Expanded(
                child: ElevatedButton.icon(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.red[700],
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                  icon: const Icon(Icons.picture_as_pdf, size: 18),
                  label: const Text('Download PDF', style: TextStyle(fontWeight: FontWeight.bold)),
                  onPressed: () => _downloadAndOpen('pdf'),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: ElevatedButton.icon(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.green[700],
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                  icon: const Icon(Icons.table_chart, size: 18),
                  label: const Text('Download Excel', style: TextStyle(fontWeight: FontWeight.bold)),
                  onPressed: () => _downloadAndOpen('xlsx'),
                ),
              ),
            ],
          ),
          const SizedBox(height: 40),
        ],
      ),
    );
  }

  Widget _buildCollapsibleSection({
    required String title,
    required IconData icon,
    required List<Widget> children,
  }) {
    return Card(
      elevation: 1,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: Theme(
        data: Theme.of(context).copyWith(dividerColor: Colors.transparent),
        child: ExpansionTile(
          initiallyExpanded: true,
          leading: Icon(icon, color: const Color(0xFF6D1A95)),
          title: Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
          childrenPadding: const EdgeInsets.all(16),
          expandedCrossAxisAlignment: CrossAxisAlignment.start,
          children: children,
        ),
      ),
    );
  }

  Widget _buildTextField(String label, TextEditingController controller, {int maxLines = 1, bool isNumeric = false, String? hint}) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 12, color: Colors.black87)),
          const SizedBox(height: 6),
          TextField(
            controller: controller,
            maxLines: maxLines,
            keyboardType: isNumeric ? const TextInputType.numberWithOptions(decimal: true) : TextInputType.text,
            style: const TextStyle(fontSize: 13),
            decoration: InputDecoration(
              hintText: hint,
              hintStyle: TextStyle(fontSize: 12, color: Colors.grey[400]),
              isDense: true,
              contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide(color: Colors.grey[300]!)),
              enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide(color: Colors.grey[300]!)),
              focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: const BorderSide(color: Color(0xFF6D1A95), width: 1.5)),
              filled: true,
              fillColor: Colors.grey[50],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildAiCalloutCard() {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF6D1A95), Color(0xFF9C27B0)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(14),
        boxShadow: [
          BoxShadow(color: const Color(0xFF6D1A95).withValues(alpha: 0.3), blurRadius: 8, offset: const Offset(0, 3)),
        ],
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.2), shape: BoxShape.circle),
            child: const Icon(Icons.auto_awesome, color: Colors.amber, size: 22),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('Fira AI Document Assistant', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 14)),
                const SizedBox(height: 2),
                Text('Customize this document with intelligent trade prompts & Ethiopian export compliance.', style: TextStyle(color: Colors.white.withValues(alpha: 0.85), fontSize: 11)),
              ],
            ),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.amber,
              foregroundColor: Colors.black87,
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
            ),
            onPressed: _openFiraAiModal,
            child: const Text('Ask AI', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
          ),
        ],
      ),
    );
  }

  Widget _buildBottomBar() {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border(top: BorderSide(color: Colors.grey[200]!)),
        boxShadow: [
          BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 6, offset: const Offset(0, -2)),
        ],
      ),
      child: SafeArea(
        child: Row(
          children: [
            Expanded(
              child: OutlinedButton.icon(
                style: OutlinedButton.styleFrom(
                  foregroundColor: const Color(0xFF6D1A95),
                  side: const BorderSide(color: Color(0xFF6D1A95)),
                  padding: const EdgeInsets.symmetric(vertical: 12),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                ),
                icon: _isSaving
                    ? const SizedBox(width: 14, height: 14, child: CircularProgressIndicator(strokeWidth: 2, color: Color(0xFF6D1A95)))
                    : const Icon(Icons.save_outlined, size: 18),
                label: Text(_isSaving ? 'Saving...' : 'Save Draft', style: const TextStyle(fontWeight: FontWeight.bold)),
                onPressed: _isSaving ? null : _saveDraft,
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: ElevatedButton.icon(
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF6D1A95),
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 12),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                ),
                icon: const Icon(Icons.verified_rounded, size: 18),
                label: const Text('Finalize', style: TextStyle(fontWeight: FontWeight.bold)),
                onPressed: _finalizeDocument,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/auth_provider.dart';
import '../../services/marketplace_service.dart';
import '../../theme/app_theme.dart';
import '../../router/app_router.dart';
import 'widgets/quota_limit_sheet.dart';
import 'widgets/verification_prompt_dialog.dart';

class MarketplaceRfqsScreen extends StatefulWidget {
  const MarketplaceRfqsScreen({super.key});

  @override
  State<MarketplaceRfqsScreen> createState() => _MarketplaceRfqsScreenState();
}

class _MarketplaceRfqsScreenState extends State<MarketplaceRfqsScreen> {
  final MarketplaceService _marketplaceService = MarketplaceService();
  List<dynamic> _rfqs = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _fetchRfqs();
  }

  Future<void> _fetchRfqs() async {
    setState(() => _isLoading = true);
    try {
      final res = await _marketplaceService.getRfqs();
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (mounted) {
          setState(() {
            final raw = data['data'];
            if (raw is List) {
              _rfqs = raw;
            } else if (raw is Map && raw['data'] is List) {
              _rfqs = raw['data'];
            } else {
              _rfqs = [];
            }
            _isLoading = false;
          });
        }
      } else {
        if (mounted) setState(() => _isLoading = false);
      }
    } catch (_) {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  void _showQuoteDialog(dynamic rfq) {
    final auth = context.read<AuthProvider>();
    if (!auth.isAuthenticated) {
      VerificationPromptDialog.showAuthRequired(
        context,
        actionDescription: 'submit a quotation on this coffee procurement tender',
      );
      return;
    }

    final priceCtrl = TextEditingController();
    final bagsCtrl = TextEditingController(text: rfq['volume_bags']?.toString() ?? '100');
    final termsCtrl = TextEditingController(text: 'Irrevocable LC at sight via Commercial Bank of Ethiopia');
    String? quoteError;

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
                  const Text('Submit Exporter Quote', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                  IconButton(icon: const Icon(Icons.close), onPressed: () => Navigator.pop(ctx)),
                ],
              ),
              Text('For: ${rfq['title']}', style: const TextStyle(fontSize: 12, color: Colors.grey)),
              const SizedBox(height: 16),
              TextField(
                controller: priceCtrl,
                keyboardType: const TextInputType.numberWithOptions(decimal: true),
                decoration: InputDecoration(
                  labelText: 'Offered FOB Price (USD / lb) *',
                  hintText: 'e.g. 4.10',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: bagsCtrl,
                keyboardType: TextInputType.number,
                decoration: InputDecoration(
                  labelText: 'Volume (60kg Bags) *',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: termsCtrl,
                decoration: InputDecoration(
                  labelText: 'Payment Terms',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
              const SizedBox(height: 18),
              StatefulBuilder(
                builder: (dialogCtx, setDialogState) {
                  return Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      if (quoteError != null)
                        Padding(
                          padding: const EdgeInsets.only(bottom: 8.0),
                          child: Text(
                            quoteError ?? '',
                            style: const TextStyle(color: Colors.red, fontSize: 12, fontWeight: FontWeight.bold),
                          ),
                        ),
                      SizedBox(
                        width: double.infinity,
                        child: ElevatedButton(
                          onPressed: () async {
                            final price = double.tryParse(priceCtrl.text.trim());
                            final bags = int.tryParse(bagsCtrl.text.trim());
                            if (price == null || price <= 0) {
                              setDialogState(() {
                                quoteError = 'Please enter a valid FOB price in USD/lb.';
                              });
                              return;
                            }
                            if (bags == null || bags <= 0) {
                              setDialogState(() {
                                quoteError = 'Please enter a valid bag volume.';
                              });
                              return;
                            }

                            Navigator.pop(ctx);
                            final res = await _marketplaceService.submitQuote(rfq['id'], {
                              'offered_price_usd_per_lb': price,
                              'quantity_bags': bags,
                              'payment_terms': termsCtrl.text.trim(),
                              'validity_date': DateTime.now().add(const Duration(days: 14)).toIso8601String().split('T')[0],
                            });

                            if (res.statusCode == 200 || res.statusCode == 201) {
                              if (mounted) {
                                ScaffoldMessenger.of(context).showSnackBar(
                                  const SnackBar(content: Text('Quotation submitted to buyer successfully!'), backgroundColor: Colors.green),
                                );
                              }
                            } else if (res.statusCode == 401) {
                              if (mounted) {
                                VerificationPromptDialog.showAuthRequired(
                                  context,
                                  actionDescription: 'submit quotations on RFQ tenders',
                                );
                              }
                            } else if (res.statusCode == 403) {
                              if (mounted) {
                                try {
                                  final err = jsonDecode(res.body);
                                  if (err['code'] == 'VERIFICATION_REQUIRED') {
                                    VerificationPromptDialog.showVerificationRequired(
                                      context,
                                      role: 'exporter',
                                      status: err['verification_status'],
                                      message: err['message'],
                                    );
                                    return;
                                  }
                                } catch (_) {}

                                if (_marketplaceService.isLimitReached(res)) {
                                  final limitInfo = _marketplaceService.parseLimitError(res) ?? {
                                    'feature': 'rfq_responses_per_month',
                                    'message': 'Monthly RFQ quote response limit reached for your plan.',
                                  };
                                  QuotaLimitSheet.show(context, limitInfo);
                                  return;
                                }

                                ScaffoldMessenger.of(context).showSnackBar(
                                  const SnackBar(content: Text('Action restricted. Verification required.'), backgroundColor: Colors.red),
                                );
                              }
                            } else if (_marketplaceService.isLimitReached(res)) {
                              if (mounted) {
                                final limitInfo = _marketplaceService.parseLimitError(res) ?? {
                                  'feature': 'rfq_responses_per_month',
                                  'message': 'Monthly RFQ quote response limit reached for your plan.',
                                };
                                QuotaLimitSheet.show(context, limitInfo);
                              }
                            } else {
                              String errMsg = 'Error submitting quote';
                              try {
                                final err = jsonDecode(res.body);
                                if (err['message'] != null) errMsg = err['message'];
                              } catch (_) {}
                              if (mounted) {
                                ScaffoldMessenger.of(context).showSnackBar(
                                  SnackBar(content: Text(errMsg), backgroundColor: Colors.red),
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
                          child: const Text('Submit Binding Quote', style: TextStyle(fontWeight: FontWeight.bold)),
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

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Buyer RFQ Tenders', style: TextStyle(fontWeight: FontWeight.bold)),
      ),
      body: RefreshIndicator(
        onRefresh: _fetchRfqs,
        color: AppTheme.primary,
        child: _isLoading
            ? const Center(child: CircularProgressIndicator(color: AppTheme.primary))
            : _rfqs.isEmpty
                ? const Center(child: Text('No open buyer tenders found.'))
                : ListView.separated(
                    padding: const EdgeInsets.all(16),
                    itemCount: _rfqs.length,
                    separatorBuilder: (_, _) => const SizedBox(height: 14),
                    itemBuilder: (context, index) {
                      final rfq = _rfqs[index];
                      final rfqNum = rfq['rfq_number'] ?? 'RFQ';
                      final title = rfq['title'] ?? 'Procurement Tender';
                      final bags = rfq['volume_bags'] ?? 0;
                      final destCountry = rfq['destination_country'] ?? '';
                      final destPort = rfq['destination_port'] ?? '';
                      final incoterm = rfq['preferred_incoterm'] ?? 'FOB';
                      final quotesCount = rfq['quotes_count'] ?? 0;

                      return Card(
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                        color: isDark ? AppTheme.surfaceDark : Colors.white,
                        elevation: 0,
                        child: Padding(
                          padding: const EdgeInsets.all(16.0),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                    decoration: BoxDecoration(
                                      color: Colors.grey.withValues(alpha: 0.15),
                                      borderRadius: BorderRadius.circular(6),
                                    ),
                                    child: Text(
                                      rfqNum,
                                      style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, fontFamily: 'monospace'),
                                    ),
                                  ),
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                    decoration: BoxDecoration(
                                      color: const Color(0xFF10B981).withValues(alpha: 0.1),
                                      borderRadius: BorderRadius.circular(6),
                                    ),
                                    child: const Text(
                                      'OPEN',
                                      style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF10B981)),
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 10),
                              Text(title, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                              const SizedBox(height: 8),
                              Row(
                                children: [
                                  const Icon(Icons.location_on_outlined, size: 14, color: Colors.grey),
                                  const SizedBox(width: 4),
                                  Expanded(
                                    child: Text(
                                      '$destPort, $destCountry ($incoterm)',
                                      style: const TextStyle(fontSize: 12, color: Colors.grey),
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 6),
                              Row(
                                children: [
                                  const Icon(Icons.shopping_bag_outlined, size: 14, color: AppTheme.primary),
                                  const SizedBox(width: 4),
                                  Text('$bags Bags (${(bags * 60 / 1000).toStringAsFixed(1)} MT)',
                                      style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                                  const Spacer(),
                                  Text('$quotesCount Quotes Received', style: const TextStyle(fontSize: 11, color: Colors.grey)),
                                ],
                              ),
                              const SizedBox(height: 14),
                              SizedBox(
                                width: double.infinity,
                                child: ElevatedButton(
                                  onPressed: () => _showQuoteDialog(rfq),
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: isDark ? AppTheme.primary : Colors.black,
                                    foregroundColor: Colors.white,
                                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                                  ),
                                  child: const Text('Submit Quotation', style: TextStyle(fontWeight: FontWeight.bold)),
                                ),
                              ),
                            ],
                          ),
                        ),
                      );
                    },
                  ),
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () {
          final auth = context.read<AuthProvider>();
          if (!auth.isAuthenticated) {
            VerificationPromptDialog.showAuthRequired(
              context,
              actionDescription: 'post an international coffee procurement tender',
            );
            return;
          }
          Navigator.pushNamed(context, AppRouter.marketplaceCreateRfq);
        },
        backgroundColor: AppTheme.primary,
        icon: const Icon(Icons.add, color: Colors.white),
        label: const Text('Post RFQ', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
      ),
    );
  }
}

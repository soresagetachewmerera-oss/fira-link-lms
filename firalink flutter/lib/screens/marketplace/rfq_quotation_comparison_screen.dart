import 'dart:convert';
import 'package:flutter/material.dart';
import '../../l10n/app_localizations.dart';
import '../../services/api_exception.dart';
import '../../services/deal_room_service.dart';
import '../../services/marketplace_service.dart';
import '../../widgets/fira_error_state.dart';
import '../deal_room/deal_room_hub_screen.dart';

class RfqQuotationComparisonScreen extends StatefulWidget {
  final int rfqId;
  final String rfqTitle;

  const RfqQuotationComparisonScreen({
    super.key,
    required this.rfqId,
    required this.rfqTitle,
  });

  @override
  State<RfqQuotationComparisonScreen> createState() =>
      _RfqQuotationComparisonScreenState();
}

class _RfqQuotationComparisonScreenState
    extends State<RfqQuotationComparisonScreen> {
  final MarketplaceService _marketplaceService = MarketplaceService();
  final DealRoomService _dealRoomService = DealRoomService();

  List<dynamic> _quotes = [];
  bool _isLoading = true;
  Object? _loadError;

  @override
  void initState() {
    super.initState();
    _fetchQuotes();
  }

  Future<void> _fetchQuotes() async {
    setState(() {
      _isLoading = true;
      _loadError = null;
    });
    try {
      final res = requireSuccess(await _marketplaceService.getRfqQuotes(widget.rfqId));
      final decoded = jsonDecode(res.body);
      final data = decoded['data'];
      final quotes = data is Map ? data['quotes'] : data;
      if (!mounted) return;
      setState(() {
        _quotes = quotes is List ? quotes : [];
        _isLoading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _quotes = [];
        _loadError = e;
        _isLoading = false;
      });
    }
  }

  String _sellerName(dynamic quote) {
    final business = quote['seller_business'];
    return (business is Map ? business['name'] : null)?.toString() ?? '';
  }

  bool _sellerVerified(dynamic quote) {
    final business = quote['seller_business'];
    return business is Map && ((business['verification_tier'] as num?) ?? 0) >= 1;
  }

  Future<void> _acceptAndOpenDealRoom(dynamic quote) async {
    final title = 'Deal Room: ${widget.rfqTitle}';
    final targetValue = (quote['total_price_usd'] as num?)?.toDouble() ?? 50000.0;

    final res = await _dealRoomService.openDealRoom(
      quotationId: quote['id'],
      title: title,
      targetValueUsd: targetValue,
      incoterm: quote['incoterm'] ?? 'FOB Djibouti',
    );

    if (mounted) {
      if (res['success'] == true) {
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (_) => DealRoomHubScreen(dealRoomId: res['deal_room'].id),
          ),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(res['message'] ?? 'Could not open deal room'),
            backgroundColor: Colors.redAccent,
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    const primaryColor = Color(0xFF6D1A95);

    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: Text(
          widget.rfqTitle,
          style: const TextStyle(color: Color(0xFF1E293B), fontWeight: FontWeight.bold, fontSize: 16),
        ),
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Color(0xFF1E293B)),
          onPressed: () => Navigator.pop(context),
        ),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: primaryColor))
          : _loadError != null
          ? FiraErrorState(error: _loadError, onRetry: _fetchQuotes)
          : Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const Text(
                    'Compare Exporter Quotations',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF1E293B)),
                  ),
                  const SizedBox(height: 6),
                  const Text(
                    'Compare FOB price, sample policy, and delivery lead time. Accepting a quote opens a dedicated Deal Room.',
                    style: TextStyle(fontSize: 12, color: Colors.black54),
                  ),
                  const SizedBox(height: 18),

                  Expanded(
                    child: _quotes.isEmpty
                        ? Center(
                            child: Text(
                              AppLocalizations.of(context).noQuotesYet,
                              textAlign: TextAlign.center,
                              style: const TextStyle(fontSize: 13, color: Colors.black54),
                            ),
                          )
                        : ListView.separated(
                      itemCount: _quotes.length,
                      separatorBuilder: (_, __) => const SizedBox(height: 14),
                      itemBuilder: (context, index) {
                        final quote = _quotes[index];
                        final price = (num.tryParse('${quote['offered_price_usd_per_lb']}') ?? 0).toStringAsFixed(2);
                        final seller = _sellerName(quote);
                        final isVerified = _sellerVerified(quote);

                        return Container(
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(color: index == 0 ? primaryColor : Colors.grey.shade200),
                            color: Colors.white,
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black.withValues(alpha: 0.04),
                                blurRadius: 10,
                                offset: const Offset(0, 4),
                              ),
                            ],
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Row(
                                    children: [
                                      Text(seller, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                                      if (isVerified) ...[
                                        const SizedBox(width: 4),
                                        const Icon(Icons.verified, size: 14, color: Colors.blue),
                                      ],
                                    ],
                                  ),
                                  Text(
                                    '\$$price / lb',
                                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: primaryColor),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 12),
                              Row(
                                children: [
                                  _buildTag('${quote['incoterm'] ?? ''}'),
                                  const SizedBox(width: 6),
                                  _buildTag(AppLocalizations.of(context).translate('bagsCount', {'count': quote['quantity_bags'] ?? 0})),
                                  if (quote['listing']?['cupping_score'] != null) ...[
                                    const SizedBox(width: 6),
                                    _buildTag('SCA ${quote['listing']['cupping_score']}'),
                                  ],
                                ],
                              ),
                              const SizedBox(height: 14),
                              Row(
                                children: [
                                  Expanded(
                                    child: OutlinedButton(
                                      style: OutlinedButton.styleFrom(
                                        foregroundColor: Colors.grey.shade700,
                                        side: BorderSide(color: Colors.grey.shade300),
                                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                      ),
                                      onPressed: () {},
                                      child: const Text('Request Sample'),
                                    ),
                                  ),
                                  const SizedBox(width: 10),
                                  Expanded(
                                    child: ElevatedButton(
                                      style: ElevatedButton.styleFrom(
                                        backgroundColor: primaryColor,
                                        foregroundColor: Colors.white,
                                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                      ),
                                      onPressed: () => _acceptAndOpenDealRoom(quote),
                                      child: const Text('Accept & Deal Room'),
                                    ),
                                  ),
                                ],
                              ),
                            ],
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

  Widget _buildTag(String text) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: Colors.grey.shade100,
        borderRadius: BorderRadius.circular(6),
      ),
      child: Text(text, style: TextStyle(fontSize: 11, color: Colors.grey.shade700)),
    );
  }
}


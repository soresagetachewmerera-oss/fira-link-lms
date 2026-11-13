import 'dart:convert';
import 'package:flutter/material.dart';
import '../../services/deal_room_service.dart';
import '../../services/marketplace_service.dart';
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

  @override
  void initState() {
    super.initState();
    _fetchQuotes();
  }

  Future<void> _fetchQuotes() async {
    setState(() => _isLoading = true);
    final res = await _marketplaceService.getRfqQuotes(widget.rfqId);
    if (mounted) {
      if (res.statusCode == 200) {
        try {
          final decoded = jsonDecode(res.body);
          if (decoded['data'] is List) {
            _quotes = decoded['data'];
          }
        } catch (_) {}
      }
      setState(() => _isLoading = false);
    }
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
                    child: ListView.separated(
                      itemCount: _quotes.isNotEmpty ? _quotes.length : 2,
                      separatorBuilder: (_, __) => const SizedBox(height: 14),
                      itemBuilder: (context, index) {
                        final price = index == 0 ? 3.45 : 3.60;
                        final seller = index == 0 ? 'Abyssinia Specialty Exporters' : 'Keffa Highlands Union';
                        final isVerified = true;

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
                                  _buildTag('FOB Djibouti'),
                                  const SizedBox(width: 6),
                                  _buildTag('GrainPro Bags'),
                                  const SizedBox(width: 6),
                                  _buildTag('SCA 87.5'),
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
                                      onPressed: () => _acceptAndOpenDealRoom({
                                        'id': index + 1,
                                        'total_price_usd': price * 42328.7,
                                        'incoterm': 'FOB Djibouti',
                                      }),
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


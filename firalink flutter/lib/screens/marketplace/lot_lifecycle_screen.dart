import 'package:flutter/material.dart';
import 'package:share_plus/share_plus.dart';
import '../../models/coffee_export_models.dart';

class LotLifecycleScreen extends StatefulWidget {
  final CoffeeListingModel listing;

  const LotLifecycleScreen({super.key, required this.listing});

  @override
  State<LotLifecycleScreen> createState() => _LotLifecycleScreenState();
}

class _LotLifecycleScreenState extends State<LotLifecycleScreen> {
  late String _status;

  @override
  void initState() {
    super.initState();
    _status = widget.listing.status;
  }

  void _shareLot() {
    Share.share(
      'Check out this Ethiopian Coffee Lot on Fira Link:\n'
      '${widget.listing.title} (${widget.listing.grade.toUpperCase()} ${widget.listing.originRegion})\n'
      'Evidence: ${widget.listing.evidenceLevel.toUpperCase()} • \$${widget.listing.indicativePriceUsdPerLb}/lb FOB Djibouti\n'
      'https://firalink.com/marketplace/listings/${widget.listing.slug}',
    );
  }

  @override
  Widget build(BuildContext context) {
    const primaryColor = Color(0xFF6D1A95);

    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: const Text(
          'Manage Lot Lifecycle',
          style: TextStyle(color: Color(0xFF1E293B), fontWeight: FontWeight.bold, fontSize: 16),
        ),
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
            tooltip: MaterialLocalizations.of(context).backButtonTooltip,
            icon: const Icon(Icons.arrow_back, color: Color(0xFF1E293B)),
          onPressed: () => Navigator.pop(context),
        ),
      ),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // Summary Card
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: Colors.grey.shade200),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      widget.listing.title,
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      '${widget.listing.originRegion} • ${widget.listing.processingMethod.toUpperCase()} • ${widget.listing.totalQuantityBags} Bags',
                      style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                          decoration: BoxDecoration(
                            color: _status == 'active' ? Colors.green.shade50 : Colors.amber.shade50,
                            borderRadius: BorderRadius.circular(6),
                          ),
                          child: Text(
                            _status.toUpperCase(),
                            style: TextStyle(
                              color: _status == 'active' ? Colors.green.shade800 : Colors.amber.shade900,
                              fontWeight: FontWeight.bold,
                              fontSize: 11,
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Text(
                          'Expires in 84 days',
                          style: TextStyle(fontSize: 12, color: Colors.grey.shade500),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 24),

              // Action tiles
              ListTile(
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14), side: BorderSide(color: Colors.grey.shade200)),
                leading: const Icon(Icons.share_outlined, color: primaryColor),
                title: const Text('Share Branded Lot Card', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                subtitle: const Text('Send verified specs to international buyers via WhatsApp/Email', style: TextStyle(fontSize: 11)),
                onTap: _shareLot,
              ),
              const SizedBox(height: 12),

              ListTile(
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14), side: BorderSide(color: Colors.grey.shade200)),
                leading: const Icon(Icons.refresh, color: Colors.blue),
                title: const Text('Renew Lot Validity (+90 Days)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                subtitle: const Text('Confirm inventory freshness and extend marketplace visibility', style: TextStyle(fontSize: 11)),
                onTap: () {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Lot validity extended for 90 days.'), backgroundColor: Colors.green),
                  );
                },
              ),
              const SizedBox(height: 12),

              ListTile(
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14), side: BorderSide(color: Colors.grey.shade200)),
                leading: Icon(_status == 'active' ? Icons.pause_circle_outline : Icons.play_circle_outline, color: Colors.amber.shade800),
                title: Text(_status == 'active' ? 'Pause Listing' : 'Resume Listing', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                subtitle: Text(_status == 'active' ? 'Temporarily hide from buyers while updating inventory' : 'Publish lot back to live trade catalog', style: const TextStyle(fontSize: 11)),
                onTap: () {
                  setState(() {
                    _status = _status == 'active' ? 'paused' : 'active';
                  });
                },
              ),
            ],
          ),
        ),
      ),
    );
  }
}


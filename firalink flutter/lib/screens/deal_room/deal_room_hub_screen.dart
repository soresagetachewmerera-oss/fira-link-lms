import 'package:flutter/material.dart';
import '../../models/deal_room_model.dart';
import '../../services/deal_room_service.dart';
import 'dispute_resolution_screen.dart';
import 'export_contract_esign_screen.dart';
import 'payment_milestones_screen.dart';
import 'preshipment_inspection_screen.dart';
import 'shipping_logistics_screen.dart';

class DealRoomHubScreen extends StatefulWidget {
  final int dealRoomId;

  const DealRoomHubScreen({super.key, required this.dealRoomId});

  @override
  State<DealRoomHubScreen> createState() => _DealRoomHubScreenState();
}

class _DealRoomHubScreenState extends State<DealRoomHubScreen> {
  final DealRoomService _service = DealRoomService();
  DealRoomModel? _dealRoom;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadDealRoom();
  }

  Future<void> _loadDealRoom() async {
    setState(() => _isLoading = true);
    final dr = await _service.getDealRoom(widget.dealRoomId);
    if (mounted) {
      setState(() {
        _dealRoom = dr;
        _isLoading = false;
      });
    }
  }

  void _onMilestoneTap(DealMilestoneModel milestone) {
    if (_dealRoom == null) return;

    switch (milestone.milestoneKey) {
      case 'contract_signed':
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (_) => ExportContractEsignScreen(dealRoom: _dealRoom!),
          ),
        ).then((_) => _loadDealRoom());
        break;
      case 'payment_escrow':
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (_) => PaymentMilestonesScreen(dealRoom: _dealRoom!, milestone: milestone),
          ),
        ).then((_) => _loadDealRoom());
        break;
      case 'preshipment_inspection':
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (_) => PreshipmentInspectionScreen(dealRoom: _dealRoom!, milestone: milestone),
          ),
        ).then((_) => _loadDealRoom());
        break;
      case 'bill_of_lading_released':
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (_) => ShippingLogisticsScreen(dealRoom: _dealRoom!, milestone: milestone),
          ),
        ).then((_) => _loadDealRoom());
        break;
      default:
        // Complete or generic milestone
        break;
    }
  }

  @override
  Widget build(BuildContext context) {
    const primaryColor = Color(0xFF6D1A95);

    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: Text(
          _dealRoom != null ? 'Deal Room: ${_dealRoom!.dealReference}' : 'B2B Deal Room',
          style: const TextStyle(color: Color(0xFF1E293B), fontWeight: FontWeight.bold, fontSize: 16),
        ),
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Color(0xFF1E293B)),
          onPressed: () => Navigator.pop(context),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.warning_amber_outlined, color: Colors.redAccent),
            tooltip: 'Raise Dispute',
            onPressed: () {
              if (_dealRoom != null) {
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (_) => DisputeResolutionScreen(dealRoom: _dealRoom!),
                  ),
                ).then((_) => _loadDealRoom());
              }
            },
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: primaryColor))
          : _dealRoom == null
              ? const Center(child: Text('Deal Room not found'))
              : RefreshIndicator(
                  onRefresh: _loadDealRoom,
                  child: SingleChildScrollView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.all(20),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        // Deal Room Summary Card
                        Container(
                          padding: const EdgeInsets.all(18),
                          decoration: BoxDecoration(
                            color: primaryColor.withValues(alpha: 0.05),
                            borderRadius: BorderRadius.circular(18),
                            border: Border.all(color: primaryColor.withValues(alpha: 0.2)),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Text(
                                    _dealRoom!.dealReference,
                                    style: const TextStyle(
                                      fontWeight: FontWeight.bold,
                                      fontSize: 16,
                                      color: primaryColor,
                                    ),
                                  ),
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                    decoration: BoxDecoration(
                                      color: _dealRoom!.isCompleted
                                          ? Colors.green
                                          : _dealRoom!.isDisputed
                                              ? Colors.red
                                              : _dealRoom!.isUnderContract
                                                  ? Colors.blue
                                                  : Colors.orange,
                                      borderRadius: BorderRadius.circular(12),
                                    ),
                                    child: Text(
                                      _dealRoom!.status.toUpperCase().replaceAll('_', ' '),
                                      style: const TextStyle(
                                        color: Colors.white,
                                        fontSize: 10,
                                        fontWeight: FontWeight.bold,
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 10),
                              Text(
                                _dealRoom!.title,
                                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                              ),
                              const SizedBox(height: 6),
                              Text(
                                'Target Value: \$${_dealRoom!.targetValueUsd.toStringAsFixed(2)} USD • ${_dealRoom!.incoterm}',
                                style: TextStyle(fontSize: 13, color: Colors.grey.shade700, fontWeight: FontWeight.w500),
                              ),
                              const Divider(height: 24),
                              Row(
                                children: [
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        const Text('EXPORTER', style: TextStyle(fontSize: 10, color: Colors.grey, fontWeight: FontWeight.bold)),
                                        const SizedBox(height: 2),
                                        Text(
                                          _dealRoom!.organization?.name ?? 'Seller Organization',
                                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12),
                                          overflow: TextOverflow.ellipsis,
                                        ),
                                      ],
                                    ),
                                  ),
                                  const SizedBox(width: 12),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        const Text('BUYER', style: TextStyle(fontSize: 10, color: Colors.grey, fontWeight: FontWeight.bold)),
                                        const SizedBox(height: 2),
                                        Text(
                                          _dealRoom!.buyer?.name ?? 'Buyer Entity',
                                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12),
                                          overflow: TextOverflow.ellipsis,
                                        ),
                                      ],
                                    ),
                                  ),
                                ],
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(height: 24),

                        // Milestones Execution Roadmap
                        const Text(
                          'Contract & Execution Milestones',
                          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF1E293B)),
                        ),
                        const SizedBox(height: 6),
                        const Text(
                          'Tap any milestone to view proof, upload documents, or e-sign contracts.',
                          style: TextStyle(fontSize: 12, color: Colors.black54),
                        ),
                        const SizedBox(height: 16),

                        ListView.separated(
                          shrinkWrap: true,
                          physics: const NeverScrollableScrollPhysics(),
                          itemCount: _dealRoom!.milestones.length,
                          separatorBuilder: (_, __) => const SizedBox(height: 12),
                          itemBuilder: (context, index) {
                            final m = _dealRoom!.milestones[index];
                            final isAchieved = m.isAchieved;

                            return InkWell(
                              onTap: () => _onMilestoneTap(m),
                              borderRadius: BorderRadius.circular(16),
                              child: Container(
                                padding: const EdgeInsets.all(16),
                                decoration: BoxDecoration(
                                  borderRadius: BorderRadius.circular(16),
                                  border: Border.all(
                                    color: isAchieved ? Colors.green.shade300 : Colors.grey.shade200,
                                  ),
                                  color: isAchieved ? Colors.green.withValues(alpha: 0.03) : Colors.white,
                                ),
                                child: Row(
                                  children: [
                                    Container(
                                      width: 36,
                                      height: 36,
                                      decoration: BoxDecoration(
                                        color: isAchieved ? Colors.green : Colors.grey.shade100,
                                        shape: BoxShape.circle,
                                      ),
                                      child: Icon(
                                        isAchieved ? Icons.check : Icons.circle_outlined,
                                        color: isAchieved ? Colors.white : Colors.grey,
                                        size: 20,
                                      ),
                                    ),
                                    const SizedBox(width: 14),
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Text(
                                            m.title,
                                            style: TextStyle(
                                              fontWeight: FontWeight.bold,
                                              fontSize: 14,
                                              color: isAchieved ? Colors.green.shade900 : const Color(0xFF1E293B),
                                            ),
                                          ),
                                          if (m.description != null) ...[
                                            const SizedBox(height: 4),
                                            Text(
                                              m.description!,
                                              style: TextStyle(fontSize: 11, color: Colors.grey.shade600, height: 1.3),
                                            ),
                                          ],
                                        ],
                                      ),
                                    ),
                                    const Icon(Icons.chevron_right, color: Colors.grey),
                                  ],
                                ),
                              ),
                            );
                          },
                        ),
                        const SizedBox(height: 32),

                        if (!_dealRoom!.isFullySigned)
                          ElevatedButton.icon(
                            style: ElevatedButton.styleFrom(
                              backgroundColor: primaryColor,
                              foregroundColor: Colors.white,
                              minimumSize: const Size.fromHeight(50),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                            ),
                            icon: const Icon(Icons.draw_outlined),
                            label: const Text('E-Sign Export Contract', style: TextStyle(fontWeight: FontWeight.bold)),
                            onPressed: () {
                              Navigator.push(
                                context,
                                MaterialPageRoute(
                                  builder: (_) => ExportContractEsignScreen(dealRoom: _dealRoom!),
                                ),
                              ).then((_) => _loadDealRoom());
                            },
                          ),
                      ],
                    ),
                  ),
                ),
    );
  }
}


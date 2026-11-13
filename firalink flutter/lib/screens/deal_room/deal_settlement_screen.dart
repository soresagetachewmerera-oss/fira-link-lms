import 'package:flutter/material.dart';
import '../../models/deal_room_model.dart';
import '../../services/deal_room_service.dart';

class DealSettlementScreen extends StatefulWidget {
  final DealRoomModel dealRoom;

  const DealSettlementScreen({super.key, required this.dealRoom});

  @override
  State<DealSettlementScreen> createState() => _DealSettlementScreenState();
}

class _DealSettlementScreenState extends State<DealSettlementScreen> {
  final DealRoomService _service = DealRoomService();
  bool _isSettling = false;

  Future<void> _completeDeal() async {
    setState(() => _isSettling = true);
    final success = await _service.completeDeal(widget.dealRoom.id);
    if (!mounted) return;
    setState(() => _isSettling = false);

    if (success) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Deal completed and archived to trust ledger!'), backgroundColor: Colors.green),
      );
      Navigator.pop(context);
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Failed to complete deal'), backgroundColor: Colors.redAccent),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: const Text(
          'Final Settlement',
          style: TextStyle(color: Color(0xFF1E293B), fontWeight: FontWeight.bold, fontSize: 16),
        ),
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
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
              const Spacer(),
              Center(
                child: Container(
                  width: 80,
                  height: 80,
                  decoration: BoxDecoration(
                    color: Colors.green.shade50,
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(Icons.verified, color: Colors.green, size: 48),
                ),
              ),
              const SizedBox(height: 20),
              const Text(
                'Final Delivery & Settlement',
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold, color: Color(0xFF1E293B)),
              ),
              const SizedBox(height: 8),
              Text(
                'Confirm that the green coffee lot has cleared destination port customs and final payments have been released.',
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 13, color: Colors.grey.shade600, height: 1.4),
              ),
              const SizedBox(height: 32),

              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: Colors.grey.shade200),
                ),
                child: Column(
                  children: [
                    _buildRow('Deal Reference', widget.dealRoom.dealReference),
                    const Divider(height: 18),
                    _buildRow('Total Value', '\$${widget.dealRoom.targetValueUsd.toStringAsFixed(2)} USD'),
                    const Divider(height: 18),
                    _buildRow('Incoterm', widget.dealRoom.incoterm),
                  ],
                ),
              ),

              const Spacer(),

              ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.green,
                  foregroundColor: Colors.white,
                  minimumSize: const Size.fromHeight(52),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                ),
                onPressed: _isSettling ? null : _completeDeal,
                child: _isSettling
                    ? const SizedBox(
                        width: 22,
                        height: 22,
                        child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                      )
                    : const Text('Confirm Settlement & Close Deal', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildRow(String label, String val) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: const TextStyle(fontSize: 13, color: Colors.grey)),
        Text(val, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold)),
      ],
    );
  }
}


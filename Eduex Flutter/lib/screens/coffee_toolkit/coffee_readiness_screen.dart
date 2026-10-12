  import 'package:flutter/material.dart';
import 'package:hugeicons/hugeicons.dart';
import 'package:provider/provider.dart';

import '../../providers/coffee_toolkit_provider.dart';
import '../../theme/app_theme.dart';

class CoffeeReadinessScreen extends StatefulWidget {
  const CoffeeReadinessScreen({super.key});

  @override
  State<CoffeeReadinessScreen> createState() => _CoffeeReadinessScreenState();
}

class _CoffeeReadinessScreenState extends State<CoffeeReadinessScreen> {
  final Map<String, bool> _answers = {
    'has_commercial_reg': true,
    'has_ecta_export_cert': false,
    'has_nbe_code': false,
    'has_tax_clearance': true,
    'has_direct_vertical_contracts': false,
    'has_traceability_system': false,
    'has_washing_station_access': true,
    'has_q_grader_access': false,
    'has_moisture_meter': true,
    'has_screen_sizing_equipment': false,
    'has_freight_forwarder_partner': true,
    'knows_container_stuffing_protocols': false,
    'knows_phytosanitary_procedures': true,
    'understands_nbe_forex_retention': true,
    'understands_lc_cad_terms': false,
    'has_buyer_contract_templates': false,
  };

  Future<void> _submitAssessment() async {
    final provider = context.read<CoffeeToolkitProvider>();
    final success = await provider.submitAssessment(_answers);

    if (!mounted) return;
    if (success) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Export readiness evaluated successfully!'),
          backgroundColor: Colors.green,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.getBackgroundColor(context),
      appBar: AppBar(
        backgroundColor: AppTheme.getBackgroundColor(context),
        elevation: 0,
        leading: IconButton(
          icon: HugeIcon(
            icon: HugeIcons.strokeRoundedArrowLeft01,
            size: 20,
            color: AppTheme.getTextColor(context),
          ),
          onPressed: () => Navigator.of(context).pop(),
        ),
        title: Text(
          'Export Readiness',
          style: TextStyle(
            color: AppTheme.getTextColor(context),
            fontSize: 18,
            fontWeight: FontWeight.bold,
          ),
        ),
      ),
      body: Consumer<CoffeeToolkitProvider>(
        builder: (context, provider, child) {
          final score = provider.latestAssessmentScore;
          final tier = provider.latestAssessmentTier;

          return SingleChildScrollView(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (score != null) ...[
                  _buildScoreCard(score, tier ?? 'Intermediate Exporter'),
                  const SizedBox(height: 20),
                ],
                _buildPillarCategory(
                  '1. Licensing & Legal Compliance',
                  [
                    _buildSwitch(
                      'has_commercial_reg',
                      'Ministry of Trade Commercial Registration',
                      'Active registration with coffee export scope',
                    ),
                    _buildSwitch(
                      'has_ecta_export_cert',
                      'ECTA Export Competence Certificate',
                      'Annual exporter license from Coffee Authority',
                    ),
                    _buildSwitch(
                      'has_nbe_code',
                      'NBE Foreign Exchange Exporter Code',
                      'Registered for forex retention account operations',
                    ),
                    _buildSwitch(
                      'has_tax_clearance',
                      'Federal Tax Clearance Certificate',
                      'Current quarterly clearance from Revenues',
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                _buildPillarCategory(
                  '2. Sourcing & Supply Chain Traceability',
                  [
                    _buildSwitch(
                      'has_direct_vertical_contracts',
                      'Direct Vertical Integration Contracts',
                      'Signed agreements with washing stations/cooperatives',
                    ),
                    _buildSwitch(
                      'has_traceability_system',
                      'EUDR-Compliant Polygon & Farm Traceability',
                      'Geolocation mapping for deforestation regulation',
                    ),
                    _buildSwitch(
                      'has_washing_station_access',
                      'Washing Station Infrastructure Access',
                      'Clean fermentation tanks & raised African beds',
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                _buildPillarCategory(
                  '3. Quality Grading & Cupping Standards',
                  [
                    _buildSwitch(
                      'has_q_grader_access',
                      'Certified Q-Arabica Grader Partnership',
                      'Access to cupping lab for scoring 84+ lots',
                    ),
                    _buildSwitch(
                      'has_moisture_meter',
                      'Calibrated Moisture Meter Equipment',
                      'Standard tester to keep moisture 10.0% - 11.5%',
                    ),
                    _buildSwitch(
                      'has_screen_sizing_equipment',
                      'Dry Mill Screen Sizing Sorting',
                      'Screen 14+, 15+, 17/18 grading compliance',
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                _buildPillarCategory(
                  '4. Djibouti Corridor Logistics',
                  [
                    _buildSwitch(
                      'has_freight_forwarder_partner',
                      'Addis-Djibouti Forwarding Agreement',
                      'Logistics agent for rail/truck container booking',
                    ),
                    _buildSwitch(
                      'knows_container_stuffing_protocols',
                      'Container Desiccant & Kraft-Paper Stuffing',
                      'Silica gel hangbags preventing transit sweat',
                    ),
                    _buildSwitch(
                      'knows_phytosanitary_procedures',
                      'Ministry of Agriculture Phytosanitary Certificate',
                      'Inspection and fumigation clearances',
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                _buildPillarCategory(
                  '5. Trade Finance & Payment Security',
                  [
                    _buildSwitch(
                      'understands_nbe_forex_retention',
                      'NBE Forex Retention & Surrender Rules',
                      'Understanding commercial bank conversion ratios',
                    ),
                    _buildSwitch(
                      'understands_lc_cad_terms',
                      'Documentary Letter of Credit (UCP 600) / CAD',
                      'Discrepancy-free shipping document presentation',
                    ),
                    _buildSwitch(
                      'has_buyer_contract_templates',
                      'Standard International Contracts (ECC / GCA)',
                      'European Contract for Coffee rules & arbitration',
                    ),
                  ],
                ),
                const SizedBox(height: 24),
                SizedBox(
                  width: double.infinity,
                  height: 48,
                  child: ElevatedButton(
                    onPressed: provider.isLoading ? null : _submitAssessment,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF6D1A95),
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(24),
                      ),
                      elevation: 0,
                    ),
                    child: provider.isLoading
                        ? const SizedBox(
                            width: 20,
                            height: 20,
                            child: CircularProgressIndicator(
                              color: Colors.white,
                              strokeWidth: 2,
                            ),
                          )
                        : const Text(
                            'Evaluate Readiness Score',
                            style: TextStyle(
                              fontSize: 14,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                  ),
                ),
                const SizedBox(height: 30),
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _buildScoreCard(double score, String tier) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF4A0E66), Color(0xFF6D1A95)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'YOUR READINESS SCORE',
                style: TextStyle(
                  color: Colors.white.withValues(alpha: 0.7),
                  fontSize: 11,
                  fontWeight: FontWeight.bold,
                  letterSpacing: 0.5,
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.2),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Text(
                  tier,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 11,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Text(
            '${score.toStringAsFixed(1)}%',
            style: const TextStyle(
              color: Colors.white,
              fontSize: 36,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            score >= 80
                ? 'Your operation meets major Ethiopian export and international buyer requirements.'
                : 'Review the gaps below to fulfill licensing and quality milestones.',
            style: TextStyle(
              color: Colors.white.withValues(alpha: 0.85),
              fontSize: 12,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildPillarCategory(String title, List<Widget> children) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppTheme.getCardColor(context),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: AppTheme.getTextColor(context).withValues(alpha: 0.08),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: const TextStyle(
              color: Color(0xFF6D1A95),
              fontSize: 13,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 12),
          ...children,
        ],
      ),
    );
  }

  Widget _buildSwitch(String key, String title, String subtitle) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: TextStyle(
                    color: AppTheme.getTextColor(context),
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                Text(
                  subtitle,
                  style: TextStyle(
                    color: AppTheme.getTextColor(context).withValues(alpha: 0.6),
                    fontSize: 11,
                  ),
                ),
              ],
            ),
          ),
          Switch(
            value: _answers[key] ?? false,
            activeColor: const Color(0xFF6D1A95),
            onChanged: (val) {
              setState(() {
                _answers[key] = val;
              });
            },
          ),
        ],
      ),
    );
  }
}

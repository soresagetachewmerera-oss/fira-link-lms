import 'package:flutter/material.dart';
import 'package:hugeicons/hugeicons.dart';
import 'package:provider/provider.dart';

import '../../models/coffee_export_models.dart';
import '../../providers/coffee_toolkit_provider.dart';
import '../../theme/app_theme.dart';
import '../../l10n/app_localizations.dart';
import 'widgets/coffee_candlestick_chart.dart';

class CoffeeMarketScreen extends StatefulWidget {
  const CoffeeMarketScreen({super.key});

  @override
  State<CoffeeMarketScreen> createState() => _CoffeeMarketScreenState();
}

class _CoffeeMarketScreenState extends State<CoffeeMarketScreen> {
  final TextEditingController _searchController = TextEditingController();
  bool _showSignals = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<CoffeeToolkitProvider>().loadMarketIntelligence();
    });
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
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
          context.l10n.marketIntelligence,
          style: TextStyle(
            color: AppTheme.getTextColor(context),
            fontSize: 16,
            fontWeight: FontWeight.bold,
          ),
        ),
        actions: [
          IconButton(
            icon: HugeIcon(
              icon: HugeIcons.strokeRoundedRefresh,
              size: 20,
              color: AppTheme.getTextColor(context),
            ),
            tooltip: 'Refresh Market Data',
            onPressed: () {
              context.read<CoffeeToolkitProvider>().loadMarketIntelligence(force: true);
            },
          ),
        ],
      ),
      body: Consumer<CoffeeToolkitProvider>(
        builder: (context, provider, child) {
          if (provider.isLoading && provider.allRecords.isEmpty) {
            return const Center(
              child: Padding(
                padding: EdgeInsets.all(32.0),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    CircularProgressIndicator(color: Color(0xFF6D1A95)),
                    SizedBox(height: 16),
                    Text(
                      'Loading authentic ECTA reference floors...',
                      style: TextStyle(fontSize: 13, color: Colors.grey),
                    ),
                  ],
                ),
              ),
            );
          }

          final response = provider.marketResponse;
          final snapshot = response?.snapshot;
          final synthesis = response?.synthesis;
          final records = provider.filteredRecords;

          return RefreshIndicator(
            color: const Color(0xFF6D1A95),
            onRefresh: () => provider.loadMarketIntelligence(force: true),
            child: SingleChildScrollView(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              physics: const AlwaysScrollableScrollPhysics(),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // 1. Regulatory Status Header Banner
                  _buildRegulatoryBanner(context, response),
                  const SizedBox(height: 16),

                  // Real-Time Candlestick Trading Chart (OHLC)
                  const CoffeeCandlestickChartWidget(),
                  const SizedBox(height: 16),

                  // 2. Market Pulse & Corridor Bar
                  _buildMarketPulseCard(context, snapshot, synthesis),
                  const SizedBox(height: 16),

                  // 3. Four Key Glanceable Insight Cards
                  _buildKeyInsightsGrid(context, snapshot, synthesis),
                  const SizedBox(height: 16),

                  // 4. Interactive Search & Filters
                  _buildSearchAndFilters(context, provider),
                  const SizedBox(height: 14),

                  // 5. Authentic Reference Price Records List
                  _buildPriceRecordsSection(context, provider, records),
                  const SizedBox(height: 20),

                  // 6. Market Signals & Commercial Guidance
                  if (synthesis != null &&
                      (synthesis.opportunitySignals.isNotEmpty ||
                          synthesis.riskSignals.isNotEmpty))
                    _buildMarketSignalsSection(context, synthesis),

                  const SizedBox(height: 20),

                  // 7. Global Macro Benchmark (ICE NY Futures)
                  _buildMacroCard(context),
                  const SizedBox(height: 16),

                  // 8. Shipping Calendar Timeline
                  _buildShippingCalendarCard(context),
                  const SizedBox(height: 32),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  /// 1. Regulatory Status Header Banner
  Widget _buildRegulatoryBanner(
      BuildContext context, CoffeeMarketIntelligenceResponse? response) {
    final meta = response?.datasetMeta;
    final scheduleTitle = meta?['title'] ?? 'ECTA Export Minimum Price Schedule';
    final effectiveDate = meta?['effective_date'] ?? 'October 20-26, 2026';
    final totalRecords = meta?['total_records'] ?? 529;

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF4A0E66), Color(0xFF6D1A95)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(18),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF6D1A95).withValues(alpha: 0.25),
            blurRadius: 12,
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
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.18),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: const Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(Icons.verified, size: 13, color: Colors.amber),
                    SizedBox(width: 4),
                    Text(
                      'OFFICIAL REGULATORY BENCHMARK',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 10,
                        fontWeight: FontWeight.bold,
                        letterSpacing: 0.4,
                      ),
                    ),
                  ],
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: Colors.green.withValues(alpha: 0.25),
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: Colors.greenAccent.withValues(alpha: 0.4)),
                ),
                child: const Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(Icons.fiber_manual_record, size: 8, color: Colors.greenAccent),
                    SizedBox(width: 4),
                    Text(
                      'Active & Enforced',
                      style: TextStyle(
                        color: Colors.greenAccent,
                        fontSize: 10,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Text(
            scheduleTitle,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 17,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 6),
          Row(
            children: [
              const Icon(Icons.calendar_today, size: 12, color: Colors.white70),
              const SizedBox(width: 4),
              Text(
                'Schedule: $effectiveDate',
                style: const TextStyle(color: Colors.white70, fontSize: 12),
              ),
              const SizedBox(width: 12),
              const Icon(Icons.dataset, size: 12, color: Colors.white70),
              const SizedBox(width: 4),
              Text(
                '$totalRecords Reference Floors',
                style: const TextStyle(color: Colors.white70, fontSize: 12),
              ),
            ],
          ),
        ],
      ),
    );
  }

  /// 2. Market Pulse & Corridor Card
  Widget _buildMarketPulseCard(
    BuildContext context,
    CoffeeDatasetSnapshotModel? snapshot,
    MarketSynthesisModel? synthesis,
  ) {
    final highest = snapshot != null ? '\$${snapshot.highestPrice.toStringAsFixed(3)}' : '\$10.120';
    final lowest = snapshot != null ? '\$${snapshot.lowestPrice.toStringAsFixed(3)}' : '\$2.430';
    final corridorText = '$lowest – $highest / lb';
    final avgText = snapshot != null ? '\$${snapshot.averagePrice.toStringAsFixed(3)} / lb' : '\$3.682 / lb';
    final spreadText = snapshot != null ? '\$${snapshot.priceSpread.toStringAsFixed(3)}' : '\$7.690';

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppTheme.getCardColor(context),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: const Color(0xFF6D1A95).withValues(alpha: 0.15),
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 10,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'WEEKLY TRADING CORRIDOR',
                style: TextStyle(
                  color: AppTheme.getTextColor(context).withValues(alpha: 0.6),
                  fontSize: 11,
                  fontWeight: FontWeight.bold,
                  letterSpacing: 0.5,
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
                decoration: BoxDecoration(
                  color: const Color(0xFF6D1A95).withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(6),
                ),
                child: const Text(
                  'FOB DJIBOUTI',
                  style: TextStyle(
                    color: Color(0xFF6D1A95),
                    fontSize: 10,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 6),
          Text(
            corridorText,
            style: const TextStyle(
              color: Color(0xFF6D1A95),
              fontSize: 22,
              fontWeight: FontWeight.bold,
              letterSpacing: -0.5,
            ),
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              Text(
                'Average Benchmark: ',
                style: TextStyle(
                  color: AppTheme.getTextColor(context).withValues(alpha: 0.6),
                  fontSize: 12,
                ),
              ),
              Text(
                avgText,
                style: TextStyle(
                  color: AppTheme.getTextColor(context),
                  fontSize: 12,
                  fontWeight: FontWeight.bold,
                ),
              ),
              const Spacer(),
              Text(
                'Spread: ',
                style: TextStyle(
                  color: AppTheme.getTextColor(context).withValues(alpha: 0.6),
                  fontSize: 12,
                ),
              ),
              Text(
                spreadText,
                style: TextStyle(
                  color: AppTheme.getTextColor(context),
                  fontSize: 12,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ],
          ),
          const Divider(height: 20),
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: Colors.amber.withValues(alpha: 0.08),
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: Colors.amber.withValues(alpha: 0.2)),
            ),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Icon(Icons.gavel, size: 16, color: Colors.amber),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    synthesis?.mandatoryRule ??
                        'Mandatory Export Rule: All export contracts submitted to ECTA and NBE must meet or exceed these minimum reference floors.',
                    style: TextStyle(
                      color: AppTheme.getTextColor(context).withValues(alpha: 0.85),
                      fontSize: 11,
                      height: 1.35,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  /// 3. Four Key Glanceable Insight Cards in 2x2 Grid
  Widget _buildKeyInsightsGrid(
    BuildContext context,
    CoffeeDatasetSnapshotModel? snapshot,
    MarketSynthesisModel? synthesis,
  ) {
    final highestPrice = snapshot != null
        ? '\$${snapshot.highestPrice.toStringAsFixed(3)}'
        : '\$10.120';
    final highestSpec = snapshot?.highestSpec ?? 'Washed Specialty G1';

    final lowestPrice = snapshot != null
        ? '\$${snapshot.lowestPrice.toStringAsFixed(3)}'
        : '\$2.430';
    final lowestSpec = snapshot?.lowestSpec ?? 'Commercial Djimmah G5';

    final largestMovement = synthesis?.largestMovement?['title'] ?? '+8.5%';
    final movementDetail = synthesis?.largestMovement?['description'] ?? 'Specialty Washed';

    final spread = snapshot != null
        ? '\$${snapshot.priceSpread.toStringAsFixed(3)}'
        : '\$7.690';
    final spreadDetail = 'Specialty vs Comm. Gap';

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Expanded(
              child: _buildInsightCard(
                context,
                title: 'Price Leader Floor',
                value: '$highestPrice/lb',
                subtitle: highestSpec,
                icon: Icons.trending_up,
                iconColor: Colors.green,
                highlightColor: Colors.green.withValues(alpha: 0.08),
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: _buildInsightCard(
                context,
                title: 'Lowest Reference',
                value: '$lowestPrice/lb',
                subtitle: lowestSpec,
                icon: Icons.shield_outlined,
                iconColor: const Color(0xFF6D1A95),
                highlightColor: const Color(0xFF6D1A95).withValues(alpha: 0.08),
              ),
            ),
          ],
        ),
        const SizedBox(height: 10),
        Row(
          children: [
            Expanded(
              child: _buildInsightCard(
                context,
                title: 'Largest Movement',
                value: largestMovement,
                subtitle: movementDetail,
                icon: Icons.bolt,
                iconColor: Colors.amber,
                highlightColor: Colors.amber.withValues(alpha: 0.08),
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: _buildInsightCard(
                context,
                title: 'Regional Spread',
                value: spread,
                subtitle: spreadDetail,
                icon: Icons.swap_horiz,
                iconColor: Colors.blueAccent,
                highlightColor: Colors.blueAccent.withValues(alpha: 0.08),
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildInsightCard(
    BuildContext context, {
    required String title,
    required String value,
    required String subtitle,
    required IconData icon,
    required Color iconColor,
    required Color highlightColor,
  }) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: AppTheme.getCardColor(context),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: AppTheme.getTextColor(context).withValues(alpha: 0.07),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Expanded(
                child: Text(
                  title.toUpperCase(),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    color: AppTheme.getTextColor(context).withValues(alpha: 0.55),
                    fontSize: 9.5,
                    fontWeight: FontWeight.bold,
                    letterSpacing: 0.3,
                  ),
                ),
              ),
              Container(
                padding: const EdgeInsets.all(4),
                decoration: BoxDecoration(
                  color: highlightColor,
                  shape: BoxShape.circle,
                ),
                child: Icon(icon, size: 14, color: iconColor),
              ),
            ],
          ),
          const SizedBox(height: 6),
          Text(
            value,
            style: TextStyle(
              color: AppTheme.getTextColor(context),
              fontSize: 16,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 2),
          Text(
            subtitle,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(
              color: AppTheme.getTextColor(context).withValues(alpha: 0.6),
              fontSize: 10.5,
            ),
          ),
        ],
      ),
    );
  }

  /// 4. Search and Filters
  Widget _buildSearchAndFilters(
      BuildContext context, CoffeeToolkitProvider provider) {
    final origins = provider.origins;
    final selectedOrigin = provider.selectedOrigin;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Search text field
        Container(
          height: 44,
          decoration: BoxDecoration(
            color: AppTheme.getCardColor(context),
            borderRadius: BorderRadius.circular(12),
            border: Border.all(
              color: AppTheme.getTextColor(context).withValues(alpha: 0.1),
            ),
          ),
          child: TextField(
            controller: _searchController,
            style: TextStyle(color: AppTheme.getTextColor(context), fontSize: 13),
            onChanged: (val) => provider.setSearchQuery(val),
            decoration: InputDecoration(
              hintText: 'Search by origin, grade, or process...',
              hintStyle: TextStyle(
                color: AppTheme.getTextColor(context).withValues(alpha: 0.4),
                fontSize: 13,
              ),
              prefixIcon: Icon(
                Icons.search,
                size: 18,
                color: AppTheme.getTextColor(context).withValues(alpha: 0.5),
              ),
              suffixIcon: _searchController.text.isNotEmpty
                  ? IconButton(
                      icon: const Icon(Icons.clear, size: 16),
                      onPressed: () {
                        _searchController.clear();
                        provider.setSearchQuery('');
                      },
                    )
                  : null,
              border: InputBorder.none,
              contentPadding: const EdgeInsets.symmetric(vertical: 10),
            ),
          ),
        ),
        const SizedBox(height: 10),

        // Horizontal origin filter pills
        SizedBox(
          height: 32,
          child: ListView.separated(
            scrollDirection: Axis.horizontal,
            itemCount: origins.length,
            separatorBuilder: (context, index) => const SizedBox(width: 6),
            itemBuilder: (context, index) {
              final origin = origins[index];
              final isSelected = origin.toLowerCase() == selectedOrigin.toLowerCase();

              return ChoiceChip(
                label: Text(origin),
                selected: isSelected,
                labelStyle: TextStyle(
                  color: isSelected ? Colors.white : AppTheme.getTextColor(context),
                  fontSize: 11,
                  fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                ),
                selectedColor: const Color(0xFF6D1A95),
                backgroundColor: AppTheme.getCardColor(context),
                side: BorderSide(
                  color: isSelected
                      ? const Color(0xFF6D1A95)
                      : AppTheme.getTextColor(context).withValues(alpha: 0.1),
                ),
                padding: const EdgeInsets.symmetric(horizontal: 4),
                onSelected: (selected) {
                  provider.setOriginFilter(origin);
                },
              );
            },
          ),
        ),
      ],
    );
  }

  /// 5. Reference Prices Records Section
  Widget _buildPriceRecordsSection(
    BuildContext context,
    CoffeeToolkitProvider provider,
    List<CoffeePriceRecordModel> records,
  ) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(
              'Official ECTA Reference Floors',
              style: TextStyle(
                color: AppTheme.getTextColor(context),
                fontSize: 15,
                fontWeight: FontWeight.bold,
              ),
            ),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
              decoration: BoxDecoration(
                color: const Color(0xFF6D1A95).withValues(alpha: 0.08),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Text(
                '${records.length} items',
                style: const TextStyle(
                  color: Color(0xFF6D1A95),
                  fontSize: 11,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
          ],
        ),
        const SizedBox(height: 10),
        if (records.isEmpty)
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(24),
            decoration: BoxDecoration(
              color: AppTheme.getCardColor(context),
              borderRadius: BorderRadius.circular(14),
              border: Border.all(
                color: AppTheme.getTextColor(context).withValues(alpha: 0.08),
              ),
            ),
            child: Column(
              children: [
                Icon(
                  Icons.search_off,
                  size: 32,
                  color: AppTheme.getTextColor(context).withValues(alpha: 0.4),
                ),
                const SizedBox(height: 8),
                Text(
                  'No coffee records matching current filter',
                  style: TextStyle(
                    color: AppTheme.getTextColor(context).withValues(alpha: 0.7),
                    fontSize: 13,
                  ),
                ),
                const SizedBox(height: 8),
                TextButton(
                  onPressed: () {
                    _searchController.clear();
                    provider.resetFilters();
                  },
                  child: const Text('Reset All Filters'),
                ),
              ],
            ),
          )
        else
          ListView.separated(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            itemCount: records.take(60).length,
            separatorBuilder: (context, index) => const SizedBox(height: 8),
            itemBuilder: (context, index) {
              final record = records[index];
              return _buildPriceRecordCard(context, record);
            },
          ),
      ],
    );
  }

  /// Single Price Record Card
  Widget _buildPriceRecordCard(
      BuildContext context, CoffeePriceRecordModel record) {
    final bool isWashed =
        record.processingMethod.toLowerCase().contains('washed');
    final double etbPerKg = record.minimumPrice * 2.20462 * 140.0;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: AppTheme.getCardColor(context),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: AppTheme.getTextColor(context).withValues(alpha: 0.08),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              // Origin Badge
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2.5),
                decoration: BoxDecoration(
                  color: const Color(0xFF6D1A95).withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Text(
                  record.origin,
                  style: const TextStyle(
                    color: Color(0xFF6D1A95),
                    fontSize: 10.5,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
              const SizedBox(width: 6),

              // Grade Badge
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2.5),
                decoration: BoxDecoration(
                  color: record.grade.contains('1')
                      ? Colors.amber.withValues(alpha: 0.15)
                      : (record.grade.contains('2')
                          ? Colors.blue.withValues(alpha: 0.12)
                          : Colors.grey.withValues(alpha: 0.12)),
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Text(
                  record.grade,
                  style: TextStyle(
                    color: record.grade.contains('1')
                        ? Colors.orange.shade800
                        : (record.grade.contains('2')
                            ? Colors.blue.shade800
                            : Colors.grey.shade700),
                    fontSize: 10.5,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
              const SizedBox(width: 6),

              // Process Badge
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2.5),
                decoration: BoxDecoration(
                  color: isWashed
                      ? Colors.teal.withValues(alpha: 0.1)
                      : Colors.brown.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Text(
                  record.processingMethod,
                  style: TextStyle(
                    color: isWashed ? Colors.teal : Colors.brown.shade700,
                    fontSize: 10,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),

              const Spacer(),

              // Exporter Type tag
              Text(
                record.exporterType,
                style: TextStyle(
                  color: AppTheme.getTextColor(context).withValues(alpha: 0.5),
                  fontSize: 10,
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),

          // Title & Price Row
          Row(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      record.coffeeType,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        color: AppTheme.getTextColor(context),
                        fontSize: 13,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      '~${etbPerKg.toStringAsFixed(0)} ETB / kg (approx floor)',
                      style: TextStyle(
                        color: AppTheme.getTextColor(context).withValues(alpha: 0.55),
                        fontSize: 10.5,
                      ),
                    ),
                  ],
                ),
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Text(
                    '\$${record.minimumPrice.toStringAsFixed(3)}',
                    style: const TextStyle(
                      color: Color(0xFF6D1A95),
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  Text(
                    'USD / lb FOB',
                    style: TextStyle(
                      color: AppTheme.getTextColor(context).withValues(alpha: 0.5),
                      fontSize: 9.5,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ],
              ),
            ],
          ),
        ],
      ),
    );
  }

  /// 6. Market Signals & Commercial Guidance
  Widget _buildMarketSignalsSection(
      BuildContext context, MarketSynthesisModel synthesis) {
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
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(6),
                    decoration: BoxDecoration(
                      color: const Color(0xFF6D1A95).withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: const Icon(
                      Icons.auto_awesome,
                      size: 16,
                      color: Color(0xFF6D1A95),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Text(
                    'Fira AI Market Intelligence Signals',
                    style: TextStyle(
                      color: AppTheme.getTextColor(context),
                      fontSize: 13,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ],
              ),
              IconButton(
                icon: Icon(
                  _showSignals ? Icons.expand_less : Icons.expand_more,
                  size: 20,
                  color: AppTheme.getTextColor(context).withValues(alpha: 0.6),
                ),
                onPressed: () {
                  setState(() {
                    _showSignals = !_showSignals;
                  });
                },
              ),
            ],
          ),
          const SizedBox(height: 6),
          Text(
            synthesis.executiveSummary,
            maxLines: _showSignals ? 10 : 2,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(
              color: AppTheme.getTextColor(context).withValues(alpha: 0.75),
              fontSize: 11.5,
              height: 1.4,
            ),
          ),
          if (_showSignals) ...[
            const Divider(height: 20),
            if (synthesis.opportunitySignals.isNotEmpty) ...[
              const Row(
                children: [
                  Icon(Icons.check_circle, size: 14, color: Colors.green),
                  SizedBox(width: 6),
                  Text(
                    'Opportunity Signals',
                    style: TextStyle(
                      color: Colors.green,
                      fontSize: 12,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 6),
              ...synthesis.opportunitySignals.take(3).map((sig) => Padding(
                    padding: const EdgeInsets.only(bottom: 4),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('• ', style: TextStyle(color: Colors.green)),
                        Expanded(
                          child: Text(
                            sig,
                            style: TextStyle(
                              color: AppTheme.getTextColor(context).withValues(alpha: 0.7),
                              fontSize: 11,
                            ),
                          ),
                        ),
                      ],
                    ),
                  )),
            ],
            if (synthesis.riskSignals.isNotEmpty) ...[
              const SizedBox(height: 10),
              const Row(
                children: [
                  Icon(Icons.warning_amber_rounded, size: 14, color: Colors.orange),
                  SizedBox(width: 6),
                  Text(
                    'Risk & Compliance Signals',
                    style: TextStyle(
                      color: Colors.orange,
                      fontSize: 12,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 6),
              ...synthesis.riskSignals.take(3).map((sig) => Padding(
                    padding: const EdgeInsets.only(bottom: 4),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('• ', style: TextStyle(color: Colors.orange)),
                        Expanded(
                          child: Text(
                            sig,
                            style: TextStyle(
                              color: AppTheme.getTextColor(context).withValues(alpha: 0.7),
                              fontSize: 11,
                            ),
                          ),
                        ),
                      ],
                    ),
                  )),
            ],
          ],
        ],
      ),
    );
  }

  /// 7. Macro Card (ICE Futures)
  Widget _buildMacroCard(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF4A0E66), Color(0xFF6D1A95)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(18),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'ICE NY ARABICA \'C\' FUTURES',
            style: TextStyle(
              color: Colors.white.withValues(alpha: 0.7),
              fontSize: 11,
              fontWeight: FontWeight.bold,
              letterSpacing: 0.5,
            ),
          ),
          const SizedBox(height: 6),
          const Row(
            crossAxisAlignment: CrossAxisAlignment.baseline,
            textBaseline: TextBaseline.alphabetic,
            children: [
              Text(
                '248.50¢',
                style: TextStyle(
                  color: Colors.white,
                  fontSize: 28,
                  fontWeight: FontWeight.bold,
                ),
              ),
              SizedBox(width: 8),
              Text(
                '+4.20¢ Firm',
                style: TextStyle(
                  color: Colors.greenAccent,
                  fontSize: 13,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ],
          ),
          const SizedBox(height: 4),
          Text(
            'Global benchmark active month base. Ethiopian specialty commands premium differentials over this floor.',
            style: TextStyle(
              color: Colors.white.withValues(alpha: 0.8),
              fontSize: 11.5,
            ),
          ),
        ],
      ),
    );
  }

  /// 8. Shipping Calendar Timeline
  Widget _buildShippingCalendarCard(BuildContext context) {
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
          const Text(
            'Annual Ethiopian Export Shipping Cycle',
            style: TextStyle(
              color: Color(0xFF6D1A95),
              fontSize: 13,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 12),
          _buildTimelineStep(
            context,
            'Oct – Dec',
            'Harvest & Wet Milling',
            'Cherry picking across Sidama, Guji, and Yirgacheffe.',
          ),
          const SizedBox(height: 8),
          _buildTimelineStep(
            context,
            'Jan – Mar',
            'Dry Hulling & Cupping',
            'Parchment resting, optical sorting & sample dispatch.',
          ),
          const SizedBox(height: 8),
          _buildTimelineStep(
            context,
            'Feb – Aug',
            'Peak Corridor Dispatch',
            'ECTA inspection and Addis-Djibouti container shipments.',
          ),
        ],
      ),
    );
  }

  Widget _buildTimelineStep(
      BuildContext context, String months, String title, String subtitle) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          width: 75,
          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 4),
          decoration: BoxDecoration(
            color: const Color(0xFF6D1A95).withValues(alpha: 0.1),
            borderRadius: BorderRadius.circular(8),
          ),
          child: Text(
            months,
            textAlign: TextAlign.center,
            style: const TextStyle(
              color: Color(0xFF6D1A95),
              fontSize: 11,
              fontWeight: FontWeight.bold,
            ),
          ),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style: TextStyle(
                  color: AppTheme.getTextColor(context),
                  fontSize: 12,
                  fontWeight: FontWeight.bold,
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
      ],
    );
  }
}

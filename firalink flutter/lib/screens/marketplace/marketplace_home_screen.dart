import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/auth_provider.dart';
import '../../services/api_exception.dart';
import '../../services/marketplace_service.dart';
import '../../theme/app_theme.dart';
import '../../widgets/fira_error_state.dart';
import '../../router/app_router.dart';
import 'widgets/verification_prompt_dialog.dart';
class MarketplaceHomeScreen extends StatefulWidget {
  const MarketplaceHomeScreen({super.key});

  @override
  State<MarketplaceHomeScreen> createState() => _MarketplaceHomeScreenState();
}

class _MarketplaceHomeScreenState extends State<MarketplaceHomeScreen> {
  final MarketplaceService _marketplaceService = MarketplaceService();
  final TextEditingController _searchController = TextEditingController();

  List<dynamic> _listings = [];
  bool _isLoading = true;
  Object? _loadError;
  String? _selectedOrigin;
  String? _selectedProcess;

  final List<String> _origins = [
    'All Origins',
    'Sidama',
    'Yirgacheffe',
    'Guji',
    'Limu',
    'Harrar',
    'Jimma',
  ];

  final List<String> _processes = [
    'All Processes',
    'Washed',
    'Natural',
    'Anaerobic',
    'Honey',
  ];

  @override
  void initState() {
    super.initState();
    _fetchListings();
  }

  Future<void> _fetchListings() async {
    setState(() {
      _isLoading = true;
      _loadError = null;
    });
    try {
      final res = requireSuccess(await _marketplaceService.getListings(
        search: _searchController.text.trim(),
        origin: (_selectedOrigin == null || _selectedOrigin == 'All Origins') ? null : _selectedOrigin,
        processing: (_selectedProcess == null || _selectedProcess == 'All Processes') ? null : _selectedProcess?.toLowerCase(),
      ));

      final data = jsonDecode(res.body);
      if (mounted) {
        setState(() {
          final raw = data['data'];
          if (raw is List) {
            _listings = raw;
          } else if (raw is Map && raw['data'] is List) {
            _listings = raw['data'];
          } else {
            _listings = [];
          }
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _listings = [];
          _loadError = e;
          _isLoading = false;
        });
      }
    }
  }

  void _openSellerPortal() {
    final auth = context.read<AuthProvider>();
    if (!auth.isAuthenticated) {
      VerificationPromptDialog.showAuthRequired(
        context,
        actionDescription: 'access your exporter or producer trading dashboard',
      );
      return;
    }
    Navigator.pushNamed(context, AppRouter.marketplaceSellerPortal);
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Scaffold(
      appBar: AppBar(
        title: const Text(
          'Coffee Marketplace',
          style: TextStyle(fontWeight: FontWeight.bold),
        ),
        centerTitle: false,
        actions: [
          IconButton(
            icon: const Icon(Icons.receipt_long_outlined),
            tooltip: 'Buyer RFQs',
            onPressed: () => Navigator.pushNamed(context, AppRouter.marketplaceRfqs),
          ),
          IconButton(
            icon: const Icon(Icons.storefront_outlined),
            tooltip: 'Seller Portal',
            onPressed: _openSellerPortal,
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _fetchListings,
        color: AppTheme.primary,
        child: CustomScrollView(
          slivers: [
            // Search & Ticker Header
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.all(16.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Search Bar
                    TextField(
                      controller: _searchController,
                      onSubmitted: (_) => _fetchListings(),
                      decoration: InputDecoration(
                        hintText: 'Search lots, washing stations, origins...',
                        prefixIcon: const Icon(Icons.search, color: AppTheme.primary),
                        suffixIcon: _searchController.text.isNotEmpty
                            ? IconButton(
                                icon: const Icon(Icons.clear),
                                onPressed: () {
                                  _searchController.clear();
                                  _fetchListings();
                                },
                              )
                            : null,
                        filled: true,
                        fillColor: isDark ? AppTheme.surfaceDark : Colors.grey.shade100,
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(16),
                          borderSide: BorderSide.none,
                        ),
                        contentPadding: const EdgeInsets.symmetric(vertical: 14),
                      ),
                    ),

                    const SizedBox(height: 14),

                    // Regulatory Price Ticker Banner
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                          colors: [
                            AppTheme.primary.withValues(alpha: 0.12),
                            Colors.purple.shade50.withValues(alpha: isDark ? 0.05 : 0.8),
                          ],
                        ),
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(color: AppTheme.primary.withValues(alpha: 0.2)),
                      ),
                      child: Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.all(6),
                            decoration: BoxDecoration(
                              color: AppTheme.primary,
                              shape: BoxShape.circle,
                            ),
                            child: const Icon(Icons.verified, color: Colors.white, size: 14),
                          ),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  'ECTA Minimum Price Compliant',
                                  style: TextStyle(
                                    fontSize: 12,
                                    fontWeight: FontWeight.bold,
                                    color: isDark ? Colors.purple.shade200 : AppTheme.primary,
                                  ),
                                ),
                                const Text(
                                  'All contract offers benchmarked with official Ethiopian regulatory daily rates.',
                                  style: TextStyle(fontSize: 11, color: Colors.grey),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),

                    const SizedBox(height: 12),

                    // Export Docs Studio Enterprise Banner
                    InkWell(
                      onTap: () => Navigator.pushNamed(context, AppRouter.exportDocsStudio),
                      borderRadius: BorderRadius.circular(14),
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                        decoration: BoxDecoration(
                          gradient: const LinearGradient(
                            colors: [Color(0xFF4A0E66), Color(0xFF6D1A95)],
                          ),
                          borderRadius: BorderRadius.circular(14),
                          boxShadow: [
                            BoxShadow(
                              color: const Color(0xFF6D1A95).withValues(alpha: 0.25),
                              blurRadius: 8,
                              offset: const Offset(0, 3),
                            ),
                          ],
                        ),
                        child: Row(
                          children: [
                            Container(
                              padding: const EdgeInsets.all(8),
                              decoration: BoxDecoration(
                                color: Colors.white.withValues(alpha: 0.15),
                                borderRadius: BorderRadius.circular(10),
                              ),
                              child: const Icon(Icons.description, color: Colors.amber, size: 20),
                            ),
                            const SizedBox(width: 12),
                            const Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Row(
                                    children: [
                                      Text(
                                        'Fira Export Docs Studio',
                                        style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Colors.white),
                                      ),
                                      SizedBox(width: 6),
                                      Text(
                                        'NEW',
                                        style: TextStyle(fontSize: 9, fontWeight: FontWeight.w800, color: Colors.amber),
                                      ),
                                    ],
                                  ),
                                  SizedBox(height: 2),
                                  Text(
                                    '30 Free Templates • Invoices, Packing Lists & Weight Statements in PDF/Excel',
                                    style: TextStyle(fontSize: 11, color: Colors.white70),
                                  ),
                                ],
                              ),
                            ),
                            const Icon(Icons.chevron_right, color: Colors.white70, size: 20),
                          ],
                        ),
                      ),
                    ),

                    const SizedBox(height: 16),

                    // Quick B2B Navigation Hub Pills
                    SingleChildScrollView(
                      scrollDirection: Axis.horizontal,
                      child: Row(
                        children: [
                          ActionChip(
                            avatar: const Icon(Icons.receipt_long, size: 16, color: AppTheme.primary),
                            label: const Text('Buyer RFQs', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600)),
                            backgroundColor: isDark ? AppTheme.surfaceDark : Colors.grey.shade100,
                            side: BorderSide(color: isDark ? Colors.white12 : Colors.grey.shade300),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                            onPressed: () => Navigator.pushNamed(context, AppRouter.marketplaceRfqs),
                          ),
                          const SizedBox(width: 8),
                          ActionChip(
                            avatar: const Icon(Icons.candlestick_chart, size: 16, color: AppTheme.primary),
                            label: const Text('Daily Market Prices', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600)),
                            backgroundColor: isDark ? AppTheme.surfaceDark : Colors.grey.shade100,
                            side: BorderSide(color: isDark ? Colors.white12 : Colors.grey.shade300),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                            onPressed: () => Navigator.pushNamed(context, AppRouter.coffeeMarket),
                          ),
                          const SizedBox(width: 8),
                          ActionChip(
                            avatar: const Icon(Icons.storefront, size: 16, color: AppTheme.primary),
                            label: const Text('Seller Portal', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600)),
                            backgroundColor: isDark ? AppTheme.surfaceDark : Colors.grey.shade100,
                            side: BorderSide(color: isDark ? Colors.white12 : Colors.grey.shade300),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                            onPressed: _openSellerPortal,
                          ),
                          const SizedBox(width: 8),
                          ActionChip(
                            avatar: const Icon(Icons.calculate_outlined, size: 16, color: AppTheme.primary),
                            label: const Text('Export Toolkit', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600)),
                            backgroundColor: isDark ? AppTheme.surfaceDark : Colors.grey.shade100,
                            side: BorderSide(color: isDark ? Colors.white12 : Colors.grey.shade300),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                            onPressed: () => Navigator.pushNamed(context, AppRouter.coffeeToolkit),
                          ),
                          const SizedBox(width: 8),
                          ActionChip(
                            avatar: const Icon(Icons.file_copy_outlined, size: 16, color: Color(0xFF6D1A95)),
                            label: const Text('Export Docs Studio', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF6D1A95))),
                            backgroundColor: const Color(0xFF6D1A95).withValues(alpha: 0.1),
                            side: const BorderSide(color: Color(0xFF6D1A95)),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                            onPressed: () => Navigator.pushNamed(context, AppRouter.exportDocsStudio),
                          ),
                        ],
                      ),
                    ),

                    const SizedBox(height: 14),

                    // Origin Categories
                    SizedBox(
                      height: 36,
                      child: ListView.separated(
                        scrollDirection: Axis.horizontal,
                        itemCount: _origins.length,
                        separatorBuilder: (context, index) => const SizedBox(width: 8),
                        itemBuilder: (context, index) {
                          final origin = _origins[index];
                          final isSelected = (_selectedOrigin == null && origin == 'All Origins') ||
                              _selectedOrigin == origin;

                          return ChoiceChip(
                            label: Text(origin),
                            selected: isSelected,
                            onSelected: (val) {
                              setState(() {
                                _selectedOrigin = origin == 'All Origins' ? null : origin;
                              });
                              _fetchListings();
                            },
                            selectedColor: AppTheme.primary,
                            labelStyle: TextStyle(
                              color: isSelected ? Colors.white : (isDark ? Colors.white70 : Colors.black87),
                              fontSize: 12,
                              fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                            ),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(12),
                              side: BorderSide(
                                color: isSelected ? AppTheme.primary : (isDark ? Colors.white10 : Colors.grey.shade300),
                              ),
                            ),
                          );
                        },
                      ),
                    ),

                    const SizedBox(height: 8),

                    // Processing Method Filter
                    SizedBox(
                      height: 34,
                      child: ListView.separated(
                        scrollDirection: Axis.horizontal,
                        itemCount: _processes.length,
                        separatorBuilder: (context, index) => const SizedBox(width: 8),
                        itemBuilder: (context, index) {
                          final proc = _processes[index];
                          final isSelected = (_selectedProcess == null && proc == 'All Processes') ||
                              _selectedProcess == proc;

                          return ChoiceChip(
                            label: Text(proc),
                            selected: isSelected,
                            onSelected: (val) {
                              setState(() {
                                _selectedProcess = proc == 'All Processes' ? null : proc;
                              });
                              _fetchListings();
                            },
                            selectedColor: isDark ? Colors.purple.shade900 : const Color(0xFFF3E8FF),
                            labelStyle: TextStyle(
                              color: isSelected ? AppTheme.primary : (isDark ? Colors.white60 : Colors.grey.shade700),
                              fontSize: 11,
                              fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                            ),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(10),
                              side: BorderSide(
                                color: isSelected ? AppTheme.primary : (isDark ? Colors.white10 : Colors.grey.shade200),
                              ),
                            ),
                          );
                        },
                      ),
                    ),
                  ],
                ),
              ),
            ),

            // Listings Grid
            if (_isLoading)
              const SliverFillRemaining(
                child: Center(child: CircularProgressIndicator(color: AppTheme.primary)),
              )
            else if (_loadError != null)
              SliverFillRemaining(
                hasScrollBody: false,
                child: FiraErrorState(error: _loadError, onRetry: _fetchListings),
              )
            else if (_listings.isEmpty)
              SliverFillRemaining(
                child: Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.inventory_2_outlined, size: 48, color: Colors.grey.shade400),
                      const SizedBox(height: 12),
                      const Text(
                        'No coffee lots found',
                        style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                      ),
                      const SizedBox(height: 4),
                      const Text(
                        'Try searching for another origin or region',
                        style: TextStyle(color: Colors.grey, fontSize: 13),
                      ),
                    ],
                  ),
                ),
              )
            else
              SliverPadding(
                padding: const EdgeInsets.fromLTRB(16, 8, 16, 100),
                sliver: SliverList(
                  delegate: SliverChildBuilderDelegate(
                    (context, index) {
                      final lot = _listings[index];
                      return _buildLotCard(context, lot, isDark);
                    },
                    childCount: _listings.length,
                  ),
                ),
              ),
          ],
        ),
      ),
      extendBody: true,
      floatingActionButton: Padding(
        padding: const EdgeInsets.only(bottom: 72.0),
        child: FloatingActionButton.extended(
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
          icon: const Icon(Icons.add_shopping_cart, color: Colors.white),
          label: const Text('Post RFQ', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
        ),
      ),
    );
  }

  Widget _buildLotCard(BuildContext context, dynamic lot, bool isDark) {
    final title = lot['title'] ?? 'Ethiopian Specialty Lot';
    final origin = lot['origin_region'] ?? 'Ethiopia';
    final process = lot['processing_method']?.toString().replaceAll('_', ' ').toUpperCase() ?? 'WASHED';
    final grade = lot['grade']?.toString().replaceAll('_', ' ').toUpperCase() ?? 'G1';
    final cuppingScore = lot['cupping_score'];
    final price = lot['indicative_price_usd_per_lb'] ?? '0.00';
    final availableBags = lot['available_quantity_bags'] ?? 0;
    final primaryImageUrl = lot['primary_image']?['url'];
    final sellerName = lot['seller']?['seller_profile']?['company_name'] ?? lot['seller']?['name'] ?? 'Verified Exporter';

    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      elevation: 0,
      color: isDark ? AppTheme.surfaceDark : Colors.white,
      child: InkWell(
        onTap: () {
          Navigator.pushNamed(
            context,
            AppRouter.coffeeListingDetail,
            arguments: lot['slug'] ?? lot['id'],
          );
        },
        borderRadius: BorderRadius.circular(20),
        child: Padding(
          padding: const EdgeInsets.all(12.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Top Image / Visual preview
              ClipRRect(
                borderRadius: BorderRadius.circular(16),
                child: Stack(
                  children: [
                    Container(
                      height: 160,
                      width: double.infinity,
                      color: isDark ? Colors.black26 : Colors.grey.shade100,
                      child: primaryImageUrl != null
                          ? Image.network(
                              primaryImageUrl,
                              fit: BoxFit.cover,
                              errorBuilder: (context, error, stackTrace) => const Center(
                                child: Icon(Icons.coffee, size: 48, color: Colors.grey),
                              ),
                            )
                          : const Center(
                              child: Icon(Icons.coffee, size: 48, color: Colors.grey),
                            ),
                    ),

                    // Origin Pill
                    Positioned(
                      top: 10,
                      left: 10,
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                        decoration: BoxDecoration(
                          color: Colors.black.withValues(alpha: 0.7),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Text(
                          origin,
                          style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold),
                        ),
                      ),
                    ),

                    // SCA Score
                    if (cuppingScore != null)
                      Positioned(
                        top: 10,
                        right: 10,
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                          decoration: BoxDecoration(
                            gradient: const LinearGradient(
                              colors: [AppTheme.primary, Color(0xFF9333EA)],
                            ),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Text(
                            '★ $cuppingScore SCA',
                            style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold),
                          ),
                        ),
                      ),
                  ],
                ),
              ),

              const SizedBox(height: 12),

              // Seller Credential
              Row(
                children: [
                  const Icon(Icons.verified, size: 14, color: Colors.green),
                  const SizedBox(width: 4),
                  Expanded(
                    child: Text(
                      sellerName,
                      style: const TextStyle(fontSize: 11, color: Colors.grey, fontWeight: FontWeight.w500),
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 4),

              // Title
              Text(
                title,
                style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),

              const SizedBox(height: 8),

              // Spec badges
              Row(
                children: [
                  Flexible(
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                      decoration: BoxDecoration(
                        color: AppTheme.primary.withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Text(
                        process,
                        style: const TextStyle(fontSize: 10, color: AppTheme.primary, fontWeight: FontWeight.bold),
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  ),
                  const SizedBox(width: 6),
                  Flexible(
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                      decoration: BoxDecoration(
                        color: Colors.grey.withValues(alpha: 0.15),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Text(
                        grade,
                        style: TextStyle(
                          fontSize: 10,
                          color: isDark ? Colors.white70 : Colors.black87,
                          fontWeight: FontWeight.bold,
                        ),
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      '$availableBags Bags',
                      textAlign: TextAlign.end,
                      style: const TextStyle(fontSize: 11, color: Colors.grey, fontWeight: FontWeight.w500),
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 12),
              const Divider(height: 1),
              const SizedBox(height: 10),

              // Price & Action
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('Indicative FOB', style: TextStyle(fontSize: 10, color: Colors.grey)),
                        Text(
                          '\$$price / lb',
                          style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w900),
                          overflow: TextOverflow.ellipsis,
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 12),
                  ElevatedButton(
                    onPressed: () {
                      Navigator.pushNamed(
                        context,
                        AppRouter.coffeeListingDetail,
                        arguments: lot['slug'] ?? lot['id'],
                      );
                    },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppTheme.primary,
                      foregroundColor: Colors.white,
                      elevation: 0,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                    ),
                    child: const Text('View Lot', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

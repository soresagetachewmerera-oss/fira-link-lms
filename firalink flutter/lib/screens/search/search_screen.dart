import 'dart:async';
import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../l10n/app_localizations.dart';
import '../../models/course.dart';
import '../../router/app_router.dart';
import '../../services/api_exception.dart';
import '../../services/course_service.dart';
import '../../services/marketplace_service.dart';
import '../../services/trade_service.dart';
import '../../theme/fira_colors.dart';
import '../course_detail/course_detail_screen.dart';
import '../marketplace/exporters_screen.dart';

/// One search result row. Everything shown comes from the API; nothing is invented here.
class SearchHit {
  const SearchHit({required this.id, required this.title, this.subtitle, this.badge, this.slug});

  final int id;
  final String title;
  final String? subtitle;
  final String? badge;
  final String? slug;
}

/// Where search results come from. The default calls the real endpoints; tests inject a fake.
class SearchBackend {
  const SearchBackend();

  Future<List<SearchHit>> lots(String query) async {
    final res = requireSuccess(await MarketplaceService().getListings(search: query));
    final decoded = jsonDecode(res.body);
    final raw = decoded is Map ? decoded['data'] : null;
    final list = raw is Map ? raw['data'] : raw;
    if (list is! List) return const [];
    return list.whereType<Map>().map((lot) {
      final region = [lot['origin_region'], lot['sub_region']].where((v) => v != null && '$v'.isNotEmpty).join(', ');
      return SearchHit(
        id: int.tryParse('${lot['id']}') ?? 0,
        slug: lot['slug']?.toString(),
        title: lot['title']?.toString() ?? '',
        subtitle: region.isEmpty ? null : region,
      );
    }).where((h) => h.title.isNotEmpty).toList();
  }

  /// Needs a signed-in user; throws [ApiException] with 401 for guests.
  Future<List<SearchHit>> exporters(String query, AppLocalizations l10n) async {
    final cards = await TradeService().exporters(search: query);
    return cards
        .map((e) => SearchHit(
              id: e.id,
              title: e.name,
              subtitle: e.city,
              badge: e.tier >= 1 ? l10n.translate('sellerVerifiedTier', {'tier': e.tier}) : null,
            ))
        .toList();
  }

  Future<List<SearchHit>> courses(String query) async {
    final res = requireSuccess(await CourseService().fetchCourses(search: query));
    final decoded = jsonDecode(res.body);
    final raw = decoded is Map ? decoded['data'] : null;
    final list = raw is Map ? raw['data'] : raw;
    if (list is! List) return const [];
    return list
        .whereType<Map<String, dynamic>>()
        .map(Course.fromJson)
        .where((c) => c.title.isNotEmpty)
        .map((c) => SearchHit(id: c.id, title: c.title, subtitle: c.instructorName))
        .toList();
  }
}

enum _Category { all, lots, exporters, courses }

class _Section {
  List<SearchHit> hits = const [];
  Object? error;
  bool loading = false;
}

class SearchScreen extends StatefulWidget {
  final String? initialQuery;
  final SearchBackend backend;

  const SearchScreen({super.key, this.initialQuery, this.backend = const SearchBackend()});

  @override
  State<SearchScreen> createState() => _SearchScreenState();
}

class _SearchScreenState extends State<SearchScreen> {
  static const _recentKey = 'recent_searches';
  static const _maxRecent = 6;
  static const _minLength = 2;

  late final TextEditingController _searchController;
  _Category _selected = _Category.all;
  List<String> _recent = const [];
  Timer? _debounce;
  int _generation = 0;
  final Map<_Category, _Section> _sections = {
    _Category.lots: _Section(),
    _Category.exporters: _Section(),
    _Category.courses: _Section(),
  };

  @override
  void initState() {
    super.initState();
    _searchController = TextEditingController(text: widget.initialQuery ?? '');
    _loadRecent();
    if (_query.length >= _minLength) {
      WidgetsBinding.instance.addPostFrameCallback((_) => _run());
    }
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _searchController.dispose();
    super.dispose();
  }

  String get _query => _searchController.text.trim();

  Future<void> _loadRecent() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      if (!mounted) return;
      setState(() => _recent = prefs.getStringList(_recentKey) ?? const []);
    } catch (_) {
      // Recent searches are a convenience; the screen works without them.
    }
  }

  Future<void> _saveRecent(String term) async {
    final updated = [term, ..._recent.where((t) => t.toLowerCase() != term.toLowerCase())].take(_maxRecent).toList();
    setState(() => _recent = updated);
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setStringList(_recentKey, updated);
    } catch (_) {}
  }

  Future<void> _clearRecent() async {
    setState(() => _recent = const []);
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove(_recentKey);
    } catch (_) {}
  }

  void _onChanged(String _) {
    setState(() {});
    _debounce?.cancel();
    if (_query.length < _minLength) return;
    _debounce = Timer(const Duration(milliseconds: 400), _run);
  }

  void _useTerm(String term) {
    _searchController.text = term;
    _searchController.selection = TextSelection.collapsed(offset: term.length);
    setState(() {});
    _run();
  }

  Future<void> _run() async {
    final query = _query;
    if (query.length < _minLength) return;
    final generation = ++_generation;
    final l10n = AppLocalizations.of(context);
    setState(() {
      for (final s in _sections.values) {
        s
          ..loading = true
          ..error = null;
      }
    });

    Future<void> load(_Category c, Future<List<SearchHit>> Function() fetch) async {
      List<SearchHit> hits = const [];
      Object? error;
      try {
        hits = await fetch();
      } catch (e) {
        error = e;
      }
      if (!mounted || generation != _generation) return;
      setState(() {
        _sections[c]!
          ..hits = hits
          ..error = error
          ..loading = false;
      });
    }

    await Future.wait([
      load(_Category.lots, () => widget.backend.lots(query)),
      load(_Category.exporters, () => widget.backend.exporters(query, l10n)),
      load(_Category.courses, () => widget.backend.courses(query)),
    ]);
    if (mounted && generation == _generation) _saveRecent(query);
  }

  String _label(AppLocalizations l10n, _Category c) => switch (c) {
        _Category.all => l10n.all,
        _Category.lots => l10n.searchCategoryLots,
        _Category.exporters => l10n.searchCategoryExporters,
        _Category.courses => l10n.searchCategoryCourses,
      };

  @override
  Widget build(BuildContext context) {
    final fira = context.fira;
    final l10n = context.l10n;

    return Scaffold(
      backgroundColor: fira.surface,
      body: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 12, 20, 8),
              child: Row(
                children: [
                  Expanded(
                    child: Container(
                      height: 52,
                      decoration: BoxDecoration(
                        color: fira.card,
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(
                          color: _query.isNotEmpty ? fira.brandPrimary : fira.brandTint,
                          width: _query.isNotEmpty ? 1.5 : 1.0,
                        ),
                      ),
                      child: Row(
                        children: [
                          const SizedBox(width: 14),
                          Icon(Icons.search_rounded, color: fira.mutedText, size: 20),
                          const SizedBox(width: 10),
                          Expanded(
                            child: TextField(
                              controller: _searchController,
                              autofocus: widget.initialQuery == null,
                              textInputAction: TextInputAction.search,
                              onChanged: _onChanged,
                              onSubmitted: (_) => _run(),
                              style: TextStyle(color: fira.onSurface, fontSize: 14, fontWeight: FontWeight.w500),
                              decoration: InputDecoration(
                                hintText: l10n.searchGlobalHint,
                                hintStyle: TextStyle(color: fira.mutedText, fontSize: 14),
                                border: InputBorder.none,
                                isDense: true,
                              ),
                            ),
                          ),
                          if (_searchController.text.isNotEmpty)
                            IconButton(
                              tooltip: MaterialLocalizations.of(context).clearButtonTooltip,
                              icon: Icon(Icons.close_rounded, size: 18, color: fira.mutedText),
                              onPressed: () {
                                _searchController.clear();
                                _generation++;
                                setState(() {});
                              },
                            ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Semantics(
                    button: true,
                    label: l10n.cancel,
                    child: InkWell(
                      onTap: () => Navigator.of(context).pop(),
                      borderRadius: BorderRadius.circular(12),
                      child: Container(
                        constraints: const BoxConstraints(minHeight: 44, minWidth: 44),
                        alignment: Alignment.center,
                        child: Text(
                          l10n.cancel,
                          style: TextStyle(fontSize: 14, fontWeight: FontWeight.w600, color: fira.brandPrimary),
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
            SizedBox(
              height: 48,
              child: ListView.separated(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 6),
                itemCount: _Category.values.length,
                separatorBuilder: (context, index) => const SizedBox(width: 8),
                itemBuilder: (context, index) {
                  final cat = _Category.values[index];
                  final isSelected = _selected == cat;
                  return ChoiceChip(
                    label: Text(
                      _label(l10n, cat),
                      style: TextStyle(
                        fontSize: 13,
                        fontWeight: isSelected ? FontWeight.w600 : FontWeight.w500,
                        color: isSelected ? fira.card : fira.onSurface,
                      ),
                    ),
                    selected: isSelected,
                    selectedColor: fira.brandPrimary,
                    backgroundColor: fira.chipUnselectedBg,
                    showCheckmark: false,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                      side: BorderSide(color: isSelected ? fira.brandPrimary : fira.brandTint),
                    ),
                    onSelected: (_) => setState(() => _selected = cat),
                  );
                },
              ),
            ),
            const SizedBox(height: 8),
            Expanded(
              child: _query.length < _minLength ? _buildStart(fira, l10n) : _buildResults(fira, l10n),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStart(FiraColors fira, AppLocalizations l10n) {
    return ListView(
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
      children: [
        Text(l10n.searchTypeToStart, style: TextStyle(fontSize: 14, color: fira.mutedText)),
        if (_recent.isNotEmpty) ...[
          const SizedBox(height: 24),
          Row(
            children: [
              Expanded(
                child: Text(
                  l10n.searchRecent,
                  style: TextStyle(fontSize: 15, fontWeight: FontWeight.w600, color: fira.onSurface),
                ),
              ),
              TextButton(onPressed: _clearRecent, child: Text(l10n.searchClearRecent)),
            ],
          ),
          const SizedBox(height: 8),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: _recent
                .map((term) => ActionChip(
                      avatar: Icon(Icons.history_rounded, size: 14, color: fira.mutedText),
                      label: Text(term, style: TextStyle(fontSize: 13, color: fira.onSurface)),
                      backgroundColor: fira.chipUnselectedBg,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                        side: BorderSide(color: fira.brandTint),
                      ),
                      onPressed: () => _useTerm(term),
                    ))
                .toList(),
          ),
        ],
      ],
    );
  }

  Widget _buildResults(FiraColors fira, AppLocalizations l10n) {
    final shown = _selected == _Category.all ? [_Category.lots, _Category.exporters, _Category.courses] : [_selected];
    final anyLoading = shown.any((c) => _sections[c]!.loading);
    final allEmpty = shown.every((c) {
      final s = _sections[c]!;
      return !s.loading && s.error == null && s.hits.isEmpty;
    });

    if (!anyLoading && allEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Text(
            l10n.translate('searchNoResults', {'query': _query}),
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 14, color: fira.mutedText),
          ),
        ),
      );
    }

    return ListView(
      padding: const EdgeInsets.fromLTRB(20, 4, 20, 24),
      children: [
        for (final c in shown) ..._buildSection(fira, l10n, c, showHeader: _selected == _Category.all),
      ],
    );
  }

  List<Widget> _buildSection(FiraColors fira, AppLocalizations l10n, _Category c, {required bool showHeader}) {
    final s = _sections[c]!;
    final header = Padding(
      padding: const EdgeInsets.only(top: 16, bottom: 8),
      child: Text(_label(l10n, c), style: TextStyle(fontSize: 15, fontWeight: FontWeight.w600, color: fira.onSurface)),
    );

    Widget? body;
    if (s.loading) {
      body = Padding(
        padding: const EdgeInsets.symmetric(vertical: 12),
        child: Center(child: SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2, color: fira.brandPrimary))),
      );
    } else if (s.error != null) {
      final signedOut = s.error is ApiException && (s.error as ApiException).isUnauthenticated;
      body = Text(
        signedOut && c == _Category.exporters ? l10n.searchExportersSignIn : l10n.searchSectionFailed,
        style: TextStyle(fontSize: 13, color: fira.mutedText),
      );
    } else if (s.hits.isEmpty) {
      // In "All", an empty section is simply left out.
      if (showHeader) return const [];
      body = Text(l10n.translate('searchNoResults', {'query': _query}), style: TextStyle(fontSize: 13, color: fira.mutedText));
    }

    if (body != null) {
      return [
        if (showHeader) header,
        body,
      ];
    }

    return [
      if (showHeader) header,
      for (final hit in s.hits) ...[
        _ResultTile(hit: hit, icon: _icon(c), onTap: () => _open(c, hit)),
        const SizedBox(height: 10),
      ],
    ];
  }

  IconData _icon(_Category c) => switch (c) {
        _Category.lots => Icons.inventory_2_outlined,
        _Category.exporters => Icons.storefront_outlined,
        _ => Icons.school_outlined,
      };

  void _open(_Category c, SearchHit hit) {
    switch (c) {
      case _Category.lots:
        Navigator.pushNamed(context, AppRouter.coffeeListingDetail, arguments: hit.slug ?? hit.id);
      case _Category.exporters:
        Navigator.of(context).push(MaterialPageRoute(builder: (_) => ExporterProfileScreen(organizationId: hit.id)));
      case _Category.courses:
        Navigator.of(context).push(MaterialPageRoute(builder: (_) => CourseDetailScreen(courseId: hit.id)));
      case _Category.all:
        break;
    }
  }
}

class _ResultTile extends StatelessWidget {
  const _ResultTile({required this.hit, required this.icon, required this.onTap});

  final SearchHit hit;
  final IconData icon;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final fira = context.fira;
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(22),
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: fira.card,
          borderRadius: BorderRadius.circular(22),
          border: Border.all(color: fira.brandTint),
        ),
        child: Row(
          children: [
            Container(
              width: 40,
              height: 40,
              decoration: BoxDecoration(color: fira.brandTint, borderRadius: BorderRadius.circular(12)),
              child: Icon(icon, color: fira.brandPrimary, size: 20),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(hit.title, maxLines: 2, overflow: TextOverflow.ellipsis, style: TextStyle(fontSize: 14, fontWeight: FontWeight.w600, color: fira.onSurface)),
                  if (hit.subtitle != null && hit.subtitle!.isNotEmpty) ...[
                    const SizedBox(height: 2),
                    Text(hit.subtitle!, maxLines: 1, overflow: TextOverflow.ellipsis, style: TextStyle(fontSize: 12, color: fira.mutedText)),
                  ],
                  if (hit.badge != null) ...[
                    const SizedBox(height: 6),
                    Text(hit.badge!, style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: fira.success)),
                  ],
                ],
              ),
            ),
            Icon(Icons.chevron_right_rounded, color: fira.mutedText, size: 20),
          ],
        ),
      ),
    );
  }
}

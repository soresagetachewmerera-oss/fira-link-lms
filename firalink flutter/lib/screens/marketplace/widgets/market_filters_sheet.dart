import 'package:flutter/material.dart';

import '../../../l10n/app_localizations.dart';
import '../../../models/trade_model.dart';
import '../../../widgets/trade_labels.dart';
import '../../../widgets/verification_labels.dart';

typedef CountLots = Future<int?> Function(MarketFilters filters);

/// Marketplace filters: region, process, grade, evidence level, seller tier, price.
/// "Show [n] lots" asks the server how many lots match. Returns the chosen filters.
class MarketFiltersSheet extends StatefulWidget {
  const MarketFiltersSheet({super.key, required this.initial, required this.countLots});

  final MarketFilters initial;
  final CountLots countLots;

  static const regions = ['Sidama', 'Yirgacheffe', 'Guji', 'Jimma', 'Harar', 'Limu'];

  static Future<MarketFilters?> show(BuildContext context, {required MarketFilters initial, required CountLots countLots}) {
    return showModalBottomSheet<MarketFilters>(
      context: context,
      isScrollControlled: true,
      showDragHandle: true,
      builder: (_) => MarketFiltersSheet(initial: initial, countLots: countLots),
    );
  }

  @override
  State<MarketFiltersSheet> createState() => _MarketFiltersSheetState();
}

class _MarketFiltersSheetState extends State<MarketFiltersSheet> {
  late MarketFilters _f = widget.initial;
  late final TextEditingController _min = TextEditingController(text: widget.initial.minPrice?.toString() ?? '');
  late final TextEditingController _max = TextEditingController(text: widget.initial.maxPrice?.toString() ?? '');
  int? _count;
  int _request = 0;

  @override
  void initState() {
    super.initState();
    _recount();
  }

  @override
  void dispose() {
    _min.dispose();
    _max.dispose();
    super.dispose();
  }

  Future<void> _recount() async {
    final mine = ++_request;
    setState(() => _count = null);
    final n = await widget.countLots(_f);
    if (mounted && mine == _request) setState(() => _count = n);
  }

  void _update(MarketFilters f) {
    setState(() => _f = f);
    _recount();
  }

  Set<String> _toggle(Set<String> set, String v) => set.contains(v) ? ({...set}..remove(v)) : {...set, v};

  Widget _section(String title, List<Widget> chips) => Padding(
        padding: const EdgeInsets.only(top: 16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(title, style: const TextStyle(fontWeight: FontWeight.w700)),
            const SizedBox(height: 8),
            Wrap(spacing: 8, runSpacing: 8, children: chips),
          ],
        ),
      );

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;

    return SafeArea(
      child: Padding(
        padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
        child: ListView(
          shrinkWrap: true,
          padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
          children: [
            Row(
              children: [
                Expanded(child: Text(l10n.marketFilters, style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700))),
                TextButton(
                  key: const Key('filters-reset'),
                  onPressed: () {
                    _min.clear();
                    _max.clear();
                    _update(const MarketFilters());
                  },
                  child: Text(l10n.filtersReset),
                ),
              ],
            ),
            _section(l10n.createLotRegion, [
              for (final r in MarketFiltersSheet.regions)
                FilterChip(label: Text(r), selected: _f.regions.contains(r), onSelected: (_) => _update(_f.copyWith(regions: _toggle(_f.regions, r)))),
            ]),
            _section(l10n.createLotProcess, [
              for (final p in ['washed', 'natural', 'honey'])
                FilterChip(
                  key: Key('filter-process-$p'),
                  label: Text(TradeLabels.process(l10n, p)),
                  selected: _f.processes.contains(p),
                  onSelected: (_) => _update(_f.copyWith(processes: _toggle(_f.processes, p))),
                ),
            ]),
            _section(l10n.createLotGrade, [
              for (final g in ['grade_1', 'grade_2', 'grade_3', 'g4_plus'])
                FilterChip(label: Text(TradeLabels.grade(l10n, g)), selected: _f.grades.contains(g), onSelected: (_) => _update(_f.copyWith(grades: _toggle(_f.grades, g)))),
            ]),
            _section(l10n.filtersEvidence, [
              for (final (value, label) in [
                (null, l10n.filtersAny),
                ('documented', l10n.filtersDocumentedPlus),
                ('lab_backed', l10n.filtersLabBackedPlus),
                ('inspected', VerificationLabels.level(l10n, 'inspected')),
              ])
                ChoiceChip(
                  key: Key('filter-level-${value ?? 'any'}'),
                  label: Text(label),
                  selected: _f.minLevel == value,
                  onSelected: (_) => _update(_f.copyWith(minLevel: () => value)),
                ),
            ]),
            _section(l10n.filtersSellerVerification, [
              for (final (value, label) in [(null, l10n.filtersTier1Plus), (2, l10n.filtersTier2Plus), (3, l10n.filtersTier3)])
                ChoiceChip(label: Text(label), selected: _f.minTier == value, onSelected: (_) => _update(_f.copyWith(minTier: () => value))),
            ]),
            Padding(
              padding: const EdgeInsets.only(top: 16),
              child: Text(l10n.filtersPrice, style: const TextStyle(fontWeight: FontWeight.w700)),
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _min,
                    keyboardType: const TextInputType.numberWithOptions(decimal: true),
                    decoration: InputDecoration(labelText: l10n.filtersMin),
                    onChanged: (v) => _update(_f.copyWith(minPrice: () => double.tryParse(v.trim()))),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: TextField(
                    controller: _max,
                    keyboardType: const TextInputType.numberWithOptions(decimal: true),
                    decoration: InputDecoration(labelText: l10n.filtersMax),
                    onChanged: (v) => _update(_f.copyWith(maxPrice: () => double.tryParse(v.trim()))),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 20),
            FilledButton(
              key: const Key('filters-show'),
              onPressed: () => Navigator.of(context).pop(_f),
              child: Text(_count == null ? l10n.marketFilters : l10n.translate('filtersShowLots', {'n': _count})),
            ),
          ],
        ),
      ),
    );
  }
}

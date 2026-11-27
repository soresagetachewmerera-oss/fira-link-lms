import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../l10n/app_localizations.dart';
import '../../models/market_watch.dart';
import '../../providers/market_watch_provider.dart';
import '../../screens/market/market_watch_screen.dart';
import '../../screens/home/widgets/home_design_constants.dart';
import '../../theme/fira_colors.dart';
import '../home_skeletons.dart';
import 'market_ui.dart' show MarketRefresher, formatMarketTime;
import 'market_watch_row.dart';
import 'market_detail_sheet.dart';
import '../../models/market_series_detail.dart';

/// Coffee market watch card (Home). Works for guests. States: loading skeleton, rows (each may be
/// stale or unavailable on its own), offline with the time of the last answer, error with Try again.
/// On Home it stays hidden until one of the Home rows has a stored price (server: home.visible).
/// Refreshes on open and every 15 minutes while visible; the data is a daily close or slower.
class MarketWatchCard extends StatefulWidget {
  const MarketWatchCard({
    super.key,
    this.provider,
    this.homeOnly = true,
    this.refreshInterval = const Duration(minutes: 15),
    this.detailActions,
    this.pinned = const {},
  });

  /// For tests; otherwise the app-wide [MarketWatchProvider] is used (or a private one if none is provided).
  final MarketWatchProvider? provider;
  final bool homeOnly;
  final Duration refreshInterval;

  /// Instruments on the person's watchlist: listed first on the full list.
  final Set<String> pinned;

  /// Signed-in actions shown in the detail sheet (watchlist, alert, Fira AI, CSV).
  final List<Widget> Function(BuildContext context, MarketSeriesDetail detail)? detailActions;

  @override
  State<MarketWatchCard> createState() => _MarketWatchCardState();
}

class _MarketWatchCardState extends State<MarketWatchCard> {
  MarketWatchProvider? _provider;
  bool _ownsProvider = false;
  MarketRefresher? _refresher;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (_provider != null) return;
    var provider = widget.provider;
    if (provider == null) {
      try {
        provider = Provider.of<MarketWatchProvider>(context, listen: false);
      } on ProviderNotFoundException {
        provider = MarketWatchProvider();
        _ownsProvider = true;
      }
    }
    _provider = provider;
    WidgetsBinding.instance.addPostFrameCallback((_) => _provider?.ensureLoaded());
    _refresher = MarketRefresher(onTick: provider.load, isVisible: _isVisible, interval: widget.refreshInterval)..start();
  }

  bool _isVisible() {
    if (!mounted) return false;
    final route = ModalRoute.of(context);
    return TickerMode.valuesOf(context).enabled && (route?.isCurrent ?? true);
  }

  void _open(BuildContext context, MarketWatchRow row) => showMarketDetailSheet(context, row, service: _provider!.service, actions: widget.detailActions);

  Future<void> _refresh() async => _refresher?.reportResult(await _provider!.load());

  @override
  void dispose() {
    _refresher?.dispose();
    if (_ownsProvider) _provider?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: _provider!,
      builder: (context, _) {
        final provider = _provider!;
        final snapshot = provider.snapshot;
        if (snapshot == null) {
          return provider.failed ? _error(context) : _skeleton(context);
        }
        final watch = snapshot.watch;
        if (widget.homeOnly && !watch.homeVisible) {
          return const SizedBox.shrink();
        }
        final rows = widget.homeOnly
            ? watch.homeRows
            : [...watch.rows.where((r) => widget.pinned.contains(r.key)), ...watch.rows.where((r) => !widget.pinned.contains(r.key))];
        return _card(context, snapshot, rows, provider.loading);
      },
    );
  }

  Widget _frame(BuildContext context, Widget child) {
    final fira = context.fira;
    // AppTheme's body styles lose the brand font family (copyWith replaces them); take it from the
    // button style, which keeps Montserrat with the Ethiopic fallback, so the card is always on brand.
    final brand = Theme.of(context).textTheme.labelLarge;
    return DefaultTextStyle.merge(
      style: TextStyle(fontFamily: brand?.fontFamily, fontFamilyFallback: brand?.fontFamilyFallback),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: HomeDesignConstants.horizontalPadding),
        child: DecoratedBox(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(HomeDesignConstants.radiusTile),
            gradient: LinearGradient(begin: Alignment.topCenter, end: Alignment.bottomCenter, colors: [fira.brandDeep, fira.brandDarker]),
          ),
          child: ClipRRect(borderRadius: BorderRadius.circular(HomeDesignConstants.radiusTile), child: child),
        ),
      ),
    );
  }

  Widget _title(BuildContext context) => Text(
    context.l10n.marketCardTitle,
    style: TextStyle(color: Theme.of(context).colorScheme.onPrimary, fontSize: 17, fontWeight: FontWeight.w700, height: 1.3),
  );

  Widget _skeleton(BuildContext context) {
    return Semantics(
      label: context.l10n.marketLoading,
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: HomeDesignConstants.horizontalPadding),
        child: BaseShimmer(
          child: Container(
            height: 330,
            decoration: BoxDecoration(color: context.fira.brandTint, borderRadius: BorderRadius.circular(HomeDesignConstants.radiusTile)),
          ),
        ),
      ),
    );
  }

  Widget _error(BuildContext context) {
    final l10n = context.l10n;
    final onDeep = Theme.of(context).colorScheme.onPrimary;
    return _frame(
      context,
      Padding(
        padding: const EdgeInsets.fromLTRB(16, 18, 16, 12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _title(context),
            const SizedBox(height: 8),
            Text(l10n.marketWatchError, style: TextStyle(color: onDeep.withValues(alpha: .74), fontSize: 14, height: 1.45)),
            const SizedBox(height: 4),
            TextButton.icon(
              onPressed: _refresh,
              style: TextButton.styleFrom(foregroundColor: onDeep, minimumSize: const Size(44, 44), padding: EdgeInsets.zero),
              icon: const Icon(Icons.refresh_rounded, size: 18),
              label: Text(l10n.marketTryAgain),
            ),
          ],
        ),
      ),
    );
  }

  Widget _card(BuildContext context, MarketWatchSnapshot snapshot, List<MarketWatchRow> rows, bool loading) {
    final l10n = context.l10n;
    final fira = context.fira;
    final onDeep = Theme.of(context).colorScheme.onPrimary;
    final muted = onDeep.withValues(alpha: .74);
    final hairline = onDeep.withValues(alpha: .12);
    final watch = snapshot.watch;
    final offlineSince = snapshot.fromCache ? snapshot.receivedAt : null;
    final sources = watch.sourcesShort.join(', ');

    return _frame(
      context,
      Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 4, 8),
            child: Row(
              children: [
                Expanded(
                  child: Wrap(
                    spacing: 10,
                    runSpacing: 6,
                    crossAxisAlignment: WrapCrossAlignment.center,
                    children: [
                      Semantics(header: true, child: _title(context)),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                        decoration: BoxDecoration(color: onDeep.withValues(alpha: .10), borderRadius: BorderRadius.circular(999)),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Container(
                              width: 6,
                              height: 6,
                              decoration: BoxDecoration(
                                shape: BoxShape.circle,
                                color: watch.status == MarketWatchStatus.unavailable || watch.status == MarketWatchStatus.stale
                                    ? fira.marketFlat
                                    : fira.marketUp,
                              ),
                            ),
                            const SizedBox(width: 6),
                            Flexible(
                              child: Text(
                                watch.statusLabel,
                                style: TextStyle(color: onDeep, fontSize: 12, fontWeight: FontWeight.w600),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
                SizedBox(
                  width: 44,
                  height: 44,
                  child: loading
                      ? Center(
                          child: SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: onDeep)),
                        )
                      : IconButton(
                          onPressed: _refresh,
                          tooltip: l10n.marketWatchRefresh,
                          icon: Icon(Icons.refresh_rounded, color: onDeep, size: 20),
                        ),
                ),
              ],
            ),
          ),
          if (offlineSince != null)
            Container(
              margin: const EdgeInsets.fromLTRB(16, 0, 16, 8),
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
              decoration: BoxDecoration(color: onDeep.withValues(alpha: .08), borderRadius: BorderRadius.circular(12)),
              child: Row(
                children: [
                  Icon(Icons.cloud_off_rounded, size: 16, color: muted),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(l10n.marketWatchOffline(formatMarketTime(offlineSince)), style: TextStyle(color: onDeep, fontSize: 12.5, height: 1.4)),
                  ),
                ],
              ),
            ),
          for (final row in rows) ...[
            Divider(height: 1, thickness: 1, color: hairline),
            MarketWatchRowTile(row: row, offlineSince: offlineSince, onTap: row.hasPrice ? () => _open(context, row) : null),
          ],
          Divider(height: 1, thickness: 1, color: hairline),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 10, 8, 6),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (sources.isNotEmpty) Text(l10n.marketWatchSources(sources), style: TextStyle(color: muted, fontSize: 12, height: 1.45)),
                Text(watch.disclaimer, style: TextStyle(color: muted, fontSize: 12, height: 1.45)),
                if (widget.homeOnly)
                  Align(
                    alignment: Alignment.centerRight,
                    child: TextButton(
                      onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const MarketWatchScreen())),
                      style: TextButton.styleFrom(foregroundColor: onDeep, minimumSize: const Size(44, 44)),
                      child: Text(
                        l10n.marketWatchSeeAll,
                        style: const TextStyle(fontWeight: FontWeight.w600, decoration: TextDecoration.underline),
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
}

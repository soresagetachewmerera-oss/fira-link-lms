import 'package:flutter/foundation.dart';

import '../models/market_watch.dart';
import '../services/market_watch_service.dart';

/// Market watch state shared by Home (card and pull-to-refresh) and later the market screen.
/// Loads on first use; [load] returns true only when the server answered (not from the cache).
class MarketWatchProvider extends ChangeNotifier {
  MarketWatchProvider({MarketWatchService? service}) : _service = service ?? MarketWatchService();

  final MarketWatchService _service;

  MarketWatchSnapshot? _snapshot;
  bool _loading = false;
  bool _failed = false;
  bool _started = false;

  MarketWatchSnapshot? get snapshot => _snapshot;
  bool get loading => _loading;

  /// True when the last attempt failed and there is nothing (not even a cache) to show.
  bool get failed => _failed && _snapshot == null;

  /// First load (app open / first time the card is shown). Later calls do nothing.
  void ensureLoaded() {
    if (_started) return;
    _started = true;
    load();
  }

  Future<bool> load() async {
    _started = true;
    _loading = true;
    notifyListeners();
    try {
      final snapshot = await _service.fetchWatch();
      _snapshot = snapshot;
      _failed = false;
      return !snapshot.fromCache;
    } catch (_) {
      _failed = true;
      final previous = _snapshot;
      if (previous != null && !previous.fromCache) {
        // What is on screen is no longer confirmed by the server: say so, with the time it was received.
        _snapshot = MarketWatchSnapshot(watch: previous.watch, fromCache: true, receivedAt: previous.receivedAt);
      }
      return false;
    } finally {
      _loading = false;
      notifyListeners();
    }
  }
}

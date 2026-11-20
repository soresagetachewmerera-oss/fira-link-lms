import 'dart:convert';
import 'package:flutter/material.dart';
import '../models/coffee_export_models.dart';
import '../services/coffee_toolkit_service.dart';

class CoffeeToolkitProvider with ChangeNotifier {
  final CoffeeToolkitService _service = CoffeeToolkitService();

  bool _isLoading = false;
  String? _error;

  List<SavedCoffeeCalculation> _recentCalculations = [];
  List<CoffeeOffer> _recentOffers = [];
  List<CoffeeMarketBenchmarkModel> _marketBenchmarks = [];
  double? _latestAssessmentScore;
  String? _latestAssessmentTier;
  Map<String, dynamic>? _latestAssessmentData;
  Map<String, dynamic>? _datasetSnapshot;

  CoffeeMarketIntelligenceResponse? _marketResponse;
  List<CoffeePriceRecordModel> _allRecords = [];
  List<CoffeePriceRecordModel> _filteredRecords = [];
  List<String> _origins = ['All'];
  String _selectedOrigin = 'All';
  String _selectedProcess = 'All';
  String _selectedExporterType = 'All';
  String _searchQuery = '';

  CoffeeCalculationResult? _currentCalculationResult;

  bool get isLoading => _isLoading;
  String? get error => _error;
  List<SavedCoffeeCalculation> get recentCalculations => _recentCalculations;
  List<CoffeeOffer> get recentOffers => _recentOffers;
  List<CoffeeMarketBenchmarkModel> get marketBenchmarks => _marketBenchmarks;
  double? get latestAssessmentScore => _latestAssessmentScore;
  String? get latestAssessmentTier => _latestAssessmentTier;
  Map<String, dynamic>? get latestAssessmentData => _latestAssessmentData;
  Map<String, dynamic>? get datasetSnapshot => _datasetSnapshot;
  CoffeeCalculationResult? get currentCalculationResult => _currentCalculationResult;

  CoffeeMarketIntelligenceResponse? get marketResponse => _marketResponse;
  List<CoffeePriceRecordModel> get allRecords => _allRecords;
  List<CoffeePriceRecordModel> get filteredRecords => _filteredRecords;
  List<String> get origins => _origins;
  String get selectedOrigin => _selectedOrigin;
  String get selectedProcess => _selectedProcess;
  String get selectedExporterType => _selectedExporterType;
  String get searchQuery => _searchQuery;

  Future<void> loadHubSummary() async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _service.getHubSummary();
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] == true && data['data'] != null) {
          final hubData = data['data'];

          if (hubData['recent_calculations'] != null) {
            final List<dynamic> calcs = hubData['recent_calculations'];
            _recentCalculations =
                calcs.map((j) => SavedCoffeeCalculation.fromJson(j)).toList();
          }

          if (hubData['recent_offers'] != null) {
            final List<dynamic> offs = hubData['recent_offers'];
            _recentOffers = offs.map((j) => CoffeeOffer.fromJson(j)).toList();
          }

          if (hubData['market_benchmarks'] != null) {
            final List<dynamic> benchs = hubData['market_benchmarks'];
            _marketBenchmarks = benchs
                .map((j) => CoffeeMarketBenchmarkModel.fromJson(j))
                .toList();
          }

          if (hubData['latest_assessment'] != null) {
            final assess = hubData['latest_assessment'];
            _latestAssessmentScore =
                (assess['overall_score'] as num?)?.toDouble();
            _latestAssessmentTier = assess['readiness_tier'];
            _latestAssessmentData = assess;
          }

          if (hubData['dataset_snapshot'] != null &&
              hubData['dataset_snapshot'] is Map) {
            _datasetSnapshot =
                Map<String, dynamic>.from(hubData['dataset_snapshot']);
          }
        }
      } else {
        _error = 'Failed to load hub summary: ${response.statusCode}';
      }
    } catch (e) {
      _error = 'Connection error: $e';
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<CoffeeCalculationResult?> calculateProfit(
      Map<String, dynamic> params) async {
    try {
      final response = await _service.calculateProfit(params);
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] == true && data['data'] != null) {
          _currentCalculationResult =
              CoffeeCalculationResult.fromJson(data['data']);
          notifyListeners();
          return _currentCalculationResult;
        }
      }
    } catch (e) {
      _error = 'Calculation error: $e';
      notifyListeners();
    }
    return null;
  }

  Future<bool> saveCalculation(Map<String, dynamic> params) async {
    _isLoading = true;
    notifyListeners();

    try {
      final response = await _service.saveCalculation(params);
      if (response.statusCode == 201 || response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['data'] != null) {
          _recentCalculations.insert(
            0,
            SavedCoffeeCalculation.fromJson(data['data']),
          );
        }
        _isLoading = false;
        notifyListeners();
        return true;
      }
    } catch (e) {
      _error = 'Failed to save deal: $e';
    }

    _isLoading = false;
    notifyListeners();
    return false;
  }

  Future<bool> createOffer(Map<String, dynamic> params) async {
    _isLoading = true;
    notifyListeners();

    try {
      final response = await _service.createOffer(params);
      if (response.statusCode == 201 || response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['data'] != null) {
          _recentOffers.insert(0, CoffeeOffer.fromJson(data['data']));
        }
        _isLoading = false;
        notifyListeners();
        return true;
      }
    } catch (e) {
      _error = 'Failed to create offer: $e';
    }

    _isLoading = false;
    notifyListeners();
    return false;
  }

  Future<bool> submitAssessment(Map<String, dynamic> answers) async {
    _isLoading = true;
    notifyListeners();

    try {
      final response = await _service.submitAssessment(answers);
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['data'] != null) {
          final assess = data['data'];
          _latestAssessmentScore =
              (assess['overall_score'] as num?)?.toDouble();
          _latestAssessmentTier = assess['readiness_tier'];
          _latestAssessmentData = assess;
        }
        _isLoading = false;
        notifyListeners();
        return true;
      }
    } catch (e) {
      _error = 'Failed to submit assessment: $e';
    }

    _isLoading = false;
    notifyListeners();
    return false;
  }

  void setSearchQuery(String query) {
    _searchQuery = query;
    _applyFilters();
  }

  void setOriginFilter(String origin) {
    _selectedOrigin = origin;
    _applyFilters();
  }

  void setProcessFilter(String process) {
    _selectedProcess = process;
    _applyFilters();
  }

  void setExporterTypeFilter(String exporterType) {
    _selectedExporterType = exporterType;
    _applyFilters();
  }

  void resetFilters() {
    _searchQuery = '';
    _selectedOrigin = 'All';
    _selectedProcess = 'All';
    _selectedExporterType = 'All';
    _applyFilters();
  }

  void _applyFilters() {
    _filteredRecords = _allRecords.where((record) {
      if (_selectedOrigin != 'All' &&
          record.origin.toLowerCase() != _selectedOrigin.toLowerCase()) {
        return false;
      }
      if (_selectedProcess != 'All' &&
          !record.processingMethod
              .toLowerCase()
              .contains(_selectedProcess.toLowerCase())) {
        return false;
      }
      if (_selectedExporterType != 'All' &&
          !record.exporterType
              .toLowerCase()
              .contains(_selectedExporterType.toLowerCase())) {
        return false;
      }
      if (_searchQuery.trim().isNotEmpty) {
        final q = _searchQuery.trim().toLowerCase();
        final match = record.coffeeType.toLowerCase().contains(q) ||
            record.origin.toLowerCase().contains(q) ||
            record.grade.toLowerCase().contains(q) ||
            record.processingMethod.toLowerCase().contains(q);
        if (!match) return false;
      }
      return true;
    }).toList();
    notifyListeners();
  }

  Future<void> loadMarketIntelligence({bool force = false}) async {
    if (_allRecords.isEmpty) {
      _isLoading = true;
      notifyListeners();
    }

    try {
      final response = await _service.getMarketIntelligence(limit: 300);
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] == true) {
          final res = CoffeeMarketIntelligenceResponse.fromJson(data);
          _marketResponse = res;
          _marketBenchmarks = res.benchmarks;
          _allRecords = res.records;

          final Set<String> originSet = {'All'};
          for (final r in res.records) {
            if (r.origin.isNotEmpty) {
              originSet.add(r.origin);
            }
          }
          if (res.origins.isNotEmpty) {
            originSet.addAll(res.origins);
          }
          _origins = originSet.toList();

          _applyFilters();
        }
      }
    } catch (e) {
      _error = 'Failed to load market intelligence: $e';
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Session ended or a new one started (SessionWatcher): forget the previous person's data.
  void resetSession() {
    _recentCalculations = [];
    _recentOffers = [];
    _latestAssessmentScore = null;
    _latestAssessmentTier = null;
    _latestAssessmentData = null;
    _currentCalculationResult = null;
    _error = null;
    notifyListeners();
  }
}

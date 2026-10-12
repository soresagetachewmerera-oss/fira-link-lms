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

  CoffeeCalculationResult? _currentCalculationResult;

  bool get isLoading => _isLoading;
  String? get error => _error;
  List<SavedCoffeeCalculation> get recentCalculations => _recentCalculations;
  List<CoffeeOffer> get recentOffers => _recentOffers;
  List<CoffeeMarketBenchmarkModel> get marketBenchmarks => _marketBenchmarks;
  double? get latestAssessmentScore => _latestAssessmentScore;
  String? get latestAssessmentTier => _latestAssessmentTier;
  Map<String, dynamic>? get latestAssessmentData => _latestAssessmentData;
  CoffeeCalculationResult? get currentCalculationResult => _currentCalculationResult;

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

  Future<void> loadMarketIntelligence() async {
    try {
      final response = await _service.getMarketIntelligence();
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] == true && data['data'] != null) {
          final List<dynamic> list = data['data'];
          _marketBenchmarks =
              list.map((j) => CoffeeMarketBenchmarkModel.fromJson(j)).toList();
          notifyListeners();
        }
      }
    } catch (e) {
      // Graceful fallback
    }
  }
}

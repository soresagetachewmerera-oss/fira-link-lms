import 'package:flutter/foundation.dart';
import '../models/subscription_plan_model.dart';
import '../models/user_subscription_model.dart';
import '../services/subscription_service.dart';

class SubscriptionProvider extends ChangeNotifier {
  final SubscriptionService _service = SubscriptionService();

  List<SubscriptionPlanModel> _plans = [];
  UserSubscriptionModel? _activeSubscription;
  bool _isLoadingPlans = false;
  bool _isLoadingSubscription = false;
  bool _isEnrolling = false;

  List<SubscriptionPlanModel> get plans => _plans;
  UserSubscriptionModel? get activeSubscription => _activeSubscription;
  bool get hasActiveSubscription => _activeSubscription != null;
  bool get isLoadingPlans => _isLoadingPlans;
  bool get isLoadingSubscription => _isLoadingSubscription;
  bool get isEnrolling => _isEnrolling;

  Future<void> fetchPlans() async {
    _isLoadingPlans = true;
    notifyListeners();

    try {
      _plans = await _service.getPlans();
    } catch (e) {
      if (kDebugMode) print('Fetch subscription plans error: $e');
    } finally {
      _isLoadingPlans = false;
      notifyListeners();
    }
  }

  Future<void> fetchUserSubscription() async {
    _isLoadingSubscription = true;
    notifyListeners();

    try {
      _activeSubscription = await _service.getUserSubscription();
    } catch (e) {
      if (kDebugMode) print('Fetch user subscription error: $e');
    } finally {
      _isLoadingSubscription = false;
      notifyListeners();
    }
  }

  Future<Map<String, dynamic>> checkout({
    required int planId,
    required String paymentMethod,
    String? transactionId,
    String? notes,
    String? receiptPath,
  }) async {
    final result = await _service.checkoutSubscription(
      planId: planId,
      paymentMethod: paymentMethod,
      transactionId: transactionId,
      notes: notes,
      receiptPath: receiptPath,
    );
    await fetchUserSubscription();
    return result;
  }

  Future<bool> verifyPayment(int paymentId, {String? paymentIntentId, String? razorpayPaymentId}) async {
    final success = await _service.verifySubscriptionPayment(
      paymentId: paymentId,
      paymentIntentId: paymentIntentId,
      razorpayPaymentId: razorpayPaymentId,
    );
    if (success) {
      await fetchUserSubscription();
    }
    return success;
  }

  Future<bool> enrollCourse(int courseId) async {
    _isEnrolling = true;
    notifyListeners();

    try {
      final success = await _service.enrollCourseViaSubscription(courseId);
      if (success) {
        await fetchUserSubscription();
      }
      return success;
    } finally {
      _isEnrolling = false;
      notifyListeners();
    }
  }
}

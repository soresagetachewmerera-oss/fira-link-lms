import 'dart:async';
import 'dart:convert';

import 'package:http/http.dart' as http;

import '../models/deal_room_model.dart';
import 'api_exception.dart';
import 'base_service.dart';

class PayoutAccountState {
  const PayoutAccountState({this.account, this.canChange = false, this.pauseHours = 48});

  final PayoutAccountView? account;
  final bool canChange;
  final int pauseHours;
}

/// The seller's payout bank account (slice 7). Only the owner changes it, after a fresh sign-in.
/// Throws [ApiException] on failure.
class PayoutAccountService extends BaseService {
  static const Duration _timeout = Duration(milliseconds: 15000);

  Uri get _url => Uri.parse('${BaseService.baseUrl}/payout-account');

  Future<PayoutAccountState> get() async {
    final res = requireSuccess(await guardedRequest(() async => http.get(_url, headers: await getHeaders()).timeout(_timeout)));
    final d = (jsonDecode(res.body) as Map)['data'] as Map? ?? {};
    return PayoutAccountState(
      account: PayoutAccountView.fromJson(d['account']),
      canChange: d['can_change'] == true,
      pauseHours: int.tryParse('${d['pause_hours'] ?? 48}') ?? 48,
    );
  }

  Future<PayoutAccountView?> save({required String bankName, required String accountName, required String accountNumber}) async {
    final res = requireSuccess(await guardedRequest(() async => http
        .post(_url, headers: await getHeaders(), body: jsonEncode({'bank_name': bankName, 'account_name': accountName, 'account_number': accountNumber}))
        .timeout(_timeout)));
    final d = (jsonDecode(res.body) as Map)['data'] as Map? ?? {};
    return PayoutAccountView.fromJson(d['account']);
  }
}

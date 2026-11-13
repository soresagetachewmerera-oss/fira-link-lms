import 'dart:convert';

import 'package:http/http.dart' as http;

/// A request that did not succeed: either it never reached the server
/// ([isNetwork]) or the server answered with an error status.
///
/// Services throw this instead of inventing a response. Screens catch it and
/// show [FiraErrorState] (loads) or a localized message (actions).
class ApiException implements Exception {
  const ApiException({
    this.statusCode,
    this.code,
    this.reason,
    this.serverMessage,
    this.isNetwork = false,
  });

  /// The server could not be reached (offline, DNS, timeout, TLS).
  const ApiException.network() : this(isNetwork: true);

  factory ApiException.fromResponse(http.Response res) {
    String? code;
    String? reason;
    String? message;
    try {
      final decoded = jsonDecode(res.body);
      if (decoded is Map) {
        code = decoded['code']?.toString();
        reason = decoded['reason']?.toString();
        message = decoded['message']?.toString();
      }
    } catch (_) {
      // Non-JSON error body (proxy error page, etc.): status code is enough.
    }
    return ApiException(
      statusCode: res.statusCode,
      code: code,
      reason: reason,
      serverMessage: message,
    );
  }

  final int? statusCode;

  /// Machine-readable error code from the API, e.g. FILE_REJECTED, CONFIRMATION_REQUIRED.
  final String? code;

  /// Detail for FILE_REJECTED: empty, too_large, too_small, bad_type, placeholder, unreadable.
  final String? reason;

  /// English message from the server. Shown only when no localized text fits.
  final String? serverMessage;

  final bool isNetwork;

  bool get isUnauthenticated => statusCode == 401;
  bool get isForbidden => statusCode == 403;
  bool get isFileRejected => code == 'FILE_REJECTED';

  @override
  String toString() =>
      'ApiException(status: $statusCode, code: $code, reason: $reason, network: $isNetwork)';
}

/// Runs a request and converts transport failures into [ApiException.network].
/// HTTP error statuses are returned as-is so callers can read the real response.
Future<http.Response> guardedRequest(Future<http.Response> Function() send) async {
  try {
    return await send();
  } catch (_) {
    throw const ApiException.network();
  }
}

/// Returns [res] when it is a 2xx response, otherwise throws [ApiException].
http.Response requireSuccess(http.Response res) {
  if (res.statusCode >= 200 && res.statusCode < 300) {
    return res;
  }
  throw ApiException.fromResponse(res);
}

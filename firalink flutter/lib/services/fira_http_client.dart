import 'package:http/http.dart' as http;

import '../config/config.dart';
import 'auth_session.dart';

/// Every HTTP request of the app goes through this client: `main.dart` installs it with
/// `http.runWithClient`, so the top-level `http.get/post` calls and `MultipartRequest.send()` of all
/// services use it without changes.
///
/// For requests to the Fira API it
/// - removes the bearer token unless [AuthSession.mayUseToken] allows it (a guest never sends a
///   leftover token, so nothing is changed on the server for someone the app shows as a guest);
/// - reports a 401 to a request that carried the token ([AuthSession.onTokenRejected]): the app
///   then wipes the session and returns to guest mode with a message.
class FiraHttpClient extends http.BaseClient {
  FiraHttpClient(this._inner, {String? apiBaseUrl}) : _apiBase = apiBaseUrl;

  final http.Client _inner;
  final String? _apiBase;

  bool _isFiraApi(Uri url) {
    final base = Uri.tryParse(_apiBase ?? AppConfig.apiBaseUrl);
    if (base == null) return false;
    return url.host == base.host && url.port == base.port && url.path.startsWith(base.path);
  }

  @override
  Future<http.StreamedResponse> send(http.BaseRequest request) async {
    final ours = _isFiraApi(request.url);
    final authKey = request.headers.keys.firstWhere((k) => k.toLowerCase() == 'authorization', orElse: () => '');
    var sentToken = authKey.isNotEmpty;

    if (ours && sentToken && !AuthSession.mayUseToken(request.url)) {
      request.headers.remove(authKey);
      sentToken = false;
    }

    final response = await _inner.send(request);

    if (ours && sentToken && response.statusCode == 401) {
      AuthSession.onTokenRejected?.call();
    }

    return response;
  }

  @override
  void close() => _inner.close();
}

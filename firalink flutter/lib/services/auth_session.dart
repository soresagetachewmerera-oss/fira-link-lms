/// The one place the app decides whether someone is signed in (auth gating 2026-11-20).
///
/// - [unknown]  app start: a token may be stored, not checked yet. Protected screens wait.
/// - [guest]    no token stored. Only public (class A) screens and calls.
/// - [signedIn] the server accepted the stored token (`GET /api/user` answered 200).
/// - [expired]  the server rejected the token (401). Everything was wiped; acts like [guest].
/// - [offline]  a token is stored but the server could not be reached or failed (5xx, 429,
///              timeout). The session is kept; protected screens show "Can't reach Fira" + Retry.
///
/// Screens never decide this themselves: they read [AuthProvider.status] (or this mirror from
/// code without a BuildContext, such as the HTTP client).
enum AuthStatus { unknown, guest, signedIn, expired, offline }

class AuthSession {
  AuthSession._();

  static AuthStatus _status = AuthStatus.unknown;

  static AuthStatus get status => _status;

  static bool get isSignedIn => _status == AuthStatus.signedIn;

  /// Guest or expired: no session at all.
  static bool get isGuest => _status == AuthStatus.guest || _status == AuthStatus.expired;

  /// Called by the HTTP client when the API answers 401 to a request that carried our token.
  static void Function()? onTokenRejected;

  static void setStatus(AuthStatus status) => _status = status;

  /// A bearer token may go out only for a signed-in session. Before the session is confirmed
  /// (unknown, offline) only the session check itself and sign-out may carry it.
  static bool mayUseToken(Uri url) {
    if (_status == AuthStatus.signedIn) return true;
    if (_status == AuthStatus.unknown || _status == AuthStatus.offline) {
      final path = url.path;
      return path.endsWith('/api/user') || path.endsWith('/api/logout');
    }
    return false;
  }
}

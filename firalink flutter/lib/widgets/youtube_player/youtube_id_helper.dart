class YoutubeIdHelper {
  /// Extracts the 11-character YouTube video ID from any format:
  /// - https://www.youtube.com/watch?v=kYJ5q-EwPno
  /// - https://youtu.be/kYJ5q-EwPno
  /// - https://www.youtube.com/embed/kYJ5q-EwPno
  /// - https://www.youtube.com/shorts/kYJ5q-EwPno
  /// - https://www.youtube-nocookie.com/embed/kYJ5q-EwPno
  /// - kYJ5q-EwPno (raw 11-char ID)
  static String? extractId(String? urlOrId) {
    if (urlOrId == null || urlOrId.trim().isEmpty) return null;
    final trimmed = urlOrId.trim();

    // Already an 11-char ID
    if (RegExp(r'^[a-zA-Z0-9_-]{11}$').hasMatch(trimmed)) {
      return trimmed;
    }

    // Comprehensive regex for all YouTube URL variants
    final regExp = RegExp(
      r'(?:v=|\/embed\/|\/shorts\/|\/live\/|\/v\/|youtu\.be\/|\/watch\?.*v=)([a-zA-Z0-9_-]{11})',
      caseSensitive: false,
    );
    final match = regExp.firstMatch(trimmed);
    if (match != null && match.groupCount >= 1) {
      return match.group(1);
    }

    // Fallback: Uri query parameter 'v'
    try {
      final uri = Uri.parse(trimmed);
      if (uri.queryParameters.containsKey('v')) {
        final v = uri.queryParameters['v'];
        if (v != null && RegExp(r'^[a-zA-Z0-9_-]{11}$').hasMatch(v)) {
          return v;
        }
      }
    } catch (_) {}

    return null;
  }

  static bool isYoutube(String? url, [String? type]) {
    if (type != null && type.trim().toLowerCase() == 'youtube') return true;
    if (url == null || url.trim().isEmpty) return false;
    final lower = url.toLowerCase();
    return lower.contains('youtube.com') ||
        lower.contains('youtu.be') ||
        lower.contains('youtube-nocookie.com') ||
        extractId(url) != null;
  }
}

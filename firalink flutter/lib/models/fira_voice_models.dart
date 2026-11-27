enum VoiceSessionState {
  idle,
  initializing,
  connecting,
  listening,
  thinking,
  speaking,
  interrupted,
  ended,
  error,
  unauthenticated,
  afanOromoFallback,
}

class VoiceDisplayCard {
  final String type;
  final String title;
  final Map<String, dynamic> data;

  const VoiceDisplayCard({
    required this.type,
    required this.title,
    required this.data,
  });

  factory VoiceDisplayCard.fromJson(Map<String, dynamic> json) {
    return VoiceDisplayCard(
      type: json['type'] as String? ?? 'info',
      title: json['title'] as String? ?? '',
      data: (json['data'] as Map<String, dynamic>?) ?? {},
    );
  }
}

class VoiceSessionCredentials {
  final String sessionId;
  final String token;
  final String model;
  final String language;
  final int maxSessionSeconds;
  final int remainingTodaySeconds;

  const VoiceSessionCredentials({
    required this.sessionId,
    required this.token,
    required this.model,
    required this.language,
    required this.maxSessionSeconds,
    required this.remainingTodaySeconds,
  });

  factory VoiceSessionCredentials.fromJson(Map<String, dynamic> json) {
    final limits = (json['limits'] as Map<String, dynamic>?) ?? {};
    return VoiceSessionCredentials(
      sessionId: json['session_id'] as String? ?? '',
      token: json['token'] as String? ?? '',
      model: json['model'] as String? ?? 'models/gemini-2.0-flash',
      language: json['language'] as String? ?? 'en',
      maxSessionSeconds: (limits['max_session_seconds'] as num?)?.toInt() ?? 120,
      remainingTodaySeconds: (limits['remaining_today_seconds'] as num?)?.toInt() ?? 300,
    );
  }
}

class FiraVoiceQuota {
  final int secondsUsed;
  final int secondsLimit;
  final int remainingTodaySeconds;
  final int sessionsCount;
  final List<String> allowedLanguages;

  const FiraVoiceQuota({
    required this.secondsUsed,
    required this.secondsLimit,
    required this.remainingTodaySeconds,
    required this.sessionsCount,
    required this.allowedLanguages,
  });

  factory FiraVoiceQuota.fromJson(Map<String, dynamic> json) {
    return FiraVoiceQuota(
      secondsUsed: (json['seconds_used'] as num?)?.toInt() ?? 0,
      secondsLimit: (json['seconds_limit'] as num?)?.toInt() ?? 300,
      remainingTodaySeconds: (json['remaining_today_seconds'] as num?)?.toInt() ?? 300,
      sessionsCount: (json['sessions_count'] as num?)?.toInt() ?? 0,
      allowedLanguages: (json['allowed_languages'] as List<dynamic>?)
              ?.map((e) => e.toString())
              .toList() ??
          ['en', 'am'],
    );
  }
}

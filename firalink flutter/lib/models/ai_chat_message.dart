class AiChatMessage {
  final int id;
  final int userId;
  final String message;
  final bool isAi;
  final DateTime createdAt;

  AiChatMessage({
    required this.id,
    required this.userId,
    required this.message,
    required this.isAi,
    required this.createdAt,
  });

  factory AiChatMessage.fromJson(Map<String, dynamic> json) {
    return AiChatMessage(
      id: (json['id'] as num?)?.toInt() ?? int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      userId: (json['user_id'] as num?)?.toInt() ?? int.tryParse(json['user_id']?.toString() ?? '0') ?? 0,
      message: json['message']?.toString() ?? '',
      isAi: json['is_ai'] == 1 || json['is_ai'] == true,
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'].toString()) ?? DateTime.now()
          : DateTime.now(),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'user_id': userId,
      'message': message,
      'is_ai': isAi ? 1 : 0,
      'created_at': createdAt.toIso8601String(),
    };
  }
}

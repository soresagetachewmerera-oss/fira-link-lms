/// One row of the in-app inbox (messaging slice 4).
///
/// Catalogue rows carry a key (`type`, e.g. `sample.shipped`), safe parameters, a route and an
/// id, and the text in English and Amharic. The text is chosen when the row is shown, in the
/// app's language, so switching language also changes old rows. Older rows (admin broadcasts,
/// earlier notification types) keep working: they have `title`/`message` and maybe an image or URL.
class NotificationModel {
  final String id;
  final String title;
  final String message;
  final String? titleAm;
  final String? messageAm;
  final String type;
  final String? image;
  final String? url;
  final String? route;
  final String? routeId;
  final DateTime? readAt;
  final DateTime createdAt;

  NotificationModel({
    required this.id,
    required this.title,
    required this.message,
    this.titleAm,
    this.messageAm,
    required this.type,
    this.image,
    this.url,
    this.route,
    this.routeId,
    this.readAt,
    required this.createdAt,
  });

  factory NotificationModel.fromJson(Map<String, dynamic> json) {
    final data = (json['data'] as Map?)?.cast<String, dynamic>() ?? const <String, dynamic>{};
    String? str(Object? v) => (v == null || '$v'.isEmpty) ? null : '$v';

    return NotificationModel(
      id: '${json['id']}',
      title: str(data['title']) ?? '',
      message: str(data['message']) ?? '',
      titleAm: str(data['title_am']),
      messageAm: str(data['message_am']),
      type: str(data['type']) ?? 'text',
      image: str(data['image']),
      url: str(data['url']),
      route: str(data['route']),
      routeId: str(data['route_id']),
      readAt: json['read_at'] != null ? DateTime.tryParse('${json['read_at']}') : null,
      createdAt: DateTime.tryParse('${json['created_at']}') ?? DateTime.now(),
    );
  }

  bool get isRead => readAt != null;

  /// A message from the catalogue (`group.event` key) that opens a screen of the app.
  bool get opensScreen => route != null && route!.startsWith('/');

  String titleFor(String languageCode) => languageCode == 'am' && titleAm != null ? titleAm! : title;

  String messageFor(String languageCode) => languageCode == 'am' && messageAm != null ? messageAm! : message;

  NotificationModel copyWith({DateTime? readAt}) => NotificationModel(
        id: id,
        title: title,
        message: message,
        titleAm: titleAm,
        messageAm: messageAm,
        type: type,
        image: image,
        url: url,
        route: route,
        routeId: routeId,
        readAt: readAt ?? this.readAt,
        createdAt: createdAt,
      );

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'data': {
        'title': title,
        'message': message,
        'title_am': titleAm,
        'message_am': messageAm,
        'type': type,
        'image': image,
        'url': url,
        'route': route,
        'route_id': routeId,
      },
      'read_at': readAt?.toIso8601String(),
      'created_at': createdAt.toIso8601String(),
    };
  }
}

class NotificationResponse {
  final List<NotificationModel> notifications;
  final int currentPage;
  final int lastPage;
  final int total;

  NotificationResponse({
    required this.notifications,
    required this.currentPage,
    required this.lastPage,
    required this.total,
  });

  factory NotificationResponse.fromJson(Map<String, dynamic> json) {
    final data = json['data'];
    final list = data['data'] as List<dynamic>;
    return NotificationResponse(
      notifications: list.map((n) => NotificationModel.fromJson(n as Map<String, dynamic>)).toList(),
      currentPage: data['current_page'] ?? 1,
      lastPage: data['last_page'] ?? 1,
      total: data['total'] ?? 0,
    );
  }
}

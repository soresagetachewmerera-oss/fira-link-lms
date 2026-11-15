import 'trade_model.dart';

int? _i(dynamic v) => v == null ? null : int.tryParse(v.toString().split('.').first);
DateTime? _dt(dynamic v) => v == null ? null : DateTime.tryParse(v.toString())?.toLocal();

/// A business chat thread (slice 7): a deal room thread or a "Message seller" thread.
class TradeConversation {
  const TradeConversation({
    required this.id,
    required this.mySide,
    this.other,
    this.dealRoomId,
    this.dealReference,
    this.lotTitle,
    this.lotCode,
    this.blocked = false,
    this.blockedByMe = false,
    this.lastMessage,
    this.lastMessageAt,
    this.unread = 0,
  });

  final int id;
  final String mySide;
  final BusinessSummary? other;
  final int? dealRoomId;
  final String? dealReference;
  final String? lotTitle;
  final String? lotCode;
  final bool blocked;
  final bool blockedByMe;
  final String? lastMessage;
  final DateTime? lastMessageAt;
  final int unread;

  factory TradeConversation.fromJson(Map<String, dynamic> j) {
    final deal = j['deal_room'] is Map ? j['deal_room'] as Map : null;
    final lot = j['lot'] is Map ? j['lot'] as Map : null;
    return TradeConversation(
      id: _i(j['id']) ?? 0,
      mySide: j['my_side']?.toString() ?? 'buyer',
      other: BusinessSummary.fromJson(j['other']),
      dealRoomId: _i(deal?['id']),
      dealReference: deal?['deal_reference']?.toString(),
      lotTitle: lot?['title']?.toString(),
      lotCode: lot?['lot_code']?.toString(),
      blocked: j['blocked'] == true,
      blockedByMe: j['blocked_by_me'] == true,
      lastMessage: j['last_message']?.toString(),
      lastMessageAt: _dt(j['last_message_at']),
      unread: _i(j['unread']) ?? 0,
    );
  }
}

/// One message. [showWarning] is set by the server for the recipient of a flagged message only.
class TradeMessage {
  const TradeMessage({
    required this.id,
    required this.mine,
    required this.body,
    this.flagged = false,
    this.flagKinds = const [],
    this.showWarning = false,
    this.read = false,
    this.createdAt,
  });

  final int id;
  final bool mine;
  final String body;
  final bool flagged;
  final List<String> flagKinds;
  final bool showWarning;
  final bool read;
  final DateTime? createdAt;

  factory TradeMessage.fromJson(Map<String, dynamic> j) => TradeMessage(
        id: _i(j['id']) ?? 0,
        mine: j['mine'] == true,
        body: j['body']?.toString() ?? '',
        flagged: j['flagged'] == true,
        flagKinds: (j['flag_kinds'] as List? ?? []).map((e) => e.toString()).toList(),
        showWarning: j['show_warning'] == true,
        read: j['read'] == true,
        createdAt: _dt(j['created_at']),
      );
}

class TradeThread {
  const TradeThread({required this.conversation, required this.messages});

  final TradeConversation conversation;
  final List<TradeMessage> messages;
}

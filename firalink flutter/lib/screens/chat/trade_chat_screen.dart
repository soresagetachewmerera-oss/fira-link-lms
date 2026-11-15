import 'package:flutter/material.dart';

import '../../l10n/app_localizations.dart';
import '../../models/trade_chat_model.dart';
import '../../services/trade_chat_service.dart';
import '../../services/trust_safety_service.dart';
import '../../theme/fira_colors.dart';
import '../../widgets/fira_error_state.dart';
import '../../widgets/trade_labels.dart';
import '../../widgets/verification_labels.dart';
import '../trust_safety/report_modal.dart';

/// One business chat thread (slice 7). Both sides are businesses. A message with contact details
/// or an off-platform invitation arrives with a warning box for the recipient.
class TradeChatScreen extends StatefulWidget {
  const TradeChatScreen({super.key, required this.conversationId, this.service, this.trust});

  final int conversationId;
  final TradeChatService? service;
  final TrustSafetyService? trust;

  @override
  State<TradeChatScreen> createState() => _TradeChatScreenState();
}

class _TradeChatScreenState extends State<TradeChatScreen> {
  late final TradeChatService _service = widget.service ?? TradeChatService();
  late final TrustSafetyService _trust = widget.trust ?? TrustSafetyService();
  final _input = TextEditingController();
  final Set<int> _dismissed = {};
  TradeThread? _thread;
  Object? _error;
  bool _sending = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _input.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() => _error = null);
    try {
      final t = await _service.thread(widget.conversationId);
      if (mounted) setState(() => _thread = t);
    } catch (e) {
      if (mounted) setState(() => _error = e);
    }
  }

  Future<void> _send() async {
    final text = _input.text.trim();
    if (text.isEmpty) return;
    final l10n = context.l10n;
    setState(() => _sending = true);
    try {
      final m = await _service.send(widget.conversationId, text);
      _input.clear();
      if (!mounted) return;
      if (m.flagged) {
        ScaffoldMessenger.of(context)
          ..hideCurrentSnackBar()
          ..showSnackBar(SnackBar(content: Text(l10n.chatFlaggedSent)));
      }
      await _load();
    } catch (e) {
      if (mounted) showFiraActionError(context, e);
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  Future<void> _toggleBlock(TradeConversation c) async {
    final other = c.other;
    if (other == null) return;
    final l10n = context.l10n;
    if (!c.blockedByMe) {
      final ok = await showDialog<bool>(
        context: context,
        builder: (ctx) => AlertDialog(
          content: Text(l10n.translate('chatBlockConfirm', {'name': other.name})),
          actions: [
            TextButton(onPressed: () => Navigator.pop(ctx, false), child: Text(l10n.cancel)),
            TextButton(key: const Key('confirm-block'), onPressed: () => Navigator.pop(ctx, true), child: Text(l10n.chatBlock)),
          ],
        ),
      );
      if (ok != true) return;
    }
    try {
      c.blockedByMe ? await _trust.unblock(other.id) : await _trust.block(other.id);
      await _load();
    } catch (e) {
      if (mounted) showFiraActionError(context, e);
    }
  }

  void _report({TradeMessage? message}) {
    final c = _thread?.conversation;
    ReportModal.show(
      context,
      reportedMessageId: message?.id,
      reportedDealRoomId: message == null ? c?.dealRoomId : null,
      reportedOrganizationId: message == null && c?.dealRoomId == null ? c?.other?.id : null,
      initialReason: message != null ? 'pay_outside' : null,
      targetTitle: c?.other?.name ?? '',
      service: _trust,
    );
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final c = _thread?.conversation;

    return Scaffold(
      appBar: AppBar(
        title: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text(c?.other?.name ?? l10n.chatTitle, overflow: TextOverflow.ellipsis),
          if (c?.other != null) BusinessTierLabel(business: c!.other),
        ]),
        actions: [
          if (c != null)
            PopupMenuButton<String>(
              key: const Key('chat-menu'),
              onSelected: (v) => v == 'block' ? _toggleBlock(c) : _report(),
              itemBuilder: (_) => [
                PopupMenuItem(value: 'report', child: Text(l10n.chatReport)),
                PopupMenuItem(value: 'block', child: Text(c.blockedByMe ? l10n.chatUnblock : l10n.chatBlock)),
              ],
            ),
        ],
      ),
      body: _error != null
          ? FiraErrorState(error: _error, onRetry: _load)
          : _thread == null
              ? const Center(child: CircularProgressIndicator())
              : _body(context, _thread!),
    );
  }

  Widget _body(BuildContext context, TradeThread t) {
    final l10n = context.l10n;
    final fira = context.fira;
    final c = t.conversation;
    final context0 = [
      if (c.dealReference != null) l10n.translate('chatDealLine', {'ref': c.dealReference}),
      if (c.lotCode != null) l10n.translate('chatLotLine', {'code': c.lotCode}),
    ].join(' · ');

    return SafeArea(
      child: Column(children: [
        if (context0.isNotEmpty)
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 8, 20, 0),
            child: Text(context0, style: TextStyle(color: fira.mutedText, fontSize: 12)),
          ),
        Expanded(
          child: RefreshIndicator(
            onRefresh: _load,
            child: t.messages.isEmpty
                ? ListView(children: [
                    Padding(
                      padding: const EdgeInsets.all(32),
                      child: Text(l10n.chatEmpty, textAlign: TextAlign.center, style: TextStyle(color: fira.mutedText)),
                    ),
                  ])
                : ListView.builder(
                    padding: const EdgeInsets.fromLTRB(20, 12, 20, 12),
                    itemCount: t.messages.length,
                    itemBuilder: (_, i) => _message(context, t.messages[i]),
                  ),
          ),
        ),
        if (c.blocked)
          Container(
            key: const Key('chat-blocked'),
            width: double.infinity,
            color: fira.brandTint,
            padding: const EdgeInsets.all(16),
            child: Row(children: [
              Expanded(child: Text(c.blockedByMe ? l10n.chatBlocked : l10n.chatBlockedByThem)),
              if (c.blockedByMe) TextButton(onPressed: () => _toggleBlock(c), child: Text(l10n.chatUnblock)),
            ]),
          )
        else
          Padding(
            padding: const EdgeInsets.fromLTRB(12, 4, 12, 8),
            child: Row(children: [
              Expanded(
                child: TextField(
                  key: const Key('chat-input'),
                  controller: _input,
                  minLines: 1,
                  maxLines: 4,
                  maxLength: 2000,
                  decoration: InputDecoration(hintText: l10n.chatHint, counterText: ''),
                ),
              ),
              const SizedBox(width: 8),
              IconButton.filled(
                key: const Key('chat-send'),
                tooltip: l10n.chatSend,
                onPressed: _sending ? null : _send,
                icon: _sending ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2)) : const Icon(Icons.send),
              ),
            ]),
          ),
      ]),
    );
  }

  Widget _message(BuildContext context, TradeMessage m) {
    final fira = context.fira;
    final bubble = Align(
      alignment: m.mine ? Alignment.centerRight : Alignment.centerLeft,
      child: ConstrainedBox(
        constraints: BoxConstraints(maxWidth: MediaQuery.of(context).size.width * 0.78),
        child: Card(
          color: m.mine ? fira.brandTint : fira.card,
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              SelectableText(m.body),
              const SizedBox(height: 4),
              Text(VerificationLabels.date(context, m.createdAt), style: TextStyle(fontSize: 11, color: fira.mutedText)),
            ]),
          ),
        ),
      ),
    );
    if (!m.showWarning || _dismissed.contains(m.id)) return bubble;
    return Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [bubble, _warning(context, m)]);
  }

  Widget _warning(BuildContext context, TradeMessage m) {
    final l10n = context.l10n;
    final fira = context.fira;
    return Card(
      key: Key('chat-warning-${m.id}'),
      color: fira.brandTint,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Row(children: [
            Icon(Icons.shield_outlined, color: fira.danger),
            const SizedBox(width: 8),
            Expanded(child: Text(l10n.chatWarnTitle, style: TextStyle(fontWeight: FontWeight.w700, color: fira.onSurface))),
          ]),
          const SizedBox(height: 8),
          for (final line in [l10n.chatWarnPay, l10n.chatWarnMove, l10n.chatWarnCheck])
            Padding(
              padding: const EdgeInsets.only(bottom: 4),
              child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
                const Text('•  '),
                Expanded(child: Text(line, style: TextStyle(color: fira.onSurface))),
              ]),
            ),
          const SizedBox(height: 8),
          Wrap(spacing: 8, runSpacing: 8, children: [
            FilledButton(
              key: Key('keep-chatting-${m.id}'),
              onPressed: () => setState(() => _dismissed.add(m.id)),
              child: Text(l10n.chatKeepHere),
            ),
            OutlinedButton(
              key: Key('report-message-${m.id}'),
              onPressed: () => _report(message: m),
              child: Text(l10n.chatReportMessage),
            ),
          ]),
        ]),
      ),
    );
  }
}

/// Business messages inbox: the active business's threads.
class TradeChatsScreen extends StatefulWidget {
  const TradeChatsScreen({super.key, this.service});

  final TradeChatService? service;

  @override
  State<TradeChatsScreen> createState() => _TradeChatsScreenState();
}

class _TradeChatsScreenState extends State<TradeChatsScreen> {
  late final TradeChatService _service = widget.service ?? TradeChatService();
  List<TradeConversation>? _items;
  Object? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _error = null);
    try {
      final items = await _service.inbox();
      if (mounted) setState(() => _items = items);
    } catch (e) {
      if (mounted) setState(() => _error = e);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final fira = context.fira;
    return Scaffold(
      appBar: AppBar(title: Text(l10n.chatInbox)),
      body: _error != null
          ? FiraErrorState(error: _error, onRetry: _load)
          : _items == null
              ? const Center(child: CircularProgressIndicator())
              : RefreshIndicator(
                  onRefresh: _load,
                  child: ListView(
                    padding: const EdgeInsets.fromLTRB(20, 12, 20, 32),
                    children: [
                      if (_items!.isEmpty)
                        Padding(
                          padding: const EdgeInsets.symmetric(vertical: 32),
                          child: Text(l10n.chatInboxEmpty, textAlign: TextAlign.center, style: TextStyle(color: fira.mutedText)),
                        ),
                      for (final c in _items!)
                        Card(
                          key: Key('thread-${c.id}'),
                          child: ListTile(
                            title: Text(c.other?.name ?? '', style: const TextStyle(fontWeight: FontWeight.w700)),
                            subtitle: Text(
                              [
                                if (c.dealReference != null) l10n.translate('chatDealLine', {'ref': c.dealReference}),
                                if (c.lastMessage != null) c.lastMessage!,
                              ].join('\n'),
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                            ),
                            trailing: c.unread > 0
                                ? CircleAvatar(radius: 12, backgroundColor: fira.brandPrimary, child: Text('${c.unread}', style: TextStyle(color: fira.card, fontSize: 12)))
                                : null,
                            onTap: () async {
                              await Navigator.of(context).push(MaterialPageRoute(builder: (_) => TradeChatScreen(conversationId: c.id, service: _service)));
                              _load();
                            },
                          ),
                        ),
                    ],
                  ),
                ),
    );
  }
}

/// Opens (or creates) the "Message seller" thread and shows it.
Future<void> openSellerChat(BuildContext context, {required int sellerOrganizationId, int? listingId, TradeChatService? service}) async {
  final s = service ?? TradeChatService();
  try {
    final c = await s.withSeller(sellerOrganizationId, listingId: listingId);
    if (!context.mounted) return;
    await Navigator.of(context).push(MaterialPageRoute(builder: (_) => TradeChatScreen(conversationId: c.id, service: s)));
  } catch (e) {
    if (context.mounted) showFiraActionError(context, e);
  }
}

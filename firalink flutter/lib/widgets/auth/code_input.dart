import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../theme/fira_colors.dart';

/// Six digit boxes backed by one text field, so paste, SMS autofill (oneTimeCode) and
/// the system keyboard all work. Built from theme tokens only (input radius 16).
class CodeInput extends StatefulWidget {
  const CodeInput({
    super.key,
    required this.onChanged,
    this.onCompleted,
    this.length = 6,
    this.hasError = false,
    this.enabled = true,
    this.controller,
  });

  final ValueChanged<String> onChanged;
  final ValueChanged<String>? onCompleted;
  final int length;
  final bool hasError;
  final bool enabled;
  final TextEditingController? controller;

  @override
  State<CodeInput> createState() => _CodeInputState();
}

class _CodeInputState extends State<CodeInput> {
  late final TextEditingController _controller = widget.controller ?? TextEditingController();
  final FocusNode _focus = FocusNode();

  @override
  void initState() {
    super.initState();
    _controller.addListener(_onText);
    _focus.addListener(() => setState(() {}));
  }

  @override
  void dispose() {
    _controller.removeListener(_onText);
    if (widget.controller == null) _controller.dispose();
    _focus.dispose();
    super.dispose();
  }

  void _onText() {
    final value = _controller.text;
    widget.onChanged(value);
    if (value.length == widget.length) widget.onCompleted?.call(value);
    setState(() {});
  }

  @override
  Widget build(BuildContext context) {
    final fira = context.fira;
    final theme = Theme.of(context);
    final text = _controller.text;

    return GestureDetector(
      onTap: widget.enabled ? () => _focus.requestFocus() : null,
      child: Stack(
        children: [
          // The real input: invisible, but focusable and autofill-capable.
          Opacity(
            opacity: 0,
            child: SizedBox(
              height: 56,
              child: TextField(
                controller: _controller,
                focusNode: _focus,
                enabled: widget.enabled,
                autofocus: true,
                keyboardType: TextInputType.number,
                autofillHints: const [AutofillHints.oneTimeCode],
                maxLength: widget.length,
                inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                decoration: const InputDecoration(counterText: ''),
              ),
            ),
          ),
          Semantics(
            label: 'Code, ${widget.length} digits',
            value: text,
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: List.generate(widget.length, (i) {
                final filled = i < text.length;
                final active = _focus.hasFocus && (i == text.length || (i == widget.length - 1 && text.length == widget.length));
                final borderColor = widget.hasError
                    ? fira.danger
                    : active
                        ? fira.brandPrimary
                        : theme.colorScheme.outlineVariant;
                return Container(
                  width: 46,
                  height: 56,
                  alignment: Alignment.center,
                  decoration: BoxDecoration(
                    color: fira.card,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: borderColor, width: active || widget.hasError ? 1.5 : 1),
                  ),
                  child: Text(
                    filled ? text[i] : '',
                    style: theme.textTheme.titleLarge?.copyWith(color: fira.onSurface),
                  ),
                );
              }),
            ),
          ),
        ],
      ),
    );
  }
}

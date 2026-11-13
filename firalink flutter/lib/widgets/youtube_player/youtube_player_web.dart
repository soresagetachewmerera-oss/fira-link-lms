import 'dart:ui_web' as ui_web;
import 'package:flutter/material.dart';
import 'package:web/web.dart' as web;

class PlatformYoutubePlayer extends StatefulWidget {
  final String videoId;
  final bool autoPlay;

  const PlatformYoutubePlayer({
    super.key,
    required this.videoId,
    this.autoPlay = true,
  });

  @override
  State<PlatformYoutubePlayer> createState() => _PlatformYoutubePlayerState();
}

class _PlatformYoutubePlayerState extends State<PlatformYoutubePlayer> {
  late final String _viewType;

  @override
  void initState() {
    super.initState();
    _viewType =
        'youtube-iframe-${widget.videoId}-${DateTime.now().microsecondsSinceEpoch}';
    _registerView();
  }

  void _registerView() {
    final videoId = widget.videoId;
    final autoPlayParam = widget.autoPlay ? '1' : '0';
    final embedUrl =
        'https://www.youtube-nocookie.com/embed/$videoId?autoplay=$autoPlayParam&rel=0&playsinline=1&modestbranding=1&enablejsapi=1';

    ui_web.platformViewRegistry.registerViewFactory(_viewType, (int viewId) {
      final iframe = web.HTMLIFrameElement();
      iframe.src = embedUrl;
      iframe.style.border = 'none';
      iframe.style.width = '100%';
      iframe.style.height = '100%';
      iframe.allow =
          'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
      iframe.allowFullscreen = true;
      return iframe;
    });
  }

  @override
  Widget build(BuildContext context) {
    return HtmlElementView(viewType: _viewType);
  }
}

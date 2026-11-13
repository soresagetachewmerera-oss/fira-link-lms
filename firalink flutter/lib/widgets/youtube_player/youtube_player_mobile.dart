import 'package:flutter/material.dart';
import 'package:youtube_player_flutter/youtube_player_flutter.dart';
import '../../theme/app_theme.dart';

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
  late final YoutubePlayerController _controller;
  bool _isPlayerReady = false;

  @override
  void initState() {
    super.initState();
    _controller = YoutubePlayerController(
      initialVideoId: widget.videoId,
      flags: YoutubePlayerFlags(
        autoPlay: widget.autoPlay,
        mute: false,
        enableCaption: true,
        isLive: false,
        forceHD: false,
      ),
    )..addListener(_listener);
  }

  void _listener() {
    if (_isPlayerReady && mounted && !_controller.value.isFullScreen) {
      // Keep state synchronized
    }
  }

  @override
  void deactivate() {
    _controller.pause();
    super.deactivate();
  }

  @override
  void dispose() {
    _controller.removeListener(_listener);
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return YoutubePlayer(
      controller: _controller,
      showVideoProgressIndicator: true,
      progressIndicatorColor: AppTheme.primary,
      topActions: <Widget>[
        const SizedBox(width: 8.0),
        Expanded(
          child: Text(
            _controller.metadata.title,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 14.0,
            ),
            overflow: TextOverflow.ellipsis,
            maxLines: 1,
          ),
        ),
      ],
      onReady: () {
        _isPlayerReady = true;
      },
      onEnded: (data) {},
    );
  }
}

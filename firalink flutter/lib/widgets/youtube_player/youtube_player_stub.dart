import 'package:flutter/material.dart';

class PlatformYoutubePlayer extends StatelessWidget {
  final String videoId;
  final bool autoPlay;

  const PlatformYoutubePlayer({
    super.key,
    required this.videoId,
    this.autoPlay = true,
  });

  @override
  Widget build(BuildContext context) {
    return const Center(
      child: Text(
        'YouTube playback not supported on this platform',
        style: TextStyle(color: Colors.white),
      ),
    );
  }
}

import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../theme/app_theme.dart';
import 'youtube_id_helper.dart';
import 'youtube_player_platform.dart';

class AppYoutubePlayer extends StatelessWidget {
  final String? url;
  final String? videoId;
  final bool autoPlay;
  final double aspectRatio;
  final bool showOpenButton;

  const AppYoutubePlayer({
    super.key,
    this.url,
    this.videoId,
    this.autoPlay = true,
    this.aspectRatio = 16 / 9,
    this.showOpenButton = true,
  });

  String? get resolvedVideoId =>
      videoId != null && videoId!.isNotEmpty
          ? YoutubeIdHelper.extractId(videoId)
          : YoutubeIdHelper.extractId(url);

  Future<void> _openInYouTube(BuildContext context, String vidId) async {
    final youtubeUri = Uri.parse('https://www.youtube.com/watch?v=$vidId');
    try {
      if (await canLaunchUrl(youtubeUri)) {
        await launchUrl(youtubeUri, mode: LaunchMode.externalApplication);
      } else {
        await launchUrl(youtubeUri);
      }
    } catch (e) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Could not open YouTube: $e'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final id = resolvedVideoId;

    if (id == null || id.isEmpty) {
      return Container(
        color: Colors.black,
        padding: const EdgeInsets.all(16),
        child: Center(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.videocam_off, color: Colors.white70, size: 40),
              const SizedBox(height: 8),
              const Text(
                'Video not found or URL is invalid',
                style: TextStyle(color: Colors.white70, fontSize: 13),
                textAlign: TextAlign.center,
              ),
              if (url != null && url!.isNotEmpty) ...[
                const SizedBox(height: 12),
                ElevatedButton.icon(
                  onPressed: () {
                    final uri = Uri.tryParse(url!);
                    if (uri != null) {
                      launchUrl(uri, mode: LaunchMode.externalApplication);
                    }
                  },
                  icon: const Icon(Icons.open_in_browser, size: 16),
                  label: const Text('Open External Link'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppTheme.primary,
                    foregroundColor: Colors.white,
                    textStyle: const TextStyle(fontSize: 12),
                  ),
                ),
              ],
            ],
          ),
        ),
      );
    }

    return Column(
      mainAxisSize: MainAxisSize.min,
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        AspectRatio(
          aspectRatio: aspectRatio,
          child: Container(
            color: Colors.black,
            child: ClipRRect(
              borderRadius: BorderRadius.circular(4),
              child: PlatformYoutubePlayer(
                videoId: id,
                autoPlay: autoPlay,
              ),
            ),
          ),
        ),
        if (showOpenButton)
          Container(
            color: Colors.black.withValues(alpha: 0.9),
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 6,
                    vertical: 3,
                  ),
                  decoration: BoxDecoration(
                    color: const Color(0xFFFF0000),
                    borderRadius: BorderRadius.circular(4),
                  ),
                  child: const Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(Icons.play_arrow, color: Colors.white, size: 14),
                      Text(
                        'YouTube',
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: 11,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 8),
                const Expanded(
                  child: Text(
                    'Playing in-app. Or tap to watch on YouTube:',
                    style: TextStyle(
                      color: Colors.white70,
                      fontSize: 11,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
                TextButton.icon(
                  onPressed: () => _openInYouTube(context, id),
                  icon: const Icon(
                    Icons.open_in_new,
                    size: 14,
                    color: Colors.white,
                  ),
                  label: const Text(
                    'Watch',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  style: TextButton.styleFrom(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 4,
                    ),
                    minimumSize: Size.zero,
                    tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                  ),
                ),
              ],
            ),
          ),
      ],
    );
  }
}

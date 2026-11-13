import 'dart:io';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

class LotPhotoCaptureScreen extends StatefulWidget {
  final ValueChanged<List<File>>? onPhotosCaptured;

  const LotPhotoCaptureScreen({super.key, this.onPhotosCaptured});

  @override
  State<LotPhotoCaptureScreen> createState() => _LotPhotoCaptureScreenState();
}

class _LotPhotoCaptureScreenState extends State<LotPhotoCaptureScreen> {
  final ImagePicker _picker = ImagePicker();
  final List<File> _capturedPhotos = [];

  Future<void> _takePhoto() async {
    final XFile? photo = await _picker.pickImage(source: ImageSource.camera, imageQuality: 85);
    if (photo != null) {
      setState(() {
        _capturedPhotos.add(File(photo.path));
      });
    }
  }

  Future<void> _pickFromGallery() async {
    final List<XFile> photos = await _picker.pickMultiImage(imageQuality: 85);
    if (photos.isNotEmpty) {
      setState(() {
        _capturedPhotos.addAll(photos.map((p) => File(p.path)));
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    const primaryColor = Color(0xFF6D1A95);

    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: const Text(
          'Lot Evidence Photography',
          style: TextStyle(color: Color(0xFF1E293B), fontWeight: FontWeight.bold, fontSize: 16),
        ),
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Color(0xFF1E293B)),
          onPressed: () => Navigator.pop(context),
        ),
        actions: [
          if (_capturedPhotos.isNotEmpty)
            TextButton(
              onPressed: () {
                if (widget.onPhotosCaptured != null) {
                  widget.onPhotosCaptured!(_capturedPhotos);
                }
                Navigator.pop(context, _capturedPhotos);
              },
              child: const Text('Save Photos', style: TextStyle(color: primaryColor, fontWeight: FontWeight.bold)),
            ),
        ],
      ),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: primaryColor.withValues(alpha: 0.05),
                  borderRadius: BorderRadius.circular(14),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.camera_alt_outlined, color: primaryColor),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Text(
                        'Include close-up of green screen size, moisture meter reading, and GrainPro bagged warehouse stacks.',
                        style: TextStyle(fontSize: 12, color: Colors.grey.shade700),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 16),

              Expanded(
                child: _capturedPhotos.isEmpty
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.add_a_photo_outlined, size: 54, color: Colors.grey.shade300),
                            const SizedBox(height: 12),
                            const Text('No photos captured yet', style: TextStyle(color: Colors.grey)),
                          ],
                        ),
                      )
                    : GridView.builder(
                        itemCount: _capturedPhotos.length,
                        gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                          crossAxisCount: 3,
                          crossAxisSpacing: 10,
                          mainAxisSpacing: 10,
                        ),
                        itemBuilder: (context, index) {
                          return Stack(
                            fit: StackFit.expand,
                            children: [
                              ClipRRect(
                                borderRadius: BorderRadius.circular(10),
                                child: Image.file(_capturedPhotos[index], fit: BoxFit.cover),
                              ),
                              Positioned(
                                top: 4,
                                right: 4,
                                child: InkWell(
                                  onTap: () {
                                    setState(() {
                                      _capturedPhotos.removeAt(index);
                                    });
                                  },
                                  child: Container(
                                    padding: const EdgeInsets.all(2),
                                    decoration: const BoxDecoration(
                                      color: Colors.black54,
                                      shape: BoxShape.circle,
                                    ),
                                    child: const Icon(Icons.close, color: Colors.white, size: 14),
                                  ),
                                ),
                              ),
                            ],
                          );
                        },
                      ),
              ),

              Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      style: OutlinedButton.styleFrom(
                        foregroundColor: primaryColor,
                        side: const BorderSide(color: primaryColor),
                        minimumSize: const Size.fromHeight(50),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                      ),
                      icon: const Icon(Icons.photo_library_outlined),
                      label: const Text('Gallery'),
                      onPressed: _pickFromGallery,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: ElevatedButton.icon(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: primaryColor,
                        foregroundColor: Colors.white,
                        minimumSize: const Size.fromHeight(50),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                      ),
                      icon: const Icon(Icons.camera_alt),
                      label: const Text('Camera'),
                      onPressed: _takePhoto,
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}


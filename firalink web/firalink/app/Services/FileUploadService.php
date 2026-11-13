<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * File Upload Service
 *
 * This service is responsible for uploading files to the storage.
 *
 * @package App\Services
 */
class FileUploadService
{
    /**
     * Upload instructor profile photo
     */
    public function uploadProfilePhoto(UploadedFile $file, string $directory = 'instructors/photos'): string
    {
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs($directory, $filename, 'public');
        
        return $path;
    }

    /**
     * Upload instructor resume
     */
    public function uploadResume(UploadedFile $file): string
    {
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('instructors/resumes', $filename, 'public');
        
        return $path;
    }

    /**
     * Upload course featured image
     */
    public function uploadCourseImage(UploadedFile $file): string
    {
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedTypes)) {
            throw new \Exception('Invalid image type. Allowed types: JPEG, PNG, GIF, WEBP');
        }

        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($file->getSize() > $maxSize) {
            throw new \Exception('Image size must be less than 5MB');
        }

        $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('courses/images', $filename, 'public');
        
        return $path;
    }

    /**
     * Upload course intro video
     */
    public function uploadIntroVideo(UploadedFile $file): string
    {
        $allowedTypes = ['video/mp4', 'video/avi', 'video/quicktime', 'video/webm', 'video/x-msvideo'];
        $allowedExtensions = ['mp4', 'avi', 'mov', 'webm'];
        
        if (!in_array($file->getMimeType(), $allowedTypes) && 
            !in_array(strtolower($file->getClientOriginalExtension()), $allowedExtensions)) {
            throw new \Exception('Invalid video type. Allowed types: MP4, AVI, MOV, WEBM');
        }

        $maxSize = 500 * 1024 * 1024; // 500MB
        if ($file->getSize() > $maxSize) {
            throw new \Exception('Video size must be less than 500MB');
        }

        $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('courses/videos/intro', $filename, 'public');
        
        return $path;
    }

    /**
     * Upload lesson video
     */
    public function uploadLessonVideo(UploadedFile $file): string
    {
        $allowedTypes = ['video/mp4', 'video/avi', 'video/quicktime', 'video/webm', 'video/x-msvideo'];
        $allowedExtensions = ['mp4', 'avi', 'mov', 'webm'];
        
        if (!in_array($file->getMimeType(), $allowedTypes) && 
            !in_array(strtolower($file->getClientOriginalExtension()), $allowedExtensions)) {
            throw new \Exception('Invalid video type. Allowed types: MP4, AVI, MOV, WEBM');
        }

        $maxSize = 500 * 1024 * 1024; // 500MB
        if ($file->getSize() > $maxSize) {
            throw new \Exception('Video size must be less than 500MB');
        }

        $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('courses/videos/lessons', $filename, 'public');
        
        return $path;
    }

    /**
     * Upload assignment file
     */
    public function uploadAssignmentFile(UploadedFile $file): array
    {
        $allowedTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/zip',
            'application/x-zip-compressed',
            'text/plain',
        ];
        $allowedExtensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'zip', 'txt'];
        
        if (!in_array($file->getMimeType(), $allowedTypes) && 
            !in_array(strtolower($file->getClientOriginalExtension()), $allowedExtensions)) {
            throw new \Exception('Invalid file type. Allowed types: PDF, DOC, DOCX, PPT, PPTX, ZIP, TXT');
        }

        $maxSize = 10 * 1024 * 1024; // 10MB
        if ($file->getSize() > $maxSize) {
            throw new \Exception('File size must be less than 10MB');
        }

        $filename = time() . '_' . Str::random(10) . '_' . $file->getClientOriginalName();
        $path = $file->storeAs('courses/assignments', $filename, 'public');
        
        return [
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ];
    }

    /**
     * Upload payment receipt
     */
    public function uploadReceipt(UploadedFile $file): string
    {
        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
        
        if (!in_array($file->getMimeType(), $allowedTypes) && 
            !in_array(strtolower($file->getClientOriginalExtension()), $allowedExtensions)) {
            throw new \Exception('Invalid file type. Allowed types: PDF, JPG, JPEG, PNG');
        }

        $maxSize = 5 * 1024 * 1024;
        if ($file->getSize() > $maxSize) {
            throw new \Exception('File size must be less than 5MB');
        }

        $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('receipts', $filename, 'public');
        
        return $path;
    }

    /**
     * Upload branding assets such as logos and favicons.
     */
    public function uploadBrandAsset(UploadedFile $file, string $directory = 'branding'): string
    {
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/svg+xml', 'image/webp'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'svg', 'webp'];

        if (!in_array($file->getMimeType(), $allowedTypes) && !in_array(strtolower($file->getClientOriginalExtension()), $allowedExtensions)) {
            throw new \Exception('Invalid image type. Allowed types: JPG, JPEG, PNG, SVG, WEBP');
        }

        $maxSize = 3 * 1024 * 1024;
        if ($file->getSize() > $maxSize) {
            throw new \Exception('Image size must be less than 3MB');
        }

        $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs("branding/{$directory}", $filename, 'public');

        return $path;
    }

    /**
     * Upload assignment submission file
     */
    public function uploadAssignmentSubmissionFile(UploadedFile $file): array
    {
        $allowedTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/zip',
            'application/x-zip-compressed',
            'text/plain',
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
        ];
        $allowedExtensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'zip', 'txt', 'jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array($file->getMimeType(), $allowedTypes) && 
            !in_array(strtolower($file->getClientOriginalExtension()), $allowedExtensions)) {
            throw new \Exception('Invalid file type. Allowed types: PDF, DOC, DOCX, PPT, PPTX, ZIP, TXT, JPG, JPEG, PNG, GIF');
        }

        $maxSize = 10 * 1024 * 1024;
        if ($file->getSize() > $maxSize) {
            throw new \Exception('File size must be less than 10MB');
        }

        $filename = time() . '_' . Str::random(10) . '_' . $file->getClientOriginalName();
        $path = $file->storeAs('assignment-submissions', $filename, 'public');
        
        return [
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'file_size' => (string) $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ];
    }

    /**
     * Delete old file if exists
     */
    public function deleteFile(?string $filepath): void
    {
        if ($filepath && Storage::disk('public')->exists($filepath)) {
            Storage::disk('public')->delete($filepath);
        }
    }
}


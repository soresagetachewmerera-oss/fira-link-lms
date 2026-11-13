<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FileController extends Controller
{
    /**
     * Stream a video file with byte-range support
     */
    public function stream(Request $request)
    {
        $path = $request->query('path');

        if (!$path) {
            return response()->json(['error' => 'Path is required'], 400);
        }

        // Sanitize path by removing traversal components and leading slashes
        $cleanPath = ltrim(str_replace(['../', '..\\', 'storage/'], '', $path), '/\\');

        $storagePublicBase = realpath(storage_path('app/public'));
        $publicStorageBase = realpath(public_path('storage'));

        $fullPath = null;
        $candidate1 = realpath(storage_path('app/public/' . $cleanPath));
        if ($candidate1 && $storagePublicBase && str_starts_with($candidate1, $storagePublicBase) && is_file($candidate1)) {
            $fullPath = $candidate1;
        } else {
            $candidate2 = realpath(public_path('storage/' . $cleanPath));
            if ($candidate2 && $publicStorageBase && str_starts_with($candidate2, $publicStorageBase) && is_file($candidate2)) {
                $fullPath = $candidate2;
            }
        }

        if (!$fullPath) {
            // Generic 404 without disclosing internal OS server path
            return response()->json(['error' => 'File not found.'], 404);
        }

        // Security Authorization: Protect paid course lessons against unauthorized streaming/piracy
        $lessonId = $request->query('lesson_id');
        $lesson = null;
        if ($lessonId) {
            $lesson = \App\Models\Lesson::with('topic.course')->find($lessonId);
        } else {
            $lesson = \App\Models\Lesson::with('topic.course')
                ->where('video_path', $cleanPath)
                ->orWhere('video_path', '/' . $cleanPath)
                ->first();
        }

        if ($lesson && !$lesson->is_free) {
            $user = $request->user('sanctum') ?: auth('sanctum')->user() ?: \Illuminate\Support\Facades\Auth::user();

            if (!$user && $request->filled('token')) {
                $tokenString = $request->query('token');
                $token = \Laravel\Sanctum\PersonalAccessToken::findToken($tokenString);
                if ($token) {
                    $user = $token->tokenable;
                }
            }

            if (!$user) {
                return response()->json(['error' => 'Authentication required to stream this course video.'], 401);
            }

            $courseId = $lesson->topic?->course_id;
            if ($courseId) {
                $isOwnerOrAdmin = (method_exists($user, 'isAdmin') && $user->isAdmin()) 
                    || (method_exists($user, 'isInstructor') && $user->isInstructor() && $lesson->topic?->course?->instructor_id == $user->id);
                $isEnrolled = $user->enrollments()
                    ->where('course_id', $courseId)
                    ->whereIn('status', ['approved', 'completed'])
                    ->exists();

                if (!$isOwnerOrAdmin && !$isEnrolled) {
                    return response()->json(['error' => 'You are not enrolled in this course.'], 403);
                }
            }
        }

        $response = new BinaryFileResponse($fullPath);

        // This helps with iOS byte-range requests
        return $response;
    }
}

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

        $response = new BinaryFileResponse($fullPath);

        // This helps with iOS byte-range requests
        return $response;
    }
}

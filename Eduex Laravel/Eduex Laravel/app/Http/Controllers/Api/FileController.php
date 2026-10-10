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

        // Clean path - remove storage prefix if present as we use Storage::path which might look in storage/app/public
        $cleanPath = str_replace('storage/', '', $path);
        if (str_starts_with($cleanPath, '/')) {
            $cleanPath = substr($cleanPath, 1);
        }

        $fullPath = storage_path('app/public/' . $cleanPath);

        if (!file_exists($fullPath)) {
            // Try without public if it failed
            $fullPath = storage_path('app/' . $cleanPath);
            if (!file_exists($fullPath)) {
                return response()->json(['error' => 'File not found at ' . $fullPath], 404);
            }
        }

        $response = new BinaryFileResponse($fullPath);

        // This helps with iOS byte-range requests
        return $response;
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\PrivateDocumentStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a private evidence file to an admin.
 * Reached only through a short-lived signed URL (PrivateDocumentStore::adminUrl),
 * inside the admin route group (auth + role:admin).
 */
class PrivateFileController extends Controller
{
    public function show(Request $request, string $kind, int $id): StreamedResponse
    {
        $map = PrivateDocumentStore::ADMIN_KINDS[$kind] ?? null;
        abort_if($map === null, 404);

        [$modelClass, $column] = $map;
        $record = $modelClass::findOrFail($id);
        $path = $record->{$column};

        abort_unless(is_string($path) && $path !== '' && Storage::disk(PrivateDocumentStore::DISK)->exists($path), 404);

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'organization_id' => $record->organization_id ?? null,
            'action' => 'private_file_viewed',
            'auditable_type' => $modelClass,
            'auditable_id' => $record->id,
            'after_state' => ['kind' => $kind],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return Storage::disk(PrivateDocumentStore::DISK)->response($path, basename($path), [
            'Cache-Control' => 'no-cache, no-store, must-revalidate, private',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}

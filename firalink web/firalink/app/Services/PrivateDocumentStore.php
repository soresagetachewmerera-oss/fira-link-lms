<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Validates uploaded evidence (bank slips, contracts, lot evidence, report evidence,
 * licenses, lab reports) by its real content and stores it on the private disk.
 *
 * Files on this disk are never reachable through /storage. Admins open them through
 * short-lived signed URLs (see adminUrl()).
 */
class PrivateDocumentStore
{
    public const DISK = 'local';

    public const MAX_BYTES = 10 * 1024 * 1024;

    /** Minimum sizes by detected type. Real scans and photos are far larger than these. */
    public const MIN_BYTES = [
        'application/pdf' => 1024,
        'image/jpeg' => 10 * 1024,
        'image/png' => 10 * 1024,
    ];

    /** Images smaller than this are placeholders, not photos of a document. */
    public const MIN_IMAGE_SIDE = 300;

    private const EXTENSIONS = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    /**
     * Kinds of private files an admin can open, mapped to model and column.
     */
    public const ADMIN_KINDS = [
        'milestone-document' => [\App\Models\DealMilestone::class, 'document_path'],
        'deal-contract' => [\App\Models\DealRoom::class, 'contract_document_path'],
        'deal-dispute' => [\App\Models\DealRoom::class, 'escrow_proof_path'],
        'listing-evidence' => [\App\Models\CoffeeListing::class, 'evidence_document_path'],
        'report-evidence' => [\App\Models\TrustReport::class, 'evidence_path'],
        'seller-license' => [\App\Models\MarketplaceSellerProfile::class, 'license_document_path'],
        'sample-lab-report' => [\App\Models\MarketplaceSample::class, 'lab_report_path'],
    ];

    /**
     * Check a file by its content. Returns null when the file is acceptable,
     * otherwise a reason code: empty, too_large, too_small, bad_type, unreadable, placeholder.
     */
    public function rejectionReason(?UploadedFile $file, array $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png']): ?string
    {
        if (!$file || !$file->isValid()) {
            return 'unreadable';
        }

        $size = (int) $file->getSize();
        if ($size === 0) {
            return 'empty';
        }
        if ($size > self::MAX_BYTES) {
            return 'too_large';
        }

        // getMimeType() inspects the bytes (finfo), not the client name or extension.
        $mime = $file->getMimeType();
        if (!in_array($mime, $allowedMimes, true) || !isset(self::EXTENSIONS[$mime])) {
            return 'bad_type';
        }

        if ($size < self::MIN_BYTES[$mime]) {
            return 'too_small';
        }

        $path = $file->getRealPath();
        $head = (string) file_get_contents($path, false, null, 0, 65536);

        // A real document or photo uses many different byte values; a file of one
        // repeated byte (zeros, spaces, "AAAA…") is a placeholder.
        if (count(count_chars($head, 1)) < 32) {
            return 'placeholder';
        }

        if ($mime === 'application/pdf') {
            $tail = (string) file_get_contents($path, false, null, max(0, $size - 2048));
            if (!str_starts_with($head, '%PDF-') || !str_contains($tail, '%%EOF')) {
                return 'unreadable';
            }

            return null;
        }

        $info = @getimagesize($path);
        if ($info === false) {
            return 'unreadable';
        }
        if ($info[0] < self::MIN_IMAGE_SIDE || $info[1] < self::MIN_IMAGE_SIDE) {
            return 'too_small';
        }
        if (function_exists('imagecreatefromstring')) {
            $image = @imagecreatefromstring((string) file_get_contents($path));
            if ($image === false) {
                return 'unreadable';
            }
            imagedestroy($image);
        }

        return null;
    }

    /**
     * Human-readable message for a rejection reason (English; clients localize by `reason`).
     */
    public function message(string $reason): string
    {
        return match ($reason) {
            'empty' => 'The file is empty.',
            'too_large' => 'The file is larger than 10 MB.',
            'too_small' => 'The file is too small to be a real document or photo.',
            'bad_type' => 'Only PDF, JPG or PNG files are accepted.',
            'placeholder' => 'The file does not contain a real document or photo.',
            default => 'The file could not be read.',
        };
    }

    /**
     * JSON error body for a rejected file.
     */
    public function errorResponse(string $field, string $reason): array
    {
        return [
            'success' => false,
            'code' => 'FILE_REJECTED',
            'reason' => $reason,
            'message' => $this->message($reason),
            'errors' => [$field => [$this->message($reason)]],
        ];
    }

    /**
     * Store an already-validated file on the private disk with a server-chosen name.
     */
    public function store(UploadedFile $file, string $directory): string
    {
        $extension = self::EXTENSIONS[$file->getMimeType()] ?? 'bin';

        return $file->storeAs(trim($directory, '/'), Str::uuid() . '.' . $extension, self::DISK);
    }

    /**
     * Short-lived signed URL for an admin to open a private file.
     */
    public function adminUrl(string $kind, int $id, int $minutes = 10): string
    {
        return URL::temporarySignedRoute('admin.private-files.show', now()->addMinutes($minutes), [
            'kind' => $kind,
            'id' => $id,
        ]);
    }

    public function exists(?string $path): bool
    {
        return $path !== null && $path !== '' && Storage::disk(self::DISK)->exists($path);
    }
}

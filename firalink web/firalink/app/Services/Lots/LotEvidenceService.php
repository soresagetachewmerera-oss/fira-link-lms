<?php

namespace App\Services\Lots;

use App\Models\AuditLog;
use App\Models\CoffeeListing;
use App\Models\CoffeeListingMedia;
use App\Models\LotEvidence;
use App\Models\LotEvidencePage;
use App\Models\User;
use App\Notifications\LotEvidenceUpdate;
use App\Services\PrivateDocumentStore;
use App\Services\Verification\DocumentReviewService;
use App\Services\Verification\VerificationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Lot evidence: in-app photos and lab reports. The only writer of review fields on lot_evidence.
 * Originals (with EXIF and location) stay on the private verification disk. Buyers only ever see
 * a re-encoded copy of an approved photo, without EXIF or location, on the public disk.
 */
class LotEvidenceService
{
    public const DISK = 'verification';

    private const EXTENSIONS = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'];

    public function __construct(private LotLevelService $levels, private PrivateDocumentStore $files)
    {
    }

    private function audit(?User $actor, CoffeeListing $lot, string $action, LotEvidence $ev, array $before, array $after): void
    {
        AuditLog::create([
            'user_id' => $actor?->id,
            'organization_id' => $lot->organization_id,
            'action' => $action,
            'auditable_type' => LotEvidence::class,
            'auditable_id' => $ev->id,
            'before_state' => $before,
            'after_state' => $after + ['listing_id' => $lot->id],
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent() ?? 'system',
        ]);
    }

    private function notifyOwner(CoffeeListing $lot, string $event, LotEvidence $ev, array $params = []): void
    {
        $lot->organization?->owner()?->notify(new LotEvidenceUpdate($event, $lot, $ev, $params));
    }

    // ── Submit ──────────────────────────────────────────────────────────────

    /**
     * An in-app photo. The EXIF capture time must exist and be recent (gallery photos do not
     * count); the reviewer still checks every photo, since EXIF can be faked.
     */
    public function submitPhoto(CoffeeListing $lot, User $by, string $kind, UploadedFile $file, ?string $capturedAt, ?float $lat, ?float $lng, ?int $accuracy): LotEvidence
    {
        if (!in_array($kind, (array) config('verification.lots.photo_kinds'), true)) {
            throw new VerificationException('EVIDENCE_KIND_UNKNOWN', __('lots.errors.kind_unknown'), 422);
        }
        if ($reason = $this->files->rejectionReason($file, (array) config('verification.lots.photo_mimes'))) {
            throw new VerificationException('FILE_REJECTED', $this->files->message($reason), 422, ['reason' => $reason]);
        }

        $taken = $this->exifCaptureTime($file);
        if (!$taken) {
            throw new VerificationException('NOT_TAKEN_IN_APP', __('lots.errors.not_in_app'), 422);
        }
        $ageHours = $taken->diffInMinutes(now(), false) / 60;
        if ($ageHours > (int) config('verification.lots.photo_max_age_hours', 24) || $ageHours < -14) {
            throw new VerificationException('NOT_TAKEN_IN_APP', __('lots.errors.not_in_app'), 422);
        }
        if ($lat !== null && (abs($lat) > 90 || $lng === null || abs($lng) > 180)) {
            throw new VerificationException('LOCATION_INVALID', __('lots.errors.location_invalid'), 422);
        }

        return $this->store($lot, $by, $kind, [$file], [
            'captured_at' => $capturedAt ? Carbon::parse($capturedAt) : $taken,
            'latitude' => $lat,
            'longitude' => $lat !== null ? $lng : null,
            'location_accuracy_m' => $lat !== null && $accuracy !== null ? max(0, $accuracy) : null,
        ]);
    }

    /** A lab or cupping report (1–10 pages, PDF or images). */
    public function submitLabReport(CoffeeListing $lot, User $by, array $pages, ?string $labName, ?string $reportNumber, ?string $issuedAt): LotEvidence
    {
        $max = (int) config('verification.upload.max_pages', 10);
        if (count($pages) < 1 || count($pages) > $max) {
            throw new VerificationException('PAGE_COUNT', "Add between 1 and {$max} pages.", 422, ['min_pages' => 1, 'max_pages' => $max]);
        }
        foreach ($pages as $i => $page) {
            if ($reason = $this->files->rejectionReason($page)) {
                throw new VerificationException('FILE_REJECTED', $this->files->message($reason), 422, ['reason' => $reason, 'page' => $i + 1]);
            }
        }
        $labName = trim((string) $labName);
        if ($labName === '' || mb_strlen($labName) > 255) {
            throw new VerificationException('DETAILS_REQUIRED', __('lots.errors.lab_name'), 422, ['errors' => ['details.lab_name' => ['required']]]);
        }
        $issued = $issuedAt ? Carbon::parse($issuedAt)->startOfDay() : null;
        if ($issued && $issued->gt(now())) {
            throw new VerificationException('ISSUE_DATE_INVALID', 'The issue date cannot be in the future.', 422);
        }

        return $this->store($lot, $by, LotEvidence::KIND_LAB_REPORT, $pages, [
            'details' => array_filter([
                'lab_name' => $labName,
                'report_number' => $reportNumber !== null ? mb_substr(trim($reportNumber), 0, 80) : null,
                'issued_at' => $issued?->toDateString(),
            ]),
        ]);
    }

    private function store(CoffeeListing $lot, User $by, string $kind, array $files, array $attributes): LotEvidence
    {
        $ev = DB::transaction(function () use ($lot, $by, $kind, $files, $attributes) {
            $ev = LotEvidence::create($attributes + [
                'listing_id' => $lot->id,
                'organization_id' => $lot->organization_id,
                'kind' => $kind,
                'submitted_by' => $by->id,
                'submitted_at' => now(),
            ]);
            $ev->forceFill(['status' => LotEvidence::STATUS_IN_REVIEW])->save();

            foreach (array_values($files) as $i => $file) {
                $mime = $file->getMimeType();
                $path = $file->storeAs("organizations/{$lot->organization_id}/lots/{$lot->id}", Str::uuid() . '.' . self::EXTENSIONS[$mime], self::DISK);
                LotEvidencePage::create([
                    'lot_evidence_id' => $ev->id,
                    'page_number' => $i + 1,
                    'file_path' => $path,
                    'file_disk' => self::DISK,
                    'mime_type' => $mime,
                    'file_size_kb' => (int) ceil($file->getSize() / 1024),
                    'sha256' => hash_file('sha256', $file->getRealPath()),
                ]);
            }

            // A new upload replaces earlier drafts of the same kind; an approved one keeps
            // counting until the new one is approved.
            $lot->evidence()->where('kind', $kind)->where('id', '!=', $ev->id)
                ->whereIn('status', [LotEvidence::STATUS_IN_REVIEW, LotEvidence::STATUS_NEEDS_CHANGES, LotEvidence::STATUS_REJECTED, LotEvidence::STATUS_EXPIRED])
                ->get()
                ->each(fn ($old) => $old->forceFill(['status' => LotEvidence::STATUS_SUPERSEDED])->save());

            $this->audit($by, $lot, 'lot_evidence_submitted', $ev, [], [
                'kind' => $kind,
                'pages' => count($files),
                'has_location' => isset($attributes['latitude']),
            ]);

            return $ev;
        });

        app(DocumentReviewService::class)->alertAdmin('new', [
            'business' => $lot->organization?->name,
            'document' => __('lots.kinds.' . $kind, [], 'en') . ' · ' . $lot->title,
        ]);

        return $ev;
    }

    /** The EXIF DateTimeOriginal of a JPEG, read in the platform timezone (or its own offset). */
    private function exifCaptureTime(UploadedFile $file): ?Carbon
    {
        if ($file->getMimeType() !== 'image/jpeg' || !function_exists('exif_read_data')) {
            return null;
        }
        $exif = @exif_read_data($file->getRealPath(), 'EXIF', true);
        $raw = $exif['EXIF']['DateTimeOriginal'] ?? $exif['IFD0']['DateTime'] ?? null;
        if (!is_string($raw) || !preg_match('/^\d{4}:\d{2}:\d{2} \d{2}:\d{2}:\d{2}$/', $raw)) {
            return null;
        }
        $offset = $exif['EXIF']['OffsetTimeOriginal'] ?? null;
        $tz = is_string($offset) && preg_match('/^[+-]\d{2}:\d{2}$/', $offset) ? $offset : config('verification.timezone', 'UTC');

        try {
            return Carbon::createFromFormat('Y:m:d H:i:s', $raw, $tz);
        } catch (\Throwable) {
            return null;
        }
    }

    // ── Review ──────────────────────────────────────────────────────────────

    private function assertCanReview(User $reviewer, CoffeeListing $lot): void
    {
        if ($lot->organization && ($reason = DocumentReviewService::conflictReason($reviewer, $lot->organization))) {
            throw new VerificationException('SELF_REVIEW', __('verification.admin.self_review', ['reason' => __('verification.admin.self_review_' . $reason)]), 403);
        }
    }

    private function assertInReview(LotEvidence $ev): void
    {
        if ($ev->status !== LotEvidence::STATUS_IN_REVIEW) {
            throw new VerificationException('NOT_IN_REVIEW', 'This evidence is not waiting for review.', 409);
        }
    }

    /** @param array<string,bool> $checks e.g. lot_code_visible, lab_contacted */
    public function approve(LotEvidence $ev, User $reviewer, array $checks): LotEvidence
    {
        $lot = $ev->listing;
        $this->assertInReview($ev);
        $this->assertCanReview($reviewer, $lot);

        $required = $ev->isPhoto()
            ? (array) (config('verification.lots.photo_kind_checks')[$ev->kind] ?? [])
            : (array) config('verification.lots.lab_report_checks');
        $confirmed = [];
        foreach ($required as $check) {
            if (empty($checks[$check])) {
                throw new VerificationException('CHECK_REQUIRED', __('verification.admin.confirm_check', ['check' => __('lots.checks.' . $check)]), 422);
            }
            $confirmed[$check] = true;
        }

        $publicPath = $ev->isPhoto() ? $this->publicCopy($ev) : null;

        DB::transaction(function () use ($ev, $reviewer, $lot, $confirmed, $publicPath) {
            $before = $ev->only(['status']);
            $ev->forceFill([
                'status' => LotEvidence::STATUS_APPROVED,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'checks' => $confirmed ?: null,
                'review_reason_code' => null,
                'review_reason' => null,
                'public_path' => $publicPath,
                'expires_at' => $ev->isPhoto() ? now()->addDays((int) config('verification.lots.documented_valid_days', 30))->endOfDay() : null,
                'reminders_sent' => null,
            ])->save();

            $lot->evidence()->where('kind', $ev->kind)->where('id', '!=', $ev->id)
                ->where('status', LotEvidence::STATUS_APPROVED)->get()
                ->each(function (LotEvidence $old) {
                    $this->removePublicCopy($old);
                    $old->forceFill(['status' => LotEvidence::STATUS_SUPERSEDED])->save();
                });

            if ($publicPath && !$lot->media()->where('media_type', 'image')->exists()) {
                CoffeeListingMedia::create([
                    'listing_id' => $lot->id,
                    'file_path' => $publicPath,
                    'file_name' => basename($publicPath),
                    'file_size' => Storage::disk('public')->size($publicPath),
                    'mime_type' => 'image/jpeg',
                    'media_type' => 'image',
                    'is_primary' => true,
                    'sort_order' => 1,
                ]);
            }

            $this->audit($reviewer, $lot, 'lot_evidence_approved', $ev, $before, ['status' => 'approved', 'kind' => $ev->kind, 'checks' => $confirmed]);
            $this->levels->recompute($lot, $reviewer);
        });

        $this->notifyOwner($lot, 'approved', $ev);

        return $ev->refresh();
    }

    public function requestChanges(LotEvidence $ev, User $reviewer, ?string $code, ?string $text): LotEvidence
    {
        return $this->decline($ev, $reviewer, $code, $text, LotEvidence::STATUS_NEEDS_CHANGES);
    }

    public function reject(LotEvidence $ev, User $reviewer, ?string $code, ?string $text): LotEvidence
    {
        return $this->decline($ev, $reviewer, $code, $text, LotEvidence::STATUS_REJECTED);
    }

    private function decline(LotEvidence $ev, User $reviewer, ?string $code, ?string $text, string $status): LotEvidence
    {
        $lot = $ev->listing;
        $this->assertInReview($ev);
        $this->assertCanReview($reviewer, $lot);

        $code = in_array($code, (array) config('verification.lots.reasons'), true) ? $code : null;
        $text = trim((string) $text);
        if (!$code && $text === '') {
            throw new VerificationException('REASON_REQUIRED', __('verification.admin.reason_required'), 422);
        }
        $reason = $text !== '' ? mb_substr($text, 0, 1000) : __('lots.reasons.' . $code, [], 'en');

        DB::transaction(function () use ($ev, $reviewer, $lot, $code, $reason, $status) {
            $before = $ev->only(['status']);
            $ev->forceFill([
                'status' => $status,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_reason_code' => $code,
                'review_reason' => $reason,
            ])->save();
            $this->audit($reviewer, $lot, 'lot_evidence_' . $status, $ev, $before, ['status' => $status, 'reason_code' => $code, 'reason' => $reason]);
            $this->levels->recompute($lot, $reviewer);
        });

        $this->notifyOwner($lot, $status, $ev, ['reason' => $reason, 'reason_code' => $code]);

        return $ev->refresh();
    }

    /** Scheduled job: an approved photo passed its expiry date; the lot level drops. */
    public function expire(LotEvidence $ev): LotEvidence
    {
        $lot = $ev->listing;
        DB::transaction(function () use ($ev, $lot) {
            $before = $ev->only(['status']);
            $this->removePublicCopy($ev);
            $ev->forceFill(['status' => LotEvidence::STATUS_EXPIRED, 'public_path' => null])->save();
            $this->audit(null, $lot, 'lot_evidence_expired', $ev, $before, ['status' => 'expired']);
            $this->levels->recompute($lot);
        });
        $this->notifyOwner($lot, 'expired', $ev);

        return $ev;
    }

    public function remindExpiring(LotEvidence $ev, int $days): void
    {
        $sent = $ev->reminders_sent ?? [];
        if (in_array($days, $sent, true)) {
            return;
        }
        $ev->forceFill(['reminders_sent' => array_values(array_merge($sent, [$days]))])->save();
        $this->notifyOwner($ev->listing, 'expiring', $ev, ['days' => $days]);
    }

    /** The owner or a manager takes back evidence still in review. Files are deleted. */
    public function withdraw(LotEvidence $ev, User $by): void
    {
        $this->assertInReview($ev);
        DB::transaction(function () use ($ev, $by) {
            $this->audit($by, $ev->listing, 'lot_evidence_withdrawn', $ev, ['status' => $ev->status, 'kind' => $ev->kind], []);
            foreach ($ev->pages as $page) {
                Storage::disk($page->file_disk ?: self::DISK)->delete($page->file_path);
            }
            $ev->delete();
        });
    }

    // ── Public copy of an approved photo ─────────────────────────────────────

    /** Re-encodes the photo with GD, which drops EXIF (time, phone, location). Fails closed. */
    private function publicCopy(LotEvidence $ev): string
    {
        $page = $ev->pages()->first();
        if (!$page || !function_exists('imagecreatefromstring')) {
            throw new VerificationException('IMAGE_PROCESSING_UNAVAILABLE', __('lots.errors.image_processing'), 503);
        }
        $image = @imagecreatefromstring(Storage::disk($page->file_disk ?: self::DISK)->get($page->file_path));
        if ($image === false) {
            throw new VerificationException('IMAGE_PROCESSING_UNAVAILABLE', __('lots.errors.image_processing'), 503);
        }

        // Phones store rotation in EXIF, which the copy loses: apply it to the pixels first.
        $orientation = 1;
        if ($page->mime_type === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data(Storage::disk($page->file_disk ?: self::DISK)->path($page->file_path));
            $orientation = (int) ($exif['Orientation'] ?? 1);
        }
        $angle = match ($orientation) { 3 => 180, 6 => -90, 8 => 90, default => 0 };
        if ($angle !== 0) {
            $rotated = imagerotate($image, $angle, 0);
            imagedestroy($image);
            $image = $rotated;
        }

        ob_start();
        imagejpeg($image, null, 85);
        $bytes = ob_get_clean();
        imagedestroy($image);

        $path = "marketplace/lots/{$ev->listing_id}/evidence/" . Str::uuid() . '.jpg';
        Storage::disk('public')->put($path, $bytes);

        return $path;
    }

    private function removePublicCopy(LotEvidence $ev): void
    {
        if (!$ev->public_path) {
            return;
        }
        CoffeeListingMedia::where('listing_id', $ev->listing_id)->where('file_path', $ev->public_path)->delete();
        Storage::disk('public')->delete($ev->public_path);
    }
}

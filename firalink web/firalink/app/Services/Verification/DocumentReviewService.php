<?php

namespace App\Services\Verification;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\OrganizationVerification;
use App\Models\User;
use App\Models\VerificationDocument;
use App\Models\VerificationDocumentPage;
use App\Models\VerificationTier3Grant;
use App\Notifications\VerificationAdminAlert;
use App\Notifications\VerificationUpdate;
use App\Services\PrivateDocumentStore;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * The ONLY writer of verification document review fields and Tier 3 grants. Every change writes
 * an audit-log entry, recomputes the tier (TierService) and notifies the owner.
 */
class DocumentReviewService
{
    public const DISK = 'verification';

    private const EXTENSIONS = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'];

    public function __construct(private TierService $tiers, private PrivateDocumentStore $files)
    {
    }

    private function audit(?User $actor, Organization|int|null $org, string $action, VerificationDocument|Organization|VerificationTier3Grant $subject, array $before, array $after): void
    {
        AuditLog::create([
            'user_id' => $actor?->id,
            'organization_id' => $org instanceof Organization ? $org->id : $org,
            'action' => $action,
            'auditable_type' => $subject::class,
            'auditable_id' => $subject->id,
            'before_state' => $before,
            'after_state' => $after,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent() ?? 'system',
        ]);
    }

    /** Document types this business needs (all tiers it can reach). */
    public static function typesFor(Organization $org): array
    {
        return array_values(array_unique(array_merge(TierService::requiredDocuments($org, 1), TierService::requiredDocuments($org, 2))));
    }

    public static function ownerIdHash(string $number): string
    {
        $key = (string) config('verification.id_hash_key');
        if ($key === '') {
            throw new VerificationException('ID_HASH_NOT_CONFIGURED', 'Owner ID uploads are not available yet. Please try again later.', 503);
        }
        $normalized = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $number));

        return hash_hmac('sha256', $normalized, $key);
    }

    // ── Submit ──────────────────────────────────────────────────────────────

    /**
     * @param UploadedFile[] $pages
     */
    public function submit(Organization $org, User $by, string $type, array $pages, ?string $number, ?string $issuedAt, ?string $expiresAt, array $details = []): VerificationDocument
    {
        $config = TierService::documentTypeConfig($type);
        if (!$config || !in_array($type, self::typesFor($org), true)) {
            throw new VerificationException('DOCUMENT_TYPE_NOT_REQUIRED', 'This document is not part of your verification.', 422);
        }

        $minPages = (int) ($config['min_pages'] ?? 1);
        $maxPages = (int) config('verification.upload.max_pages', 10);
        if (count($pages) < $minPages || count($pages) > $maxPages) {
            throw new VerificationException('PAGE_COUNT', "Add between {$minPages} and {$maxPages} pages.", 422, ['min_pages' => $minPages, 'max_pages' => $maxPages]);
        }
        foreach ($pages as $i => $page) {
            if ($reason = $this->files->rejectionReason($page)) {
                throw new VerificationException('FILE_REJECTED', $this->files->message($reason), 422, ['reason' => $reason, 'page' => $i + 1]);
            }
        }

        $number = $number !== null ? trim($number) : null;
        if (($config['number'] ?? false) && ($number === null || $number === '')) {
            throw new VerificationException('NUMBER_REQUIRED', 'Enter the document number.', 422, ['errors' => ['document_number' => ['required']]]);
        }
        $hash = ($config['owner_id_hash'] ?? false) ? self::ownerIdHash((string) $number) : null;

        $expires = $expiresAt ? Carbon::parse($expiresAt)->startOfDay() : null;
        if (($config['expires'] ?? false) && (!$expires || $expires->lte(now()->startOfDay()))) {
            throw new VerificationException('EXPIRY_REQUIRED', 'Enter an expiry date in the future.', 422, ['errors' => ['expires_at' => ['after:today']]]);
        }
        $issued = $issuedAt ? Carbon::parse($issuedAt)->startOfDay() : null;
        if ($issued && $issued->gt(now())) {
            throw new VerificationException('ISSUE_DATE_INVALID', 'The issue date cannot be in the future.', 422, ['errors' => ['issued_at' => ['before_or_equal:today']]]);
        }

        $clean = [];
        foreach ($config['fields'] ?? [] as $field) {
            $value = trim((string) ($details[$field] ?? ''));
            if ($value === '' || mb_strlen($value) > 255) {
                throw new VerificationException('DETAILS_REQUIRED', 'Fill in every field.', 422, ['errors' => ["details.{$field}" => ['required']]]);
            }
            $clean[$field] = $value;
        }

        $doc = DB::transaction(function () use ($org, $by, $type, $config, $pages, $number, $hash, $issued, $expires, $clean) {
            $container = OrganizationVerification::firstOrCreate([
                'organization_id' => $org->id,
                'target_role' => $org->roleKey(),
            ]);

            $stored = [];
            foreach (array_values($pages) as $i => $page) {
                $mime = $page->getMimeType();
                $path = $page->storeAs("organizations/{$org->id}/documents", Str::uuid() . '.' . self::EXTENSIONS[$mime], self::DISK);
                $stored[] = [
                    'page_number' => $i + 1,
                    'file_path' => $path,
                    'file_disk' => self::DISK,
                    'mime_type' => $mime,
                    'file_size_kb' => (int) ceil($page->getSize() / 1024),
                    'sha256' => hash_file('sha256', $page->getRealPath()),
                ];
            }

            $doc = VerificationDocument::create([
                'organization_verification_id' => $container->id,
                'organization_id' => $org->id,
                'document_type' => $type,
                'file_path' => $stored[0]['file_path'],
                'file_disk' => self::DISK,
                'original_filename' => $type . '.' . pathinfo($stored[0]['file_path'], PATHINFO_EXTENSION),
                'file_size_kb' => array_sum(array_column($stored, 'file_size_kb')),
                'mime_type' => $stored[0]['mime_type'],
                'verification_hash' => $stored[0]['sha256'],
                'is_valid' => true,
                'document_number' => $number ?: null,
                'issued_at' => $issued,
                'expires_at' => $expires,
                'details' => $clean ?: null,
                'submitted_by' => $by->id,
                'submitted_at' => now(),
            ]);
            $doc->forceFill([
                'tier' => (int) $config['tier'],
                'status' => VerificationDocument::STATUS_IN_REVIEW,
                'document_number_hash' => $hash,
            ])->save();

            foreach ($stored as $row) {
                VerificationDocumentPage::create($row + ['verification_document_id' => $doc->id]);
            }

            // A new upload replaces earlier drafts of the same type. An approved one keeps counting
            // until the new one is approved.
            $org->verificationDocuments()
                ->where('document_type', $type)
                ->where('id', '!=', $doc->id)
                ->whereIn('status', [VerificationDocument::STATUS_IN_REVIEW, VerificationDocument::STATUS_NEEDS_CHANGES, VerificationDocument::STATUS_REJECTED, VerificationDocument::STATUS_EXPIRED])
                ->get()
                ->each(fn ($old) => $old->forceFill(['status' => VerificationDocument::STATUS_SUPERSEDED])->save());

            $this->audit($by, $org, 'verification_document_submitted', $doc, [], [
                'document_type' => $type,
                'tier' => (int) $config['tier'],
                'pages' => count($stored),
                'expires_at' => $expires?->toDateString(),
                'has_number' => (bool) $number,
            ]);

            return $doc;
        });

        $org->owner()?->notify(new VerificationUpdate('in_review', $org, $doc, ['days' => config('verification.expected_review_business_days')]));
        $this->alertAdmin('new', [
            'business' => $org->name,
            'document' => __('verification.doc_types.' . $type, [], 'en'),
        ]);

        return $doc;
    }

    public function alertAdmin(string $kind, array $data): void
    {
        $email = config('verification.admin_email');
        if (!$email) {
            Log::warning('verification.admin_email is not set; admin alert not sent.', ['kind' => $kind]);

            return;
        }
        try {
            Notification::route('mail', $email)->notify(new VerificationAdminAlert($kind, $data));
        } catch (\Throwable $e) {
            Log::error('Failed sending verification admin alert email: ' . $e->getMessage(), ['kind' => $kind]);
        }
    }

    // ── Review ──────────────────────────────────────────────────────────────

    /** Why this reviewer may not review this business, or null. */
    public static function conflictReason(User $reviewer, Organization $org): ?string
    {
        if (OrganizationMembership::where('organization_id', $org->id)->where('user_id', $reviewer->id)->exists()) {
            return 'member';
        }
        if ((int) $org->created_by === (int) $reviewer->id) {
            return 'creator';
        }

        $ownerIds = OrganizationMembership::where('organization_id', $org->id)->where('role', OrganizationMembership::ROLE_OWNER)->pluck('user_id');
        if ($reviewer->phone && User::whereIn('id', $ownerIds)->where('phone', $reviewer->phone)->exists()) {
            return 'phone';
        }

        $reviewerOrgIds = OrganizationMembership::where('user_id', $reviewer->id)->pluck('organization_id');
        if ($reviewerOrgIds->isNotEmpty()) {
            $reviewerHashes = VerificationDocument::whereIn('organization_id', $reviewerOrgIds)->whereNotNull('document_number_hash')->pluck('document_number_hash');
            if ($reviewerHashes->isNotEmpty() && $org->verificationDocuments()->whereIn('document_number_hash', $reviewerHashes)->exists()) {
                return 'id';
            }
        }

        return null;
    }

    private function assertCanReview(User $reviewer, Organization $org): void
    {
        if ($reason = self::conflictReason($reviewer, $org)) {
            throw new VerificationException('SELF_REVIEW', __('verification.admin.self_review', ['reason' => __('verification.admin.self_review_' . $reason)]), 403);
        }
    }

    private function assertInReview(VerificationDocument $doc): void
    {
        if ($doc->status !== VerificationDocument::STATUS_IN_REVIEW) {
            throw new VerificationException('NOT_IN_REVIEW', 'This document is not waiting for review.', 409);
        }
    }

    /**
     * @param array<string,bool> $checks reviewer confirmations, e.g. account_name_matches
     * @param bool $steppedUp the reviewer passed a fresh code or password (required for Tier 2+)
     */
    public function approve(VerificationDocument $doc, User $reviewer, array $checks, bool $steppedUp): VerificationDocument
    {
        $org = $doc->organization;
        $this->assertInReview($doc);
        $this->assertCanReview($reviewer, $org);

        if ((int) $doc->tier >= 2 && !$steppedUp) {
            throw new VerificationException('STEP_UP_REQUIRED', __('verification.admin.step_up_failed'), 403);
        }
        if ($doc->isExpiredNow()) {
            throw new VerificationException('DOCUMENT_EXPIRED', 'An expired document cannot be approved.', 422);
        }

        $required = TierService::documentTypeConfig($doc->document_type)['checks'] ?? [];
        $confirmed = [];
        foreach ($required as $check) {
            if (empty($checks[$check])) {
                throw new VerificationException('CHECK_REQUIRED', __('verification.admin.confirm_check', ['check' => __('verification.checks.' . $check)]), 422);
            }
            $confirmed[$check] = true;
        }

        DB::transaction(function () use ($doc, $reviewer, $org, $confirmed) {
            $before = $doc->only(['status']);
            $doc->forceFill([
                'status' => VerificationDocument::STATUS_APPROVED,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'checks' => $confirmed ?: null,
                'review_reason_code' => null,
                'review_reason' => null,
            ])->save();

            $org->verificationDocuments()
                ->where('document_type', $doc->document_type)
                ->where('id', '!=', $doc->id)
                ->where('status', VerificationDocument::STATUS_APPROVED)
                ->get()
                ->each(fn ($old) => $old->forceFill(['status' => VerificationDocument::STATUS_SUPERSEDED])->save());

            $this->audit($reviewer, $org, 'verification_document_approved', $doc, $before, ['status' => 'approved', 'checks' => $confirmed, 'tier' => $doc->tier]);
            $this->tiers->recompute($org, $reviewer);
        });

        $org->owner()?->notify(new VerificationUpdate('approved', $org, $doc));

        return $doc->refresh();
    }

    public function requestChanges(VerificationDocument $doc, User $reviewer, ?string $reasonCode, ?string $reasonText): VerificationDocument
    {
        return $this->decline($doc, $reviewer, $reasonCode, $reasonText, VerificationDocument::STATUS_NEEDS_CHANGES);
    }

    public function reject(VerificationDocument $doc, User $reviewer, ?string $reasonCode, ?string $reasonText): VerificationDocument
    {
        return $this->decline($doc, $reviewer, $reasonCode, $reasonText, VerificationDocument::STATUS_REJECTED);
    }

    private function decline(VerificationDocument $doc, User $reviewer, ?string $code, ?string $text, string $status): VerificationDocument
    {
        $org = $doc->organization;
        $this->assertInReview($doc);
        $this->assertCanReview($reviewer, $org);

        $code = in_array($code, config('verification.reasons', []), true) ? $code : null;
        $text = trim((string) $text);
        if (!$code && $text === '') {
            throw new VerificationException('REASON_REQUIRED', __('verification.admin.reason_required'), 422);
        }
        $reason = $text !== '' ? $text : __('verification.reasons.' . $code, [], 'en');

        DB::transaction(function () use ($doc, $reviewer, $org, $code, $reason, $status) {
            $before = $doc->only(['status']);
            $doc->forceFill([
                'status' => $status,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_reason_code' => $code,
                'review_reason' => $reason,
            ])->save();
            $this->audit($reviewer, $org, 'verification_document_' . $status, $doc, $before, ['status' => $status, 'reason_code' => $code, 'reason' => $reason]);
            $this->tiers->recompute($org, $reviewer);
        });

        $org->owner()?->notify(new VerificationUpdate($status, $org, $doc, ['reason' => $reason, 'reason_code' => $code]));

        return $doc->refresh();
    }

    /** Scheduled job: an approved document passed its expiry date. */
    public function expire(VerificationDocument $doc): VerificationDocument
    {
        $org = $doc->organization;
        DB::transaction(function () use ($doc, $org) {
            $before = $doc->only(['status']);
            $doc->forceFill(['status' => VerificationDocument::STATUS_EXPIRED])->save();
            $this->audit(null, $org, 'verification_document_expired', $doc, $before, ['status' => 'expired', 'expires_at' => $doc->expires_at?->toDateString()]);
            $this->tiers->recompute($org);
        });
        $org->owner()?->notify(new VerificationUpdate('expired', $org, $doc));

        return $doc;
    }

    /** Scheduled job: remind the owner before expiry (once per reminder day). */
    public function remindExpiring(VerificationDocument $doc, int $days): void
    {
        $sent = $doc->reminders_sent ?? [];
        if (in_array($days, $sent, true)) {
            return;
        }
        $doc->forceFill(['reminders_sent' => array_values(array_merge($sent, [$days]))])->save();
        $doc->organization->owner()?->notify(new VerificationUpdate('expiring', $doc->organization, $doc, ['days' => $days]));
    }

    // ── Tier 3 ──────────────────────────────────────────────────────────────

    /**
     * @param array<string,mixed> $answers checklist key => true, plus optional "{key}_note"
     */
    public function grantTier3(Organization $org, User $reviewer, array $answers, ?string $notes, bool $steppedUp): VerificationTier3Grant
    {
        $this->assertCanReview($reviewer, $org);
        if (!$steppedUp) {
            throw new VerificationException('STEP_UP_REQUIRED', __('verification.admin.step_up_failed'), 403);
        }
        if ($this->tiers->compute($org)['tier'] < 2 || $org->status === 'suspended') {
            throw new VerificationException('TIER2_REQUIRED', __('verification.admin.tier3_needs_tier2'), 422);
        }

        $checklist = [];
        foreach (config('verification.tiers.3.checklist', []) as $item) {
            if (empty($answers[$item])) {
                throw new VerificationException('CHECKLIST_INCOMPLETE', __('verification.admin.tier3_checklist'), 422, ['missing' => $item]);
            }
            $checklist[$item] = ['done' => true, 'note' => isset($answers[$item . '_note']) ? mb_substr(trim((string) $answers[$item . '_note']), 0, 500) : null];
        }

        return DB::transaction(function () use ($org, $reviewer, $checklist, $notes) {
            $grant = new VerificationTier3Grant();
            $grant->forceFill([
                'organization_id' => $org->id,
                'granted_by' => $reviewer->id,
                'granted_at' => now(),
                'expires_at' => now()->addMonths((int) config('verification.tiers.3.valid_months', 12)),
                'checklist' => $checklist,
                'notes' => $notes ? mb_substr($notes, 0, 2000) : null,
            ])->save();

            $this->audit($reviewer, $org, 'verification_tier3_granted', $grant, [], ['checklist' => $checklist, 'notes' => $grant->notes, 'expires_at' => $grant->expires_at->toIso8601String()]);
            $this->tiers->recompute($org, $reviewer);

            return $grant;
        });
    }

    /** Suspend or lift a suspension. A suspended business is hidden and its public page says so. */
    public function setSuspended(Organization $org, User $admin, bool $suspended, string $reason): void
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new VerificationException('REASON_REQUIRED', __('verification.admin.reason_required'), 422);
        }
        if ($suspended) {
            $this->assertCanReview($admin, $org);
        }
        DB::transaction(function () use ($org, $admin, $suspended, $reason) {
            $before = ['status' => $org->status];
            $org->forceFill(['status' => $suspended ? 'suspended' : 'active'])->save();
            $this->audit($admin, $org, $suspended ? 'business_suspended' : 'business_unsuspended', $org, $before, ['status' => $org->status, 'reason' => mb_substr($reason, 0, 1000)]);
            $this->tiers->recompute($org, $admin);
            // Slice 7: a suspension pauses the business's open deal rooms (both sides) until it is lifted.
            app(\App\Services\Trade\DealRoomService::class)->pauseForSuspension($org, $admin, $suspended);
        });
    }

    public function revokeTier3(Organization $org, User $admin, string $reason): void
    {
        DB::transaction(function () use ($org, $admin, $reason) {
            foreach ($org->tier3Grants()->active()->get() as $grant) {
                $grant->forceFill(['revoked_at' => now(), 'revoked_by' => $admin->id, 'revoke_reason' => $reason])->save();
                $this->audit($admin, $org, 'verification_tier3_revoked', $grant, [], ['reason' => $reason]);
            }
            $this->tiers->recompute($org, $admin);
        });
    }
}

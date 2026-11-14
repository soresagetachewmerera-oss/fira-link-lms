<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\VerificationDocument;
use App\Models\VerificationDocumentPage;
use App\Services\Auth\StaffTwoFactor;
use App\Services\Verification\DocumentReviewService;
use App\Services\Verification\TierService;
use App\Services\Verification\VerificationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/** Single review queue (oldest first). Every decision goes through DocumentReviewService. */
class VerificationReviewController extends Controller
{
    public function __construct(private DocumentReviewService $reviews)
    {
    }

    public function index()
    {
        $documents = VerificationDocument::with(['organization', 'submitter'])
            ->where('status', VerificationDocument::STATUS_IN_REVIEW)
            ->orderBy('submitted_at')
            ->orderBy('id')
            ->paginate(30);

        return view('admin.verification-review.index', ['documents' => $documents]);
    }

    public function business(Request $request, int $id)
    {
        $org = Organization::findOrFail($id);
        $documents = $org->verificationDocuments()->current()->with('pages')->orderByDesc('id')->get();

        $pageUrls = [];
        foreach ($documents as $doc) {
            foreach ($doc->pages as $page) {
                $pageUrls[$page->id] = URL::temporarySignedRoute('admin.verification-review.page', now()->addMinutes(10), ['page' => $page->id]);
            }
        }

        $hashes = $documents->pluck('document_number_hash')->filter()->unique();
        $sharedId = $hashes->isEmpty() ? collect() : Organization::whereIn('id', VerificationDocument::whereIn('document_number_hash', $hashes)
            ->where('organization_id', '!=', $org->id)->pluck('organization_id'))->pluck('name');

        return view('admin.verification-review.business', [
            'org' => $org,
            'documents' => $documents,
            'pageUrls' => $pageUrls,
            'conflict' => DocumentReviewService::conflictReason($request->user(), $org),
            'sharedIdNames' => $sharedId,
            'computed' => app(TierService::class)->compute($org),
            'tier3Grant' => $org->tier3Grants()->active()->latest('granted_at')->first(),
            'steppedUpFresh' => \App\Services\Auth\FreshAuth::isFresh($request),
        ]);
    }

    /** Streams one page of a document (signed, 10 minutes, audited). */
    public function page(Request $request, VerificationDocumentPage $page)
    {
        $disk = $page->file_disk ?: DocumentReviewService::DISK;
        abort_unless($page->file_path && Storage::disk($disk)->exists($page->file_path), 404);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'organization_id' => $page->document?->organization_id,
            'action' => 'verification_page_viewed',
            'auditable_type' => VerificationDocument::class,
            'auditable_id' => $page->verification_document_id,
            'after_state' => ['page' => $page->page_number],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return Storage::disk($disk)->response($page->file_path, 'page-' . $page->page_number . '.' . pathinfo($page->file_path, PATHINFO_EXTENSION), [
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function run(Request $request, Organization $org, callable $action)
    {
        try {
            $action();
        } catch (VerificationException $e) {
            if ($request->expectsJson()) {
                return $e->toResponse();
            }

            return back()->withInput()->with('error', $e->getMessage());
        }
        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('admin.verification-review.business', $org->id)->with('success', __('verification.admin.done'));
    }

    public function approve(Request $request, VerificationDocument $document)
    {
        return $this->run($request, $document->organization, fn () => $this->reviews->approve(
            $document,
            $request->user(),
            (array) $request->input('checks', []),
            (int) $document->tier >= 2 ? StaffTwoFactor::steppedUp($request) : true
        ));
    }

    public function requestChanges(Request $request, VerificationDocument $document)
    {
        return $this->run($request, $document->organization, fn () => $this->reviews->requestChanges(
            $document, $request->user(), $request->input('reason_code'), $request->input('reason')
        ));
    }

    public function reject(Request $request, VerificationDocument $document)
    {
        return $this->run($request, $document->organization, fn () => $this->reviews->reject(
            $document, $request->user(), $request->input('reason_code'), $request->input('reason')
        ));
    }

    public function grantTier3(Request $request, Organization $organization)
    {
        return $this->run($request, $organization, fn () => $this->reviews->grantTier3(
            $organization,
            $request->user(),
            (array) $request->input('checklist', []),
            $request->input('notes'),
            StaffTwoFactor::steppedUp($request)
        ));
    }

    public function revokeTier3(Request $request, Organization $organization)
    {
        $reason = trim((string) $request->input('reason'));

        return $this->run($request, $organization, function () use ($organization, $request, $reason) {
            if ($reason === '') {
                throw new VerificationException('REASON_REQUIRED', __('verification.admin.reason_required'), 422);
            }
            $this->reviews->revokeTier3($organization, $request->user(), $reason);
        });
    }

    public function suspend(Request $request, Organization $organization)
    {
        return $this->run($request, $organization, fn () => $this->reviews->setSuspended($organization, $request->user(), true, (string) $request->input('reason')));
    }

    public function unsuspend(Request $request, Organization $organization)
    {
        return $this->run($request, $organization, fn () => $this->reviews->setSuspended($organization, $request->user(), false, (string) $request->input('reason')));
    }
}

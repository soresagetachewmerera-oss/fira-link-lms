<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Services\EntitlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrganizationAuditLogController extends Controller
{
    protected EntitlementService $entitlementService;

    public function __construct(EntitlementService $entitlementService)
    {
        $this->entitlementService = $entitlementService;
    }

    /**
     * Resolve and authorize organization access for owner/manager/admin.
     */
    private function resolveOrg(Request $request, int $id): ?Organization
    {
        $user = $request->user();
        if (!$user) {
            return null;
        }

        $org = Organization::find($id);
        if (!$org) {
            return null;
        }

        if ($user->isAdmin()) {
            return $org;
        }

        // Only active owners and managers can access compliance audit trails
        $membership = $org->memberships()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if (!$membership || !in_array($membership->role, ['owner', 'manager'], true)) {
            return null;
        }

        return $org;
    }

    /**
     * GET /api/organizations/{id}/audit-logs
     * Returns paginated audit trail for organization.
     */
    public function index(Request $request, int $id): JsonResponse
    {
        $org = $this->resolveOrg($request, $id);
        if (!$org) {
            return response()->json(['success' => false, 'message' => 'Resource not found.'], 404);
        }

        $logs = AuditLog::with('user')
            ->where('organization_id', $org->id)
            ->latest('created_at')
            ->paginate(25);

        $logs->getCollection()->transform(function (AuditLog $log) {
            return [
                'id' => $log->id,
                'action' => $log->action,
                'user' => $log->user ? [
                    'id' => $log->user->id,
                    'name' => $log->user->name,
                ] : null,
                'auditable_type' => class_basename($log->auditable_type ?? ''),
                'auditable_id' => $log->auditable_id,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at?->toIso8601String(),
                'details' => $this->sanitizeDetails($log->after ?? $log->metadata ?? []),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    /**
     * GET /api/organizations/{id}/audit-logs/export
     * Exports compliance audit trail to CSV. Gated by 'audit_log_export' SaaS entitlement.
     */
    public function export(Request $request, int $id): JsonResponse|StreamedResponse
    {
        $org = $this->resolveOrg($request, $id);
        if (!$org) {
            return response()->json(['success' => false, 'message' => 'Resource not found.'], 404);
        }

        // Enforce SaaS plan entitlement
        if (!$this->entitlementService->can($org, 'audit_log_export')) {
            return response()->json(
                array_merge(['success' => false], $this->entitlementService->limitReachedResponse($org, 'audit_log_export')),
                403
            );
        }

        // Audit the export action
        AuditLog::create([
            'user_id' => $request->user()->id,
            'organization_id' => $org->id,
            'action' => 'audit_logs_exported',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        $fileName = sprintf('audit-trail-%s-%s.csv', $org->slug, now()->format('Y-m-d'));

        return new StreamedResponse(function () use ($org) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Log ID', 'Timestamp (UTC)', 'Action', 'User ID', 'User Name', 'IP Address', 'Auditable Type', 'Auditable ID']);

            AuditLog::with('user')
                ->where('organization_id', $org->id)
                ->latest('created_at')
                ->chunk(200, function ($records) use ($handle) {
                    foreach ($records as $log) {
                        fputcsv($handle, [
                            $log->id,
                            $log->created_at?->toIso8601String() ?? '',
                            $log->action,
                            $log->user_id ?? '',
                            $log->user?->name ?? 'System',
                            $log->ip_address ?? '',
                            class_basename($log->auditable_type ?? ''),
                            $log->auditable_id ?? '',
                        ]);
                    }
                });

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ]);
    }

    private function sanitizeDetails(mixed $details): array
    {
        if (!is_array($details)) {
            return [];
        }

        $redactedKeys = ['password', 'token', 'secret', 'key', 'auth'];
        $clean = [];

        foreach ($details as $k => $v) {
            foreach ($redactedKeys as $rk) {
                if (stripos((string) $k, $rk) !== false) {
                    $v = '***';
                    break;
                }
            }
            $clean[$k] = is_array($v) ? $this->sanitizeDetails($v) : $v;
        }

        return $clean;
    }
}

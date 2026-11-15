<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Tour;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Slice 8 admin details for events and tours: category, online, agenda, member ticket, service
 * fee, refund policy, dates, deposit and safety, and the organizer or host business (linked by Fira
 * ID; "verified" shows only while that business has an active Tier 1).
 */
class EventTourFlowController extends Controller
{
    private function business(?string $firaId): array
    {
        $firaId = strtoupper(trim((string) $firaId));
        if ($firaId === '') {
            return [null, null];
        }
        $org = Organization::whereNotNull('tier1_first_granted_at')->where('fira_id', $firaId)->first();

        return [$org, $org ? null : __('bookings.admin_flow.fira_id_unknown')];
    }

    /** "09:00 | Opening" per line. */
    private static function agenda(?string $text): array
    {
        return collect(preg_split('/\r?\n/', (string) $text))->map(fn ($l) => trim($l))->filter()->map(function ($l) {
            [$time, $title] = array_pad(array_map('trim', explode('|', $l, 2)), 2, null);

            return $title === null ? ['time' => null, 'title' => mb_substr($time, 0, 200)] : ['time' => mb_substr($time, 0, 20), 'title' => mb_substr($title, 0, 200)];
        })->values()->take(30)->all();
    }

    private function audit(Request $request, string $type, int $id, array $after): void
    {
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'booking_flow_details_updated',
            'auditable_type' => $type,
            'auditable_id' => $id,
            'after_state' => $after,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    public function updateEvent(Request $request, Event $event): RedirectResponse
    {
        $v = $request->validate([
            'category' => 'nullable|in:trade_fair,training,cupping,networking',
            'is_online' => 'nullable|boolean',
            'member_price' => 'nullable|numeric|min:0',
            'service_fee' => 'nullable|numeric|min:0',
            'refund_policy' => 'nullable|string|max:2000',
            'agenda_text' => 'nullable|string|max:6000',
            'organizer_fira_id' => 'nullable|string|max:40',
            'organized_by_fira_link' => 'nullable|boolean',
        ]);
        [$org, $error] = $this->business($v['organizer_fira_id'] ?? null);
        if ($error) {
            return back()->withInput()->withErrors(['organizer_fira_id' => $error]);
        }
        $event->forceFill([
            'category' => $v['category'] ?? null,
            'is_online' => $request->boolean('is_online'),
            'member_price' => $v['member_price'] ?? null,
            'service_fee' => $v['service_fee'] ?? 0,
            'refund_policy' => $v['refund_policy'] ?? null,
            'agenda' => self::agenda($v['agenda_text'] ?? null),
            'organizer_organization_id' => $org?->id,
            'organized_by_fira_link' => $request->boolean('organized_by_fira_link') && !$org,
        ])->save();
        $this->audit($request, Event::class, $event->id, ['organizer' => $org?->id, 'fira_link' => (bool) $event->organized_by_fira_link]);

        return back()->with('success', __('bookings.admin_flow.saved'));
    }

    public function updateTour(Request $request, Tour $tour): RedirectResponse
    {
        $v = $request->validate([
            'host_fira_id' => 'nullable|string|max:40',
            'deposit_percent' => 'required|integer|min:1|max:100',
            'balance_due_days' => 'required|integer|min:0|max:180',
            'safety_notes' => 'nullable|string|max:2000',
            'dates_text' => 'nullable|string|max:2000',
        ]);
        [$org, $error] = $this->business($v['host_fira_id'] ?? null);
        if ($error) {
            return back()->withInput()->withErrors(['host_fira_id' => $error]);
        }
        $dates = collect(preg_split('/[\s,]+/', (string) ($v['dates_text'] ?? '')))->filter()
            ->map(fn ($d) => rescue(fn () => \Carbon\Carbon::parse($d)->toDateString(), null, false))->filter()->unique()->sort()->values()->all();
        $tour->forceFill([
            'host_organization_id' => $org?->id,
            'deposit_percent' => (int) $v['deposit_percent'],
            'balance_due_days' => (int) $v['balance_due_days'],
            'safety_notes' => $v['safety_notes'] ?? null,
            'available_schedules' => $dates,
        ])->save();
        $this->audit($request, Tour::class, $tour->id, ['host' => $org?->id, 'dates' => $dates]);

        return back()->with('success', __('bookings.admin_flow.saved'));
    }
}

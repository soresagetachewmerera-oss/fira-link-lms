<?php

namespace App\Support;

use App\Models\Enrollment;
use App\Models\Event;
use App\Models\EventBooking;
use App\Models\Organization;
use App\Models\Tour;
use App\Models\TourBooking;
use App\Models\User;
use Carbon\Carbon;

/**
 * Slice 8 fields for events and tours: who organizes or hosts (verified only through an active
 * Tier 1 of the linked business, never a plan), ticket options, tour dates with places left and the
 * deposit and balance of a tour booking.
 */
class EventTourPresenter
{
    /** @return array{type:string,name:?string,verified:bool,fira_id:?string,since:?int,past_events?:int}|null */
    public static function business(?Organization $org, bool $firaLink, string $kind): ?array
    {
        if ($firaLink) {
            return ['type' => 'fira_link', 'name' => 'Fira Link', 'verified' => true, 'fira_id' => null, 'since' => null];
        }
        if (!$org) {
            return null;
        }
        $s = BusinessSummary::of($org);

        return [
            'type' => 'business',
            'name' => $org->name,
            'verified' => ($s['verification_tier'] ?? 0) >= 1,
            'fira_id' => $s['fira_id'],
            'since' => $org->created_at?->year,
        ] + ($kind === 'event' ? [
            'past_events' => Event::where('organizer_organization_id', $org->id)->where('end_date', '<', now()->toDateString())->count(),
        ] : []);
    }

    public static function organizer(Event $e): ?array
    {
        return self::business($e->organizerOrganization, (bool) $e->organized_by_fira_link, 'event');
    }

    public static function host(Tour $t): ?array
    {
        return self::business($t->hostOrganization, false, 'tour');
    }

    /** Academy member: at least one approved course enrollment. */
    public static function isAcademyMember(?User $user): bool
    {
        return $user !== null && Enrollment::where('user_id', $user->id)->whereIn('status', [Enrollment::STATUS_APPROVED, Enrollment::STATUS_COMPLETED])->exists();
    }

    /** @return list<array{type:string,price:float,eligible:bool}> */
    public static function tickets(Event $e, ?User $user): array
    {
        $out = [['type' => 'general', 'price' => (float) $e->price, 'eligible' => true]];
        if ($e->member_price !== null) {
            $out[] = ['type' => 'member', 'price' => (float) $e->member_price, 'eligible' => self::isAcademyMember($user)];
        }

        return $out;
    }

    public static function eventFields(Event $e, ?User $user): array
    {
        return [
            'category' => $e->category,
            'is_online' => (bool) $e->is_online,
            'agenda' => $e->agenda ?? [],
            'tickets' => self::tickets($e, $user),
            'service_fee' => (float) $e->service_fee,
            'refund_policy' => $e->refund_policy,
            'seats_left' => $e->remainingSeats(),
            'organizer' => self::organizer($e),
        ];
    }

    // ── Tours ───────────────────────────────────────────────────────────────

    /** Future dates the tour runs on (schedules, else the start date). */
    public static function tourDates(Tour $t): array
    {
        $raw = collect($t->available_schedules ?? [])->map(fn ($s) => is_array($s) ? ($s['date'] ?? null) : $s)->filter();
        if ($raw->isEmpty() && $t->start_date) {
            $raw = collect([$t->start_date->toDateString()]);
        }

        return $raw->map(fn ($d) => rescue(fn () => Carbon::parse($d)->toDateString(), null, false))
            ->filter(fn ($d) => $d && $d >= now()->toDateString())
            ->unique()->sort()->values()->all();
    }

    public static function placesLeft(Tour $t, string $date): int
    {
        if (!$t->total_seats) {
            return 0;
        }
        $taken = TourBooking::where('tour_id', $t->id)->whereDate('schedule_date', $date)
            ->whereIn('status', [TourBooking::STATUS_PENDING, TourBooking::STATUS_CONFIRMED])->sum('participants');

        return max(0, (int) $t->total_seats - (int) $taken);
    }

    /**
     * Deposit now and balance due [balance_due_days] before the tour. A tour that starts sooner
     * than that is paid in full now.
     *
     * @return array{total:float, deposit:float, balance:float, balance_due:?string, deposit_percent:int}
     */
    public static function tourSplit(Tour $t, string $date, int $guests): array
    {
        $total = round((float) $t->price * $guests, 2);
        $pct = (int) ($t->deposit_percent ?: 30);
        $due = Carbon::parse($date)->subDays((int) ($t->balance_due_days ?? 14))->startOfDay();
        if ($total <= 0 || $pct >= 100 || !$due->isFuture()) {
            return ['total' => $total, 'deposit' => $total, 'balance' => 0.0, 'balance_due' => null, 'deposit_percent' => 100];
        }
        $deposit = round($total * $pct / 100, 2);

        return ['total' => $total, 'deposit' => $deposit, 'balance' => round($total - $deposit, 2), 'balance_due' => $due->toDateString(), 'deposit_percent' => $pct];
    }

    public static function tourFields(Tour $t): array
    {
        return [
            'region' => $t->destination,
            'host' => self::host($t),
            'safety_notes' => $t->safety_notes,
            'deposit_percent' => (int) ($t->deposit_percent ?: 30),
            'balance_due_days' => (int) ($t->balance_due_days ?? 14),
            'next_dates' => collect(self::tourDates($t))->take(6)->map(fn ($d) => ['date' => $d, 'places_left' => self::placesLeft($t, $d)])->values()->all(),
        ];
    }

    /** A tour booking as its owner sees it: what is paid and what is due. */
    public static function tourBookingPayment(TourBooking $b): array
    {
        return [
            'deposit_amount' => $b->deposit_amount !== null ? (float) $b->deposit_amount : (float) $b->total_amount,
            'balance_amount' => (float) ($b->balance_amount ?? 0),
            'balance_due_date' => $b->balance_due_date?->toDateString(),
            'deposit_paid' => (bool) $b->deposit_paid_at,
            'balance_paid' => (bool) $b->balance_paid_at || (float) ($b->balance_amount ?? 0) <= 0,
            'payment_method' => $b->payment_method,
        ];
    }

    public static function eventBookingFields(EventBooking $b): array
    {
        return [
            'ticket_type' => $b->ticket_type ?? 'general',
            'service_fee' => (float) ($b->service_fee ?? 0),
            'company' => $b->company,
            'payment_method' => $b->payment_method,
            // Shown in purple until finance confirms a bank slip.
            'in_review' => $b->payment_status === 'in_review',
        ];
    }
}

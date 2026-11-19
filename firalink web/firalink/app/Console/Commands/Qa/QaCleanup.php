<?php

namespace App\Console\Commands\Qa;

use App\Models\Payment;
use App\Models\User;
use App\Services\PrivateDocumentStore;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Removes QA data: accounts …@qa.firalink.test (one run, or all with --all), their businesses,
 * bookings, payments and slips, chats, reports, deal rooms, lots, and the QA events and tours.
 * Real accounts and real content are never touched.
 */
class QaCleanup extends Command
{
    use GuardsQa;

    protected $signature = 'qa:cleanup {--run= : One run name} {--all : Every QA run} {--dry-run : Only count}';

    protected $description = 'Remove QA accounts and everything they created (never in production)';

    public function handle(): int
    {
        if (!$this->qaAllowed()) {
            return self::FAILURE;
        }
        $run = strtolower((string) $this->option('run'));
        if ($run === '' && !$this->option('all')) {
            $this->error('Give --run=<name> or --all.');

            return self::FAILURE;
        }
        if ($run !== '' && !preg_match('/^[a-z0-9]{3,20}$/', $run)) {
            $this->error('Run name: 3 to 20 letters or digits.');

            return self::FAILURE;
        }
        $pattern = ($run !== '' ? $run . '.' : '') . '%@' . $this->qaDomain();
        $slug = ($run !== '' ? $run : '%') . '-%';

        // Accounts made through phone sign-up have no email: QA names them "QA …" on a QA_PHONES number.
        $qaPhones = collect(config('qa.phones', []))->map(fn ($p) => preg_replace('/\D/', '', $p))->filter();
        $phoneVariants = $qaPhones->flatMap(fn ($p) => [$p, '+' . $p, '0' . substr($p, 3)])->all();
        $users = User::where('email', 'like', $pattern)
            ->orWhere(fn ($q) => $q->whereNull('email')->where('name', 'like', 'QA %')->whereIn('phone', $phoneVariants ?: ['-']))
            ->pluck('id');
        $orgs = DB::table('organizations')->whereIn('created_by', $users)->where('name', 'like', 'QA %')->pluck('id');
        $events = DB::table('events')->where('title', 'like', 'QA %')->where('slug', 'like', $slug)->pluck('id');
        $tours = DB::table('tours')->where('title', 'like', 'QA %')->where('slug', 'like', $slug)->pluck('id');
        $eb = DB::table('event_bookings')->whereIn('event_id', $events)->orWhereIn('user_id', $users)->pluck('id');
        $tb = DB::table('tour_bookings')->whereIn('tour_id', $tours)->orWhereIn('user_id', $users)->pluck('id');
        $payments = Payment::where(fn ($q) => $q->whereIn('event_booking_id', $eb)->orWhereIn('tour_booking_id', $tb)->orWhereIn('user_id', $users))->get(['id', 'receipt_file']);
        $convs = DB::table('trade_conversations')->where(fn ($q) => $q->whereIn('buyer_organization_id', $orgs)->orWhereIn('seller_organization_id', $orgs))->pluck('id');
        $deals = DB::table('deal_rooms')->where(fn ($q) => $q->whereIn('organization_id', $orgs)->orWhereIn('buyer_organization_id', $orgs))->pluck('id');
        $lots = DB::table('coffee_listings')->whereIn('organization_id', $orgs)->pluck('id');

        $this->table(['What', 'Count'], [
            ['Accounts', $users->count()], ['Businesses', $orgs->count()], ['Events', $events->count()], ['Tours', $tours->count()],
            ['Event bookings', $eb->count()], ['Tour bookings', $tb->count()], ['Payments', $payments->count()],
            ['Chats', $convs->count()], ['Deal rooms', $deals->count()], ['Lots', $lots->count()],
        ]);
        if ($this->option('dry-run')) {
            $this->info('Dry run: nothing removed.');

            return self::SUCCESS;
        }

        foreach ($payments as $p) {
            if ($p->receipt_file) {
                Storage::disk(PrivateDocumentStore::DISK)->delete($p->receipt_file);
            }
        }
        DB::transaction(function () use ($users, $orgs, $events, $tours, $eb, $tb, $payments, $convs, $deals, $lots) {
            DB::table('trust_reports')->whereIn('reporter_id', $users)->orWhereIn('reported_organization_id', $orgs)->delete();
            DB::table('trade_messages')->whereIn('trade_conversation_id', $convs)->delete();
            DB::table('trade_conversations')->whereIn('id', $convs)->delete();
            DB::table('deal_milestones')->whereIn('deal_room_id', $deals)->delete();
            DB::table('deal_rooms')->whereIn('id', $deals)->delete();
            DB::table('payments')->whereIn('id', $payments->pluck('id'))->delete();
            DB::table('event_bookings')->whereIn('id', $eb)->delete();
            DB::table('tour_bookings')->whereIn('id', $tb)->delete();
            DB::table('events')->whereIn('id', $events)->delete();
            DB::table('tours')->whereIn('id', $tours)->delete();
            foreach (['marketplace_quotations' => 'organization_id', 'marketplace_samples' => 'organization_id'] as $table => $col) {
                DB::table($table)->whereIn($col, $orgs)->delete();
            }
            DB::table('marketplace_samples')->whereIn('buyer_organization_id', $orgs)->delete();
            DB::table('marketplace_rfqs')->whereIn('organization_id', $orgs)->delete();
            DB::table('coffee_listings')->whereIn('id', $lots)->delete();
            DB::table('business_blocks')->whereIn('organization_id', $orgs)->orWhereIn('blocked_organization_id', $orgs)->delete();
            DB::table('payout_accounts')->whereIn('organization_id', $orgs)->delete();
            DB::table('verification_documents')->whereIn('organization_id', $orgs)->delete();
            DB::table('organization_verifications')->whereIn('organization_id', $orgs)->delete();
            DB::table('organization_memberships')->whereIn('organization_id', $orgs)->orWhereIn('user_id', $users)->delete();
            DB::table('users')->whereIn('id', $users)->update(['active_organization_id' => null]);
            DB::table('organizations')->whereIn('id', $orgs)->delete();
            DB::table('notifications')->where('notifiable_type', User::class)->whereIn('notifiable_id', $users)->delete();
            DB::table('personal_access_tokens')->where('tokenable_type', User::class)->whereIn('tokenable_id', $users)->delete();
            DB::table('model_has_roles')->where('model_type', User::class)->whereIn('model_id', $users)->delete();
            DB::table('users')->whereIn('id', $users)->delete();
        });
        $this->info('QA data removed. Audit log entries are kept.');

        return self::SUCCESS;
    }
}

<?php

namespace Tests\Feature;

use App\Models\BusinessBlock;
use App\Models\CoffeeListing;
use App\Models\DealMilestone;
use App\Models\DealRoom;
use App\Models\MarketplaceRfq;
use App\Models\OrganizationMembership;
use App\Models\Organization;
use App\Models\PayoutAccount;
use App\Models\TradeMessage;
use App\Models\TrustReport;
use App\Models\User;
use App\Services\Auth\FreshAuth;
use App\Services\Trade\TradeService;
use App\Services\Verification\VerificationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\ApprovesBusinesses;
use Tests\Concerns\BuildsTradeFixtures;
use Tests\TestCase;

/**
 * Slice 7: deal room with six steps in order, payout account, business chat with flagged
 * messages, reports (the admin decides) and blocks.
 */
class DealRoomTrustTest extends TestCase
{
    use DatabaseTransactions, BuildsTradeFixtures, ApprovesBusinesses;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        Storage::fake('verification');
        Notification::fake();
    }

    private function business(string $role, bool $approved = true): array
    {
        $owner = $this->makeUser();
        $owner->forceFill(['name' => 'Person ' . Str::random(6), 'phone' => '+2519' . random_int(10000000, 99999999)])->save();
        $org = $this->makeOrganization($owner);
        $org->forceFill(['business_role' => $role, 'created_by' => $owner->id])->save();
        if ($approved) {
            $org = $this->approveBusiness($org, $role);
        }
        $owner->forceFill(['active_organization_id' => $org->id])->save();

        return [$org->fresh(), $owner];
    }

    private function lot(Organization $org, User $owner): CoffeeListing
    {
        return CoffeeListing::create([
            'seller_id' => $owner->id, 'organization_id' => $org->id, 'title' => 'Guji G1 ' . Str::random(4),
            'origin_region' => 'Guji', 'processing_method' => 'natural', 'grade' => 'grade_1',
            'total_quantity_bags' => 100, 'available_quantity_bags' => 100, 'indicative_price_usd_per_lb' => 5,
            'status' => 'active', 'sample_available' => true, 'sample_cost_usd' => 0,
        ]);
    }

    private function member(Organization $org, string $role): User
    {
        $u = $this->makeUser();
        OrganizationMembership::create(['organization_id' => $org->id, 'user_id' => $u->id, 'role' => $role, 'is_active' => true, 'joined_at' => now()]);
        $u->forceFill(['active_organization_id' => $org->id])->save();

        return $u;
    }

    /** A new deal room opened through the API: [deal id, buyer, seller, buyerOrg, sellerOrg]. */
    private function openDeal(): array
    {
        [$sellerOrg, $seller] = $this->business('exporter');
        [$buyerOrg, $buyer] = $this->business('buyer');
        $lot = $this->lot($sellerOrg, $seller);
        Sanctum::actingAs($buyer);
        $quote = $this->acceptedQuote($lot, $buyer, $buyerOrg, 100000);
        $id = $this->postJson('/api/deal-rooms', ['quotation_id' => $quote->id, 'title' => 'Guji deal'])
            ->assertStatus(201)->json('data.id');

        return [$id, $buyer, $seller, $buyerOrg, $sellerOrg];
    }

    private function sign(int $id, User ...$users): void
    {
        foreach ($users as $u) {
            Sanctum::actingAs($u);
            $this->postJson("/api/deal-rooms/{$id}/sign-contract")->assertOk();
        }
    }

    private function step(int $dealId, string $key): DealMilestone
    {
        return DealMilestone::where('deal_room_id', $dealId)->where('milestone_key', $key)->firstOrFail();
    }

    private function slip(User $u, int $dealId, string $key, array $extra = [])
    {
        Sanctum::actingAs($u);

        return $this->post("/api/deal-rooms/{$dealId}/milestones/{$this->step($dealId, $key)->id}/advance", ['document' => $this->realPng()] + $extra, ['Accept' => 'application/json']);
    }

    private function confirm(int $dealId, string $key)
    {
        $admin = $this->makeUser('admin');
        FreshAuth::markUser($admin);
        Sanctum::actingAs($admin);

        return $this->postJson("/api/deal-rooms/{$dealId}/milestones/{$this->step($dealId, $key)->id}/confirm", ['bank_reference' => 'CBE-' . Str::random(6)]);
    }

    // ---- Deal room -------------------------------------------------------------

    public function test_deal_room_shows_both_sides_as_businesses_with_six_steps_and_amounts(): void
    {
        [$id, $buyer, $seller, $buyerOrg, $sellerOrg] = $this->openDeal();
        $this->sign($id, $buyer, $seller);

        Sanctum::actingAs($seller);
        $res = $this->getJson("/api/deal-rooms/{$id}")->assertOk()
            ->assertJsonPath('data.my_side', 'seller')
            ->assertJsonPath('data.buyer.name', $buyerOrg->name)
            ->assertJsonPath('data.seller.name', $sellerOrg->name)
            ->assertJsonPath('data.step_count', 6)
            ->assertJsonPath('data.step_number', 2)
            ->assertJsonPath('data.current_step', 'payment_escrow')
            ->assertJsonPath('data.deposit_percent', 30)
            ->assertJsonPath('data.milestones.1.amount_usd', 30000)
            ->assertJsonPath('data.milestones.4.amount_usd', 70000)
            ->assertJsonPath('data.milestones.3.key', 'shipped')
            ->assertJsonPath('data.milestones.2.locked', true)
            ->assertJsonPath('data.documents.contract_signed', true)
            ->assertJsonPath('data.pay_to.state', 'missing');

        // Nothing about the people: no names, emails, phones, signatures or ids of users.
        $json = $res->getContent();
        foreach ([$buyer->name, $buyer->email, $buyer->phone, $seller->name, $seller->email] as $personal) {
            $this->assertStringNotContainsString((string) $personal, $json);
        }
        $this->assertArrayNotHasKey('buyer_id', $res->json('data'));
        $this->assertNotEmpty($res->json('data.activity'));

        Sanctum::actingAs($buyer);
        $list = $this->getJson('/api/deal-rooms')->assertOk()->assertJsonPath('data.0.id', $id)->getContent();
        $this->assertStringNotContainsString((string) $buyer->email, $list);
        $this->assertStringNotContainsString('buyer_signature', $list);
    }

    public function test_steps_open_in_order_by_the_right_side_and_payment_waits_for_a_confirmed_payout_account(): void
    {
        [$id, $buyer, $seller, , $sellerOrg] = $this->openDeal();

        // The contract step comes first.
        $this->slip($buyer, $id, 'payment_escrow')->assertStatus(422)->assertJsonPath('code', 'STEP_LOCKED');
        $this->sign($id, $buyer, $seller);

        // Payment belongs to the buyer, and needs the seller's confirmed payout account.
        $this->slip($seller, $id, 'payment_escrow')->assertStatus(403)->assertJsonPath('code', 'OTHER_SIDE_STEP');
        $this->slip($buyer, $id, 'payment_escrow')->assertStatus(422)->assertJsonPath('code', 'PAYOUT_NOT_READY')->assertJsonPath('pay_to.state', 'missing');

        $account = $this->confirmedPayoutAccount($sellerOrg, '1000987654321');
        $this->slip($buyer, $id, 'payment_escrow', ['payment_method' => 'letter_of_credit'])->assertOk()->assertJsonPath('data.status', DealMilestone::STATUS_PROOF_SUBMITTED);
        $this->assertSame('letter_of_credit', DealRoom::find($id)->payment_method);

        Sanctum::actingAs($buyer);
        $this->getJson("/api/deal-rooms/{$id}")->assertJsonPath('data.pay_to.state', 'ready')
            ->assertJsonPath('data.pay_to.account_last4', '4321')
            ->assertJsonPath('data.pay_to.matches_verified', true)
            ->assertJsonMissingPath('data.pay_to.account_number');

        // Inspection is locked until the bank confirms the payment; then it is the seller's step.
        $this->slip($seller, $id, 'preshipment_inspection')->assertStatus(422)->assertJsonPath('code', 'STEP_LOCKED');
        $this->confirm($id, 'bill_of_lading_released')->assertStatus(422)->assertJsonPath('code', 'STEP_LOCKED');
        $this->confirm($id, 'payment_escrow')->assertOk();
        $this->slip($buyer, $id, 'preshipment_inspection')->assertStatus(403)->assertJsonPath('code', 'OTHER_SIDE_STEP');
        $this->slip($seller, $id, 'preshipment_inspection')->assertOk();
        $this->assertSame(PayoutAccount::STATUS_CONFIRMED, $account->fresh()->status);
    }

    // ---- Payout account --------------------------------------------------------

    public function test_only_the_owner_adds_the_payout_account_and_the_number_is_encrypted_and_masked(): void
    {
        [$org, $owner] = $this->business('exporter');
        $manager = $this->member($org, 'manager');

        FreshAuth::markUser($manager);
        Sanctum::actingAs($manager);
        $this->postJson('/api/payout-account', ['bank_name' => 'CBE', 'account_name' => $org->name, 'account_number' => '1000555566667777'])
            ->assertStatus(403)->assertJsonPath('code', 'OWNER_ONLY');
        $this->getJson('/api/payout-account')->assertOk()->assertJsonPath('data.can_change', false);

        // Stale session: fresh sign-in needed for bank details.
        Sanctum::actingAs($owner);
        $this->postJson('/api/payout-account', ['bank_name' => 'CBE', 'account_name' => $org->name, 'account_number' => '1000555566667777'])->assertStatus(403);

        FreshAuth::markUser($owner);
        $this->postJson('/api/payout-account', ['bank_name' => 'CBE', 'account_name' => $org->name, 'account_number' => 'abc'])
            ->assertStatus(422)->assertJsonPath('code', 'ACCOUNT_NUMBER_INVALID');
        $res = $this->postJson('/api/payout-account', ['bank_name' => 'CBE', 'account_name' => $org->name, 'account_number' => '1000 5555 6666 7777'])
            ->assertStatus(201)->assertJsonPath('data.account.account_last4', '7777')->assertJsonPath('data.account.status', 'pending')
            ->assertJsonPath('data.account.is_change', false);
        $this->assertStringNotContainsString('1000555566667777', $res->getContent());

        $raw = DB::table('payout_accounts')->where('id', $res->json('data.account.id'))->value('account_number');
        $this->assertStringNotContainsString('5555', $raw);
        $this->assertSame('1000555566667777', PayoutAccount::find($res->json('data.account.id'))->account_number);

        // Buyers and sellers of other businesses cannot read it.
        [, $other] = $this->business('buyer');
        Sanctum::actingAs($other);
        $this->getJson('/api/payout-account')->assertStatus(403);
    }

    public function test_a_change_of_bank_details_pauses_payments_for_48_hours_and_needs_a_call(): void
    {
        [$id, $buyer, $seller, , $sellerOrg] = $this->openDeal();
        $this->sign($id, $buyer, $seller);
        $this->confirmedPayoutAccount($sellerOrg);

        FreshAuth::markUser($seller);
        Sanctum::actingAs($seller);
        $newId = $this->postJson('/api/payout-account', ['bank_name' => 'Awash Bank', 'account_name' => $sellerOrg->name, 'account_number' => '01320099887766'])
            ->assertStatus(201)->assertJsonPath('data.account.is_change', true)->json('data.account.id');
        Notification::assertSentTo($seller, \App\Notifications\TradeUpdate::class, fn ($n) => $n->event === 'payout_changed');

        // The buyer is told to stop; payment proof is refused.
        Sanctum::actingAs($buyer);
        $this->getJson("/api/deal-rooms/{$id}")->assertJsonPath('data.pay_to.state', 'paused')->assertJsonPath('data.pay_to.account_last4', '7766');
        $this->slip($buyer, $id, 'payment_escrow')->assertStatus(422)->assertJsonPath('code', 'PAYOUT_NOT_READY')->assertJsonPath('pay_to.state', 'paused');

        // Reviewer: name check and a call to the number on file.
        $reviewer = $this->makeUser('admin');
        $account = PayoutAccount::find($newId);
        $this->actingAsStaff($reviewer, fresh: true)->post(route('admin.verification-review.payout.confirm', $newId), ['checks' => ['name_matches' => 1]])->assertSessionHas('error');
        $this->assertSame(PayoutAccount::STATUS_PENDING, $account->fresh()->status);
        $this->actingAsStaff($reviewer, fresh: true)->post(route('admin.verification-review.payout.confirm', $newId), ['checks' => ['name_matches' => 1, 'called_on_file' => 1]])->assertSessionHas('success');
        $this->assertSame(PayoutAccount::STATUS_CONFIRMED, $account->fresh()->status);
        $this->assertNotNull($account->fresh()->called_on_file_at);

        // Still inside the 48 hours.
        $this->slip($buyer, $id, 'payment_escrow')->assertStatus(422)->assertJsonPath('pay_to.state', 'paused');
        $this->travel(49)->hours();
        $this->slip($buyer, $id, 'payment_escrow')->assertOk();
    }

    public function test_a_reviewer_cannot_confirm_their_own_business_payout_account_and_rejects_with_a_reason(): void
    {
        [$org, $owner] = $this->business('exporter');
        FreshAuth::markUser($owner);
        Sanctum::actingAs($owner);
        $id = $this->postJson('/api/payout-account', ['bank_name' => 'CBE', 'account_name' => 'Someone Else PLC', 'account_number' => '1000111122223333'])->json('data.account.id');

        $insider = $this->member($org, 'member');
        $insider->forceFill(['role' => 'admin'])->save();
        $this->actingAsStaff($insider, fresh: true)->post(route('admin.verification-review.payout.confirm', $id), ['checks' => ['name_matches' => 1]])->assertSessionHas('error');

        $reviewer = $this->makeUser('admin');
        $this->actingAsStaff($reviewer)->get(route('admin.verification-review.index', ['tab' => 'payouts']))->assertOk()->assertSee('Someone Else PLC')->assertSee('3333')->assertDontSee('1000111122223333');
        $this->actingAsStaff($reviewer, fresh: true)->post(route('admin.verification-review.payout.reject', $id), ['reason' => ''])->assertSessionHas('error');
        $this->actingAsStaff($reviewer, fresh: true)->post(route('admin.verification-review.payout.reject', $id), ['reason' => 'Name does not match the licence'])->assertSessionHas('success');
        $this->assertSame(PayoutAccount::STATUS_REJECTED, PayoutAccount::find($id)->status);

        Sanctum::actingAs($owner);
        $this->getJson('/api/payout-account')->assertJsonPath('data.account', null);
    }

    // ---- Chat ------------------------------------------------------------------

    public function test_message_seller_chat_is_between_businesses_and_flagged_messages_are_delivered_with_a_warning(): void
    {
        [$sellerOrg, $seller] = $this->business('exporter');
        [$buyerOrg, $buyer] = $this->business('buyer');
        [$unverifiedOrg] = $this->business('exporter', false);
        $lot = $this->lot($sellerOrg, $seller);

        Sanctum::actingAs($buyer);
        $this->postJson('/api/trade-chats', ['seller_organization_id' => $unverifiedOrg->id])->assertStatus(422)->assertJsonPath('code', 'SELLER_NOT_VERIFIED');
        $cid = $this->postJson('/api/trade-chats', ['seller_organization_id' => $sellerOrg->id, 'listing_id' => $lot->id])
            ->assertStatus(201)->assertJsonPath('data.other.name', $sellerOrg->name)->assertJsonPath('data.lot.lot_code', $lot->fresh()->lot_code)->json('data.id');
        // Same pair: same thread.
        $this->postJson('/api/trade-chats', ['seller_organization_id' => $sellerOrg->id])->assertOk()->assertJsonPath('data.id', $cid);

        $this->postJson("/api/trade-chats/{$cid}/messages", ['body' => 'Is the Guji G1 still available for March?'])->assertStatus(201)->assertJsonPath('data.flagged', false);
        $this->postJson("/api/trade-chats/{$cid}/messages", ['body' => ''])->assertStatus(422);

        Sanctum::actingAs($seller);
        $flagged = $this->postJson("/api/trade-chats/{$cid}/messages", ['body' => 'Yes. Faster on WhatsApp: +251 911 234 567'])
            ->assertStatus(201)->assertJsonPath('data.flagged', true)->assertJsonPath('data.show_warning', false)->json('data.id');
        $this->assertEqualsCanonicalizing(['messenger', 'phone'], TradeMessage::find($flagged)->flag_kinds);

        Sanctum::actingAs($buyer);
        $this->getJson('/api/trade-chats')->assertOk()->assertJsonPath('data.0.id', $cid)->assertJsonPath('data.0.unread', 1);
        $thread = $this->getJson("/api/trade-chats/{$cid}")->assertOk()
            ->assertJsonPath('data.messages.1.show_warning', true)
            ->assertJsonPath('data.messages.0.mine', true)
            ->assertJsonPath('data.conversation.other.name', $sellerOrg->name);
        foreach ([$seller->name, $seller->email, $seller->phone, $buyer->email] as $personal) {
            $this->assertStringNotContainsString((string) $personal, $thread->getContent());
        }
        $this->getJson('/api/trade-chats')->assertJsonPath('data.0.unread', 0);

        // Outsiders cannot read or write.
        [, $stranger] = $this->business('buyer');
        Sanctum::actingAs($stranger);
        $this->getJson("/api/trade-chats/{$cid}")->assertStatus(403);
        $this->postJson("/api/trade-chats/{$cid}/messages", ['body' => 'hi'])->assertStatus(403);

        // Admins see it in the flagged list.
        $this->actingAsStaff($this->makeUser('admin'))->get(route('admin.verification-review.index', ['tab' => 'flagged']))
            ->assertOk()->assertSee($sellerOrg->name)->assertSee(__('trade.admin.kind_messenger'));
    }

    public function test_deal_room_chat_is_one_thread_for_both_sides(): void
    {
        [$id, $buyer, $seller] = $this->openDeal();
        Sanctum::actingAs($buyer);
        $cid = $this->postJson("/api/deal-rooms/{$id}/chat")->assertOk()->assertJsonPath('data.my_side', 'buyer')->assertJsonPath('data.deal_room.id', $id)->json('data.id');
        Sanctum::actingAs($seller);
        $this->postJson("/api/deal-rooms/{$id}/chat")->assertOk()->assertJsonPath('data.id', $cid)->assertJsonPath('data.my_side', 'seller');
        $this->getJson("/api/deal-rooms/{$id}")->assertJsonPath('data.conversation_id', $cid);
        Sanctum::actingAs($this->makeUser());
        $this->postJson("/api/deal-rooms/{$id}/chat")->assertStatus(403);
    }

    // ---- Reports and blocks ----------------------------------------------------

    public function test_reporting_a_received_message_finds_the_business_and_never_pauses_anything_automatically(): void
    {
        [$id, $buyer, $seller, $buyerOrg, $sellerOrg] = $this->openDeal();
        Sanctum::actingAs($buyer);
        $cid = $this->postJson("/api/deal-rooms/{$id}/chat")->json('data.id');
        Sanctum::actingAs($seller);
        $mid = $this->postJson("/api/trade-chats/{$cid}/messages", ['body' => 'Pay the deposit to my personal account, t.me/quickpay'])->json('data.id');

        // The sender cannot report their own message; a stranger cannot report it.
        $this->postJson('/api/reports', ['reason_code' => 'pay_outside', 'reported_message_id' => $mid])->assertStatus(403);
        [, $stranger] = $this->business('buyer');
        Sanctum::actingAs($stranger);
        $this->postJson('/api/reports', ['reason_code' => 'pay_outside', 'reported_message_id' => $mid])->assertStatus(403);

        Sanctum::actingAs($buyer);
        $this->postJson('/api/reports', ['reason_code' => 'other', 'reported_message_id' => $mid])->assertStatus(422); // details needed for "Something else"
        $this->postJson('/api/reports', ['reason_code' => 'nonsense', 'reported_message_id' => $mid])->assertStatus(422);
        $res = $this->post('/api/reports', [
            'reason_code' => 'pay_outside',
            'details' => 'Asked for a personal account',
            'reported_message_id' => $mid,
            'reported_organization_id' => $buyerOrg->id, // ignored: the server works out the business
            'evidence' => $this->realPng('screenshot.png'),
        ], ['Accept' => 'application/json'])->assertStatus(201)->assertJsonPath('data.blocked', false);

        $report = TrustReport::findOrFail($res->json('data.id'));
        $this->assertSame($sellerOrg->id, (int) $report->reported_organization_id);
        $this->assertSame($buyerOrg->id, (int) $report->reporter_organization_id);
        $this->assertSame($id, (int) $report->reported_deal_room_id);
        Storage::disk('local')->assertExists($report->evidence_path);
        $this->assertNull(DealRoom::find($id)->paused_at);
        $this->assertSame('active', $sellerOrg->fresh()->status);

        // The reported side gets no notice naming the reporter.
        Notification::assertNotSentTo($seller, \App\Notifications\TradeUpdate::class, fn ($n) => str_contains(json_encode($n->params), $buyerOrg->name) && $n->event !== 'message_received');

        // Admin: Reports tab, report page with the chat context, decision needs notes.
        $admin = $this->makeUser('admin');
        $this->actingAsStaff($admin)->get(route('admin.verification-review.index', ['tab' => 'reports']))->assertOk()->assertSee($sellerOrg->name)->assertSee(__('trade.report_reasons.pay_outside'));
        $this->actingAsStaff($admin)->get(route('admin.verification-review.report', $report->id))->assertOk()->assertSee('t.me/quickpay')->assertSee(__('trade.admin.suspend_title'));
        $this->actingAsStaff($admin)->post(route('admin.verification-review.report.decide', $report->id), ['decision' => 'resolved', 'notes' => ''])->assertSessionHas('error');
        $this->actingAsStaff($admin)->post(route('admin.verification-review.report.decide', $report->id), ['decision' => 'resolved', 'notes' => 'Warned the seller, suspended.'])->assertSessionHas('success');
        $this->assertSame(TrustReport::STATUS_RESOLVED, $report->fresh()->status);

        // Reporters and reported businesses are not reviewers.
        $this->actingAs($buyer)->get(route('admin.verification-review.report', $report->id))->assertStatus(403);
    }

    public function test_the_public_seller_page_reports_by_fira_id_and_a_report_needs_a_business_or_lot(): void
    {
        [$sellerOrg] = $this->business('exporter');
        [, $buyer] = $this->business('buyer');
        Sanctum::actingAs($buyer);
        $this->postJson('/api/reports', ['reason_code' => 'impersonation'])->assertStatus(422)->assertJsonPath('code', 'TARGET_REQUIRED');
        $this->postJson('/api/reports', ['reason_code' => 'impersonation', 'reported_fira_id' => 'FL-NOPE-0000'])->assertStatus(404);
        $id = $this->postJson('/api/reports', ['reason_code' => 'impersonation', 'reported_fira_id' => strtolower($sellerOrg->fira_id)])->assertStatus(201)->json('data.id');
        $this->assertSame($sellerOrg->id, (int) TrustReport::find($id)->reported_organization_id);
    }

    public function test_suspending_a_reported_business_pauses_its_open_deal_rooms_until_lifted(): void
    {
        [$id, $buyer, $seller, , $sellerOrg] = $this->openDeal();
        $this->sign($id, $buyer, $seller);
        $this->confirmedPayoutAccount($sellerOrg);

        $admin = $this->makeUser('admin');
        $this->actingAsStaff($admin, fresh: true)->post(route('admin.verification-review.suspend', $sellerOrg), ['reason' => 'Report #1'])->assertSessionHas('success');
        $deal = DealRoom::find($id);
        $this->assertNotNull($deal->paused_at);
        $this->assertSame('business_suspended', $deal->pause_reason);

        $this->slip($buyer, $id, 'payment_escrow')->assertStatus(423)->assertJsonPath('code', 'DEAL_PAUSED');
        $this->confirm($id, 'payment_escrow')->assertStatus(423);
        Sanctum::actingAs($buyer);
        $this->getJson("/api/deal-rooms/{$id}")->assertJsonPath('data.paused', true)->assertJsonPath('data.seller.verification_tier', 0);

        $this->actingAsStaff($admin, fresh: true)->post(route('admin.verification-review.unsuspend', $sellerOrg), ['reason' => 'Checked'])->assertSessionHas('success');
        $this->assertNull(DealRoom::find($id)->paused_at);
        $this->slip($buyer, $id, 'payment_escrow')->assertOk();
    }

    public function test_also_block_stops_messages_samples_quotes_and_new_deal_rooms_between_the_two_businesses(): void
    {
        [$sellerOrg, $seller] = $this->business('exporter');
        [$buyerOrg, $buyer] = $this->business('buyer');
        $lot = $this->lot($sellerOrg, $seller);

        Sanctum::actingAs($buyer);
        $cid = $this->postJson('/api/trade-chats', ['seller_organization_id' => $sellerOrg->id])->json('data.id');
        $this->postJson('/api/reports', ['reason_code' => 'fake_photos', 'reported_listing_id' => $lot->id, 'block' => true])
            ->assertStatus(201)->assertJsonPath('data.blocked', true);
        $this->assertTrue(BusinessBlock::between($buyerOrg->id, $sellerOrg->id));

        $this->getJson("/api/trade-chats/{$cid}")->assertJsonPath('data.conversation.blocked_by_me', true);
        $this->postJson("/api/trade-chats/{$cid}/messages", ['body' => 'hello'])->assertStatus(403)->assertJsonPath('code', 'BLOCKED');
        Sanctum::actingAs($seller);
        $this->postJson("/api/trade-chats/{$cid}/messages", ['body' => 'hello'])->assertStatus(403)->assertJsonPath('code', 'BLOCKED');

        Sanctum::actingAs($buyer);
        $this->postJson('/api/marketplace/samples', [
            'listing_id' => $lot->id, 'quantity_grams' => 250, 'delivery_company' => 'Roasters', 'delivery_address' => 'Street 1',
            'delivery_country' => 'Germany', 'delivery_phone' => '+49 40 1234567', 'courier_carrier' => 'dhl', 'courier_account' => '963123456',
        ])->assertStatus(403)->assertJsonPath('code', 'BLOCKED');
        $quote = $this->acceptedQuote($lot, $buyer, $buyerOrg, 1000);
        $this->postJson('/api/deal-rooms', ['quotation_id' => $quote->id, 'title' => 'Deal'])->assertStatus(403)->assertJsonPath('code', 'BLOCKED');

        $rfq = MarketplaceRfq::create(['buyer_id' => $buyer->id, 'rfq_number' => 'RFQ-B' . Str::upper(Str::random(6)), 'title' => 'Guji', 'volume_bags' => 10, 'destination_country' => 'Germany', 'destination_port' => 'Hamburg']);
        $rfq->forceFill(['organization_id' => $buyerOrg->id, 'status' => 'open'])->save();
        try {
            app(TradeService::class)->submitQuote($rfq, $sellerOrg, $seller, ['listing_id' => $lot->id, 'volume_bags' => 10, 'price_usd_per_lb' => 5]);
            $this->fail('A blocked seller quoted.');
        } catch (VerificationException $e) {
            $this->assertSame('BLOCKED', $e->errorCode);
        }

        // Members who are not owner or manager cannot unblock; the owner can.
        $member = $this->member($buyerOrg, 'member');
        Sanctum::actingAs($member);
        $this->deleteJson("/api/business-blocks/{$sellerOrg->id}")->assertStatus(403);
        Sanctum::actingAs($buyer);
        $this->getJson('/api/business-blocks')->assertOk()->assertJsonPath('data.0.business.name', $sellerOrg->name);
        $this->deleteJson("/api/business-blocks/{$sellerOrg->id}")->assertOk();
        $this->postJson("/api/trade-chats/{$cid}/messages", ['body' => 'hello again'])->assertStatus(201);
    }
}

<?php

namespace App\Console\Commands\Qa;

use App\Models\CoffeeListing;
use App\Models\Event;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\OrganizationVerification;
use App\Models\PayoutAccount;
use App\Models\Tour;
use App\Models\User;
use App\Models\VerificationDocument;
use App\Services\Verification\TierService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use PragmaRX\Google2FA\Google2FA;
use Spatie\Permission\Models\Role;

/**
 * Creates one QA run: an account per role, three businesses (verified exporter with owner, manager
 * and staff; verified buyer; unverified exporter) and content (a lot, an event, a tour). Every
 * account is <run>.<name>@qa.firalink.test. Prints the run as JSON once (passwords and staff
 * authenticator secrets are not stored anywhere else). Remove it with qa:cleanup.
 */
class QaSeed extends Command
{
    use GuardsQa;

    protected $signature = 'qa:seed {--password= : Password for every QA account (random when empty)} {--run= : Run name (random when empty)}';

    protected $description = 'Create QA accounts, businesses and content for testing (never in production)';

    public function handle(): int
    {
        if (!$this->qaAllowed()) {
            return self::FAILURE;
        }
        $run = Str::lower($this->option('run') ?: 'qa' . Str::random(6));
        if (!preg_match('/^[a-z0-9]{3,20}$/', $run)) {
            $this->error('Run name: 3 to 20 letters or digits.');

            return self::FAILURE;
        }
        $password = $this->option('password') ?: Str::random(10) . 'aA1!' . Str::random(4);
        $domain = $this->qaDomain();
        foreach (['reviewer', 'finance'] as $r) {
            Role::findOrCreate($r, 'web');
        }

        $out = DB::transaction(function () use ($run, $password, $domain) {
            $phoneBase = 2519 . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $n = 0;
            $accounts = [];
            $user = function (string $key, string $role, array $spatie = []) use (&$accounts, &$n, $run, $password, $domain, $phoneBase) {
                $n++;
                $u = new User();
                $u->forceFill([
                    'name' => 'QA ' . Str::headline($key),
                    'email' => "{$run}.{$key}@{$domain}",
                    'password' => Hash::make($password),
                    'role' => $role,
                    'phone' => $phoneBase . str_pad((string) $n, 4, '0', STR_PAD_LEFT),
                    'phone_verified_at' => now(),
                    'email_verified_at' => now(),
                ])->save();
                $u->assignRole($role);
                foreach ($spatie as $r) {
                    $u->assignRole($r);
                }
                $accounts[$key] = ['id' => $u->id, 'email' => $u->email, 'phone' => '+' . $u->phone, 'role' => $role, 'extra_roles' => $spatie];

                return $u;
            };

            $student = $user('student', 'student');
            $instructor = $user('instructor', 'instructor');
            $instructor->forceFill(['is_approved' => true])->save();
            $pending = $user('instructor_pending', 'instructor');
            $pending->forceFill(['is_approved' => false])->save();
            $admin = $user('admin', 'admin');
            $reviewer = $user('reviewer', 'student', ['reviewer']);
            $finance = $user('finance', 'student', ['finance']);
            $exOwner = $user('exporter_owner', 'student');
            $exManager = $user('exporter_manager', 'student');
            $exStaff = $user('exporter_staff', 'student');
            $buyerOwner = $user('buyer_owner', 'student');
            $newOwner = $user('unverified_owner', 'student');

            // Staff sign in to admin with an authenticator app; QA gets the secret to make codes.
            $secrets = [];
            foreach (['admin' => $admin, 'reviewer' => $reviewer] as $key => $staff) {
                $secret = app(Google2FA::class)->generateSecretKey(32);
                $staff->forceFill([
                    'two_factor_secret' => Fortify::currentEncrypter()->encrypt($secret),
                    'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode([Str::random(10) . '-' . Str::random(10)])),
                    'two_factor_confirmed_at' => now(),
                ])->save();
                $secrets[$key] = $secret;
            }

            $business = function (string $key, string $name, string $role, User $owner, array $members, bool $approve) use ($run) {
                $org = Organization::create(['name' => "QA {$name}", 'slug' => "{$run}-{$key}", 'status' => 'active']);
                $org->forceFill(['business_role' => $role, 'created_by' => $owner->id, 'entity_type' => 'private_limited_company', 'country' => 'Ethiopia'])->save();
                foreach (['owner' => [$owner], ...$members] as $mRole => $list) {
                    foreach ($list as $m) {
                        OrganizationMembership::create(['organization_id' => $org->id, 'user_id' => $m->id, 'role' => $mRole, 'is_active' => true, 'joined_at' => now()]);
                        $m->forceFill(['active_organization_id' => $org->id])->save();
                    }
                }
                if ($approve) {
                    $c = OrganizationVerification::firstOrCreate(['organization_id' => $org->id, 'target_role' => $role]);
                    foreach (TierService::requiredDocuments($org, 1) as $type) {
                        $cfg = TierService::documentTypeConfig($type);
                        $d = VerificationDocument::create([
                            'organization_verification_id' => $c->id, 'organization_id' => $org->id, 'document_type' => $type,
                            'file_path' => "qa/{$run}/{$type}.pdf", 'file_disk' => 'verification', 'original_filename' => "{$type}.pdf",
                            'file_size_kb' => 40, 'mime_type' => 'application/pdf', 'is_valid' => true,
                            'expires_at' => ($cfg['expires'] ?? false) ? now()->addYear() : null, 'submitted_at' => now(),
                        ]);
                        $d->forceFill(['tier' => (int) $cfg['tier'], 'status' => VerificationDocument::STATUS_APPROVED, 'reviewed_at' => now()])->save();
                    }
                    $org = app(TierService::class)->recompute($org);
                }

                return $org->fresh();
            };

            $exporter = $business('exporter', 'Exporter PLC', 'exporter', $exOwner, ['manager' => [$exManager], 'staff' => [$exStaff]], true);
            $buyer = $business('buyer', 'Roasters GmbH', 'buyer', $buyerOwner, [], true);
            $unverified = $business('unverified', 'New Exporter', 'exporter', $newOwner, [], false);

            $payout = new PayoutAccount();
            $payout->forceFill([
                'organization_id' => $exporter->id, 'bank_name' => 'Commercial Bank of Ethiopia', 'account_name' => $exporter->name,
                'account_number' => '1000' . random_int(100000000, 999999999), 'account_last4' => '0000', 'status' => PayoutAccount::STATUS_CONFIRMED,
                'active_from' => now()->subDay(), 'reviewed_at' => now(),
            ])->save();
            $payout->forceFill(['account_last4' => substr($payout->account_number, -4)])->save();

            $lot = CoffeeListing::create([
                'seller_id' => $exOwner->id, 'organization_id' => $exporter->id, 'title' => 'QA Guji Natural G1', 'origin_region' => 'Guji',
                'processing_method' => 'natural', 'grade' => 'grade_1', 'total_quantity_bags' => 100, 'available_quantity_bags' => 100,
                'indicative_price_usd_per_lb' => 5.2, 'status' => 'active', 'sample_available' => true, 'sample_cost_usd' => 0,
            ]);
            $hiddenLot = CoffeeListing::create([
                'seller_id' => $newOwner->id, 'organization_id' => $unverified->id, 'title' => 'QA Hidden lot', 'origin_region' => 'Sidama',
                'processing_method' => 'washed', 'grade' => 'grade_2', 'total_quantity_bags' => 10, 'available_quantity_bags' => 10,
                'indicative_price_usd_per_lb' => 4, 'status' => 'active',
            ]);

            $event = new Event();
            $event->forceFill([
                'title' => 'QA Cupping Day', 'slug' => "{$run}-event", 'start_date' => now()->addDays(10)->toDateString(), 'end_date' => now()->addDays(10)->toDateString(),
                'start_time' => '09:00', 'location' => 'Addis Ababa', 'total_seats' => 20, 'price' => 500, 'member_price' => 300, 'service_fee' => 25,
                'is_published' => true, 'category' => 'cupping', 'agenda' => [['time' => '09:00', 'title' => 'Welcome']],
                'refund_policy' => 'Full refund up to 7 days before.', 'organizer_organization_id' => $exporter->id,
            ])->save();

            $tour = new Tour();
            $tour->forceFill([
                'title' => 'QA Guji harvest tour', 'slug' => "{$run}-tour", 'destination' => 'Guji', 'duration_days' => 3, 'total_seats' => 10,
                'price' => 10000, 'currency' => 'ETB', 'is_published' => true, 'deposit_percent' => 30, 'balance_due_days' => 14,
                'host_organization_id' => $exporter->id, 'available_schedules' => [now()->addDays(40)->toDateString(), now()->addDays(5)->toDateString()],
                'safety_notes' => 'Emergency contact at the meeting point.', 'exclusions' => ['Flights'],
            ])->save();

            return [
                'run' => $run,
                'password' => $password,
                'accounts' => $accounts,
                'staff_totp_secrets' => $secrets,
                'businesses' => [
                    'exporter' => ['id' => $exporter->id, 'name' => $exporter->name, 'fira_id' => $exporter->fira_id, 'tier' => (int) $exporter->verification_tier],
                    'buyer' => ['id' => $buyer->id, 'name' => $buyer->name, 'fira_id' => $buyer->fira_id],
                    'unverified' => ['id' => $unverified->id, 'name' => $unverified->name],
                ],
                'content' => ['lot' => $lot->id, 'hidden_lot' => $hiddenLot->id, 'event' => $event->id, 'tour' => $tour->id, 'tour_date' => now()->addDays(40)->toDateString()],
            ];
        });

        $this->line('QA_JSON:' . json_encode($out));
        $this->info("QA run {$run} created. Remove it with: php artisan qa:cleanup --run={$run}");

        return self::SUCCESS;
    }
}

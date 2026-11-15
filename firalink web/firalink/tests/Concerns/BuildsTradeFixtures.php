<?php

namespace Tests\Concerns;

use App\Models\DealMilestone;
use App\Models\DealRoom;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\PayoutAccount;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Users, organizations, deal rooms and upload files for security tests.
 * Use together with DatabaseTransactions so nothing is left in the test database.
 */
trait BuildsTradeFixtures
{
    protected function makeUser(string $role = 'student'): User
    {
        return User::factory()->create([
            'email' => 'u_' . Str::lower(Str::random(12)) . '@example.test',
            'role' => $role,
        ]);
    }

    protected function makeOrganization(?User $owner = null): Organization
    {
        $suffix = Str::lower(Str::random(10));
        $org = Organization::create([
            'name' => 'Test Exporter ' . $suffix,
            'slug' => 'test-exporter-' . $suffix,
            'status' => 'active',
        ]);

        if ($owner) {
            OrganizationMembership::create([
                'organization_id' => $org->id,
                'user_id' => $owner->id,
                'role' => 'owner',
                'is_active' => true,
                'joined_at' => now(),
            ]);
        }

        return $org;
    }

    /**
     * @return array{0: DealRoom, 1: User, 2: User} deal room, buyer, seller
     */
    protected function makeDealRoom(): array
    {
        $buyer = $this->makeUser();
        $seller = $this->makeUser();
        $org = $this->makeOrganization($seller);

        $deal = DealRoom::create([
            'deal_reference' => 'DR-T' . Str::upper(Str::random(8)),
            'organization_id' => $org->id,
            'buyer_id' => $buyer->id,
            'title' => 'Test deal',
            'target_value_usd' => 50000,
            'status' => DealRoom::STATUS_UNDER_CONTRACT,
        ]);

        // Under contract: the contract step is done, so "Payment secured" is the current step.
        // The five keys are the layout of deal rooms opened before slice 7.
        foreach (['contract_signed', 'payment_escrow', 'preshipment_inspection', 'bill_of_lading_released', 'final_settlement'] as $key) {
            DealMilestone::create([
                'deal_room_id' => $deal->id,
                'title' => Str::headline($key),
                'milestone_key' => $key,
                'status' => $key === 'contract_signed' ? DealMilestone::STATUS_ACHIEVED : DealMilestone::STATUS_PENDING,
            ]);
        }
        $this->confirmedPayoutAccount($org);

        return [$deal, $buyer, $seller];
    }

    /** A payout account a reviewer already confirmed, outside any change pause. */
    protected function confirmedPayoutAccount(Organization $org, string $number = '1000123456789'): PayoutAccount
    {
        $a = new PayoutAccount();
        $a->forceFill([
            'organization_id' => $org->id,
            'bank_name' => 'Commercial Bank of Ethiopia',
            'account_name' => $org->name,
            'account_number' => $number,
            'account_last4' => substr($number, -4),
            'status' => PayoutAccount::STATUS_CONFIRMED,
            'active_from' => now()->subDay(),
            'reviewed_at' => now()->subDay(),
        ])->save();

        return $a;
    }

    protected function milestone(DealRoom $deal, string $key): DealMilestone
    {
        return $deal->milestones()->where('milestone_key', $key)->firstOrFail();
    }

    /** A real PNG photo: 400x400 of noise, well above the minimum size. */
    protected function realPng(string $name = 'slip.png'): UploadedFile
    {
        $image = imagecreatetruecolor(400, 400);
        for ($x = 0; $x < 400; $x += 2) {
            for ($y = 0; $y < 400; $y += 2) {
                imagefilledrectangle($image, $x, $y, $x + 1, $y + 1, imagecolorallocate($image, random_int(0, 255), random_int(0, 255), random_int(0, 255)));
            }
        }
        $path = tempnam(sys_get_temp_dir(), 'png');
        imagepng($image, $path);
        imagedestroy($image);

        return new UploadedFile($path, $name, 'image/png', null, true);
    }

    /** A structurally valid PDF with real text content. */
    protected function realPdf(string $name = 'contract.pdf'): UploadedFile
    {
        $text = '';
        for ($i = 0; $i < 60; $i++) {
            $text .= "(Line {$i}: " . Str::random(40) . ") Tj T*\n";
        }
        $stream = "BT /F1 10 Tf 40 800 Td 12 TL\n{$text}ET";
        $pdf = "%PDF-1.4\n"
            . "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n"
            . "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n"
            . "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >> endobj\n"
            . '4 0 obj << /Length ' . strlen($stream) . " >> stream\n{$stream}\nendstream endobj\n"
            . "5 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj\n"
            . "trailer << /Root 1 0 R >>\n%%EOF\n";

        $path = tempnam(sys_get_temp_dir(), 'pdf');
        file_put_contents($path, $pdf);

        return new UploadedFile($path, $name, 'application/pdf', null, true);
    }

    /** Dummy bytes with a document name, like the old client fallback sent. */
    protected function dummyBytes(string $name = 'slip.pdf', int $bytes = 20480, string $prefix = ''): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'dmy');
        file_put_contents($path, $prefix . str_repeat("\0", $bytes));

        return new UploadedFile($path, $name, 'application/pdf', null, true);
    }

    protected function emptyFile(string $name = 'slip.pdf'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'emp');

        return new UploadedFile($path, $name, 'application/pdf', null, true);
    }

    protected function tinyPng(string $name = 'photo.png'): UploadedFile
    {
        $image = imagecreatetruecolor(1, 1);
        $path = tempnam(sys_get_temp_dir(), 'tny');
        imagepng($image, $path);
        imagedestroy($image);

        return new UploadedFile($path, $name, 'image/png', null, true);
    }
}

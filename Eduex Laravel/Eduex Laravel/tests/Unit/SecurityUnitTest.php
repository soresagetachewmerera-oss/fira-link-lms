<?php

namespace Tests\Unit;

use App\Services\CertificateVerificationService;
use App\Services\SmsEthiopiaService;
use App\Services\ThemeService;
use Tests\TestCase;

class SecurityUnitTest extends TestCase
{
    public function test_theme_slug_traversal_is_rejected(): void
    {
        $themeService = new ThemeService();

        $this->expectException(\InvalidArgumentException::class);
        $themeService->deleteTheme('../../../critical_directory');
    }

    public function test_sms_ethiopia_phone_normalization_blocks_malicious_inputs(): void
    {
        $smsService = new SmsEthiopiaService();

        $this->assertNull($smsService->normalizePhoneNumber("0911223344\r\nBcc: victim@example.com"));
        $this->assertNull($smsService->normalizePhoneNumber("0911223344; rm -rf /"));
        $this->assertEquals('251911223344', $smsService->normalizePhoneNumber('0911223344'));
        $this->assertEquals('251711223344', $smsService->normalizePhoneNumber('0711223344'));
    }
}

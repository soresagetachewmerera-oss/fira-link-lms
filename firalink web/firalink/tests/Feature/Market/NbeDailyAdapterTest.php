<?php

namespace Tests\Feature\Market;

use App\Models\MarketSeries;
use App\Models\MarketSource;
use App\Services\Market\Adapters\NbeDailyAdapter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class NbeDailyAdapterTest extends TestCase
{
    use DatabaseTransactions;

    protected NbeDailyAdapter $adapter;
    protected MarketSource $source;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter = new NbeDailyAdapter();

        $this->source = MarketSource::firstOrCreate(
            ['slug' => 'nbe_daily'],
            [
                'name' => 'National Bank of Ethiopia',
                'kind' => 'fx',
                'url' => 'https://nbe.gov.et/exchange-rate/api',
                'adapter' => 'nbe',
                'refresh_minutes' => 1440,
                'max_age_minutes' => 2880,
                'enabled' => true,
                'configuration' => ['timeout_seconds' => 5],
            ]
        );
    }

    public function test_fetches_and_extracts_nbe_usd_rate(): void
    {
        Http::fake([
            'https://nbe.gov.et/*' => Http::response([
                'date' => '2026-11-27',
                'rates' => [
                    [
                        'currency' => 'USD',
                        'buying_rate' => 124.5000,
                        'selling_rate' => 127.0000,
                        'central_rate' => 125.7500,
                    ],
                    [
                        'currency' => 'EUR',
                        'buying_rate' => 135.2000,
                        'selling_rate' => 138.0000,
                        'central_rate' => 136.6000,
                    ],
                ],
            ], 200),
        ]);

        $quotes = $this->adapter->fetch($this->source);

        $this->assertNotEmpty($quotes);
        $quote = $quotes[0];

        $this->assertEquals('NBE_USD_ETB', $quote['symbol']);
        $this->assertEquals('125.75', $quote['value']);
        $this->assertEquals('ETB', $quote['currency']);
        $this->assertEquals('USD', $quote['unit']);
        $this->assertStringStartsWith('2026-11-27', $quote['source_time']);
        $this->assertArrayHasKey('response_sha256', $quote['raw_ref']);
    }

    public function test_handles_direct_currency_object_format(): void
    {
        Http::fake([
            'https://nbe.gov.et/*' => Http::response([
                'date' => '2026-11-27',
                'USD' => [
                    'rate' => 126.10,
                    'buying_rate' => 124.80,
                    'selling_rate' => 127.40,
                ],
            ], 200),
        ]);

        $quotes = $this->adapter->fetch($this->source);

        $this->assertNotEmpty($quotes);
        $this->assertEquals('126.1', $quotes[0]['value']);
    }

    public function test_throws_when_response_is_invalid(): void
    {
        Http::fake([
            'https://nbe.gov.et/*' => Http::response(['message' => 'No rates available today'], 200),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('did not contain valid USD/ETB rate');

        $this->adapter->fetch($this->source);
    }

    public function test_throws_when_http_fails(): void
    {
        Http::fake([
            'https://nbe.gov.et/*' => Http::response(null, 503),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('NBE endpoint returned HTTP 503');

        $this->adapter->fetch($this->source);
    }
}

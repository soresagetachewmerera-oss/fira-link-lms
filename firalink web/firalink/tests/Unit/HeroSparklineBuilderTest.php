<?php

namespace Tests\Unit;

use App\Services\Market\HeroSparklineBuilder;
use PHPUnit\Framework\TestCase;

class HeroSparklineBuilderTest extends TestCase
{
    public function test_build_path_returns_null_when_fewer_than_two_points(): void
    {
        $this->assertNull(HeroSparklineBuilder::buildPath([]));
        $this->assertNull(HeroSparklineBuilder::buildPath([249.09]));
        $this->assertNull(HeroSparklineBuilder::buildPath(['not-a-number']));
    }

    public function test_build_path_normalizes_last_thirty_closes_and_handles_flat_series(): void
    {
        $series = range(100, 139); // 40 points -> takes last 30 (110..139)
        $result = HeroSparklineBuilder::buildPath($series, 200.0, 52.0, 4.0);

        $this->assertNotNull($result);
        $this->assertSame(30, $result['count']);
        $this->assertSame(110.0, $result['min']);
        $this->assertSame(139.0, $result['max']);
        $this->assertStringStartsWith('M4,48 ', $result['line']);
        $this->assertStringEndsWith(' L196,4', $result['line']);
        $this->assertStringEndsWith(' L196,52 L4,52 Z', $result['area']);

        $flat = HeroSparklineBuilder::buildPath([250.0, 250.0, 250.0], 200.0, 52.0, 4.0);
        $this->assertSame('M4,26 L100,26 L196,26', $flat['line']);
    }

    public function test_trend_word_from_label_and_series(): void
    {
        $this->assertSame('Up', HeroSparklineBuilder::trendWordFromLabel('bullish'));
        $this->assertSame('Down', HeroSparklineBuilder::trendWordFromLabel('bearish'));
        $this->assertSame('Sideways', HeroSparklineBuilder::trendWordFromLabel('sideways'));
        $this->assertNull(HeroSparklineBuilder::trendWordFromLabel('not_enough_data'));

        $rising = array_merge(array_fill(0, 15, 200.0), [205.0, 210.0, 215.0, 220.0, 230.0]);
        $this->assertSame('Up', HeroSparklineBuilder::trendWordFromSeries($rising));

        $falling = array_merge(array_fill(0, 15, 250.0), [240.0, 230.0, 220.0, 210.0, 200.0]);
        $this->assertSame('Down', HeroSparklineBuilder::trendWordFromSeries($falling));

        $flat = array_fill(0, 20, 240.0);
        $this->assertSame('Sideways', HeroSparklineBuilder::trendWordFromSeries($flat));

        $short = [200.0, 210.0, 220.0];
        $this->assertNull(HeroSparklineBuilder::trendWordFromSeries($short));
    }
}

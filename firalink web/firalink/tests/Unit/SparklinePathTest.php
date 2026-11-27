<?php

namespace Tests\Unit;

use App\Support\Market\SparklinePath;
use PHPUnit\Framework\TestCase;

class SparklinePathTest extends TestCase
{
    public function test_fewer_than_two_values_draw_nothing(): void
    {
        $this->assertNull(SparklinePath::build([]));
        $this->assertNull(SparklinePath::build([249.09]));
        $this->assertNull(SparklinePath::build(['x', null, 249.09]));
    }

    public function test_line_spans_the_box_with_highest_value_at_the_top(): void
    {
        $path = SparklinePath::build([10, 20, 30], null, 96, 34, 3);

        $this->assertSame('M3,31 L48,17 L93,3', $path['line']);
        $this->assertSame('M3,31 L48,17 L93,3 L93,31 L3,31 Z', $path['area']);
        $this->assertNull($path['ref_y']);
        $this->assertSame([93.0, 3.0], [$path['last_x'], $path['last_y']]);
    }

    public function test_previous_close_outside_the_range_widens_the_scale(): void
    {
        $path = SparklinePath::build([20, 30], 10, 96, 34, 3);

        $this->assertSame(31.0, $path['ref_y'], 'The reference is the lowest value, so it sits on the bottom edge.');
        $this->assertSame('M3,17 L93,3', $path['line']);
    }

    public function test_a_flat_series_is_drawn_in_the_middle(): void
    {
        $path = SparklinePath::build([5, 5, 5], 5, 96, 34, 3);

        $this->assertSame('M3,17 L48,17 L93,17', $path['line']);
        $this->assertSame(17.0, $path['ref_y']);
    }
}

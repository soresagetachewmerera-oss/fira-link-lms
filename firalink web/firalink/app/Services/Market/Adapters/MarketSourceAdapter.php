<?php

namespace App\Services\Market\Adapters;

use App\Models\MarketSource;

interface MarketSourceAdapter
{
    /** @return array<int, array{symbol:string,value:string|int|float,currency:string,unit:string,source_time:string,raw_ref:array}> */
    public function fetch(MarketSource $source): array;
}

<?php

namespace App\Http\Controllers\Api\Concerns;

use Illuminate\Http\Request;

trait PaginatesApiRequests
{
    protected function perPage(Request $request): int
    {
        $default = (int) config('api.pagination.per_page', 15);
        $max = (int) config('api.pagination.max_per_page', 100);
        $requested = (int) $request->integer('per_page', $default);

        return min(max($requested, 1), $max);
    }
}

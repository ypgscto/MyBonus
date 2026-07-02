<?php

namespace App\Support;

use App\Models\PresenterRequest;
use Illuminate\Support\Facades\DB;

class RequestCodeGenerator
{
    /**
     * Generate request code with format PR-YYYYMM-0001.
     * Sequence resets every month.
     */
    public function generate(?\DateTimeInterface $date = null): string
    {
        $date = $date ?? now();
        $prefix = 'PR-'.$date->format('Ym').'-';

        return DB::transaction(function () use ($prefix) {
            $lastCode = PresenterRequest::query()
                ->where('request_code', 'like', $prefix.'%')
                ->lockForUpdate()
                ->orderByDesc('request_code')
                ->value('request_code');

            $sequence = 1;

            if ($lastCode) {
                $sequence = (int) substr($lastCode, -4) + 1;
            }

            return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
        });
    }
}

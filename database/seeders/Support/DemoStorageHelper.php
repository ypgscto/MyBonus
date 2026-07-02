<?php

namespace Database\Seeders\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoStorageHelper
{
    public static function ensureUploadDirs(): void
    {
        foreach (['payment_proofs', 'verifikator_transfers', 'presenter_transfers'] as $folder) {
            $path = storage_path('uploads/'.$folder);
            if (! is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }
    }

    public static function putPdf(string $disk, ?string $filename = null): string
    {
        $filename = $filename ?? Str::uuid()->toString().'.pdf';
        Storage::disk($disk)->put($filename, '%PDF-1.4 BONUSKU demo placeholder');

        return $filename;
    }
}

<?php

namespace App\Services;

use App\Support\SecureFileUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PaymentProofService
{
    private const DISK = 'payment_proofs';

    /**
     * @return array{0: string, 1: array<string, mixed>}
     */
    public function store(UploadedFile $file): array
    {
        $extension = SecureFileUpload::validate($file, 'payment_proof');
        $filename = Str::uuid()->toString().'.'.$extension;

        $path = $file->storeAs('', $filename, self::DISK);

        return [$path, [
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]];
    }

    public function delete(?string $path): void
    {
        if ($path && Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    public function path(?string $filename): ?string
    {
        if (! $filename) {
            return null;
        }

        return Storage::disk(self::DISK)->path($filename);
    }

    public function exists(?string $filename): bool
    {
        return $filename && Storage::disk(self::DISK)->exists($filename);
    }

    public static function maxSizeKb(): int
    {
        return SecureFileUpload::maxSizeKb();
    }
}

<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class SecureFileUpload
{
    public const MAX_BYTES = 5 * 1024 * 1024;

    /**
     * @var list<string>
     */
    public const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'pdf'];

    /**
     * @var list<string>
     */
    private const BLOCKED_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar',
        'exe', 'bat', 'cmd', 'com', 'msi', 'dll', 'scr',
        'sh', 'bash', 'zsh', 'js', 'jar', 'svg', 'html', 'htm', 'asp', 'aspx',
    ];

    /**
     * @var list<string>
     */
    private const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'application/pdf',
    ];

    public static function maxSizeKb(): int
    {
        return (int) (self::MAX_BYTES / 1024);
    }

    public static function validate(UploadedFile $file, string $field = 'file'): string
    {
        if (! $file->isValid()) {
            throw ValidationException::withMessages([
                $field => 'File upload tidak valid.',
            ]);
        }

        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === '' || in_array($extension, self::BLOCKED_EXTENSIONS, true)) {
            throw ValidationException::withMessages([
                $field => 'Jenis file tidak diizinkan.',
            ]);
        }

        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw ValidationException::withMessages([
                $field => 'File harus berformat JPG, PNG, atau PDF.',
            ]);
        }

        if ($file->getSize() > self::MAX_BYTES) {
            throw ValidationException::withMessages([
                $field => 'Ukuran file maksimal 5 MB.',
            ]);
        }

        $mime = $file->getMimeType();
        if ($mime && ! in_array($mime, self::ALLOWED_MIMES, true)) {
            throw ValidationException::withMessages([
                $field => 'Tipe file tidak valid.',
            ]);
        }

        return $extension;
    }
}

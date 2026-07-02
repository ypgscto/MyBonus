<?php

namespace Tests\Unit;

use App\Support\SecureFileUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SecureFileUploadTest extends TestCase
{
    public function test_rejects_php_extension(): void
    {
        $file = UploadedFile::fake()->create('malicious.php', 100, 'application/x-php');

        $this->expectException(ValidationException::class);
        SecureFileUpload::validate($file);
    }

    public function test_rejects_executable_extension(): void
    {
        $file = UploadedFile::fake()->create('virus.exe', 100, 'application/octet-stream');

        $this->expectException(ValidationException::class);
        SecureFileUpload::validate($file);
    }

    public function test_rejects_oversized_file(): void
    {
        $file = UploadedFile::fake()->create('bukti.pdf', SecureFileUpload::maxSizeKb() + 1, 'application/pdf');

        $this->expectException(ValidationException::class);
        SecureFileUpload::validate($file);
    }

    public function test_accepts_valid_pdf(): void
    {
        $file = UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf');

        $this->assertSame('pdf', SecureFileUpload::validate($file));
    }
}

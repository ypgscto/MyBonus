<?php

namespace Tests\Unit;

use App\Support\WhatsappNumberHelper;
use Tests\TestCase;

class WhatsappNumberHelperTest extends TestCase
{
    public function test_normalize_converts_local_format_to_62(): void
    {
        $this->assertSame('628123456789', WhatsappNumberHelper::normalize('08123456789'));
    }

    public function test_normalize_converts_plus_62_format(): void
    {
        $this->assertSame('628123456789', WhatsappNumberHelper::normalize('+628123456789'));
    }

    public function test_normalize_keeps_62_format(): void
    {
        $this->assertSame('628123456789', WhatsappNumberHelper::normalize('628123456789'));
    }

    public function test_is_valid_indonesian_number(): void
    {
        $this->assertTrue(WhatsappNumberHelper::isValidIndonesianNumber('08123456789'));
        $this->assertTrue(WhatsappNumberHelper::isValidIndonesianNumber('+628123456789'));
        $this->assertTrue(WhatsappNumberHelper::isValidIndonesianNumber('628123456789'));
        $this->assertFalse(WhatsappNumberHelper::isValidIndonesianNumber('12345'));
        $this->assertFalse(WhatsappNumberHelper::isValidIndonesianNumber(''));
    }
}

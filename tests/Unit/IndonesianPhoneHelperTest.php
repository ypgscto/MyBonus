<?php

namespace Tests\Unit;

use App\Support\IndonesianPhoneHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class IndonesianPhoneHelperTest extends TestCase
{
    #[DataProvider('phoneProvider')]
    public function test_normalizes_indonesian_phone_numbers(string $input, string $expected): void
    {
        $this->assertSame($expected, IndonesianPhoneHelper::normalize($input));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function phoneProvider(): array
    {
        return [
            'leading zero' => ['081234567890', '6281234567890'],
            'with plus sixty two' => ['+6281234567890', '6281234567890'],
            'already normalized' => ['6281234567890', '6281234567890'],
            'with spaces and dashes' => ['0812-3456 7890', '6281234567890'],
        ];
    }
}

<?php

namespace App\Rules;

use App\Support\WhatsappNumberHelper;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IndonesianPhoneNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! WhatsappNumberHelper::isValidIndonesianNumber((string) $value)) {
            $fail('Nomor WhatsApp harus menggunakan format nomor Indonesia yang valid.');
        }
    }
}

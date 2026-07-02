<?php

namespace App\Exceptions;

use Exception;

class DuplicateNimValidationException extends Exception
{
    /**
     * @param  array<int, array<string, mixed>>  $report
     */
    public function __construct(
        public readonly array $report,
        string $message = 'Permintaan tidak dapat dikirim karena terdapat NIM/Nomor Pendaftaran yang sudah pernah digunakan pada permintaan presenter lain.',
    ) {
        parent::__construct($message);
    }
}

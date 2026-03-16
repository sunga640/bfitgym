<?php

namespace App\Integrations\Zkteco\DTO;

class ConnectionTestResult
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        public readonly bool $ok,
        public readonly string $status,
        public readonly string $message,
        public readonly array $details = [],
    ) {
    }
}


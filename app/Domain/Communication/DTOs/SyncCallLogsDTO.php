<?php

namespace App\Domain\Communication\DTOs;

final readonly class SyncCallLogsDTO
{
    public function __construct(
        public int   $deviceId,
        public array $calls,
    ) {}

    public static function fromArray(array $data, int $deviceId): self
    {
        return new self(
            deviceId: $deviceId,
            calls:    $data['calls'],
        );
    }
}

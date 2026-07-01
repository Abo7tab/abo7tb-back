<?php

namespace App\Domain\Communication\DTOs;

final readonly class SyncSmsLogsDTO
{
    public function __construct(
        public int   $deviceId,
        public array $messages,
    ) {}

    public static function fromArray(array $data, int $deviceId): self
    {
        return new self(
            deviceId: $deviceId,
            messages: $data['messages'],
        );
    }
}

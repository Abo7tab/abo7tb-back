<?php

namespace App\Domain\Communication\DTOs;

final readonly class SyncContactsDTO
{
    public function __construct(
        public int   $deviceId,
        public array $contacts,
    ) {}

    public static function fromArray(array $data, int $deviceId): self
    {
        return new self(
            deviceId: $deviceId,
            contacts: $data['contacts'],
        );
    }
}

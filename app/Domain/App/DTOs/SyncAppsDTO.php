<?php

namespace App\Domain\App\DTOs;

/**
 * DTO for syncing the list of installed apps from the child's device.
 */
final readonly class SyncAppsDTO
{
    public function __construct(
        public int   $deviceId,
        public array $appsList,
    ) {}

    public static function fromArray(array $data, int $deviceId): self
    {
        return new self(
            deviceId: $deviceId,
            appsList: $data['apps'],
        );
    }
}

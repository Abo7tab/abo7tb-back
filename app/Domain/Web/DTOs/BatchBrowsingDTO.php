<?php

namespace App\Domain\Web\DTOs;

final readonly class BatchBrowsingDTO
{
    public function __construct(
        public int   $deviceId,
        public array $history,
    ) {}

    public static function fromArray(array $data, int $deviceId): self
    {
        return new self(
            deviceId: $deviceId,
            history:  $data['history'],
        );
    }
}

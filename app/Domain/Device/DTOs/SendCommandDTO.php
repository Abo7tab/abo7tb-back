<?php

namespace App\Domain\Device\DTOs;

final readonly class SendCommandDTO
{
    public function __construct(
        public int    $deviceId,
        public int    $createdBy,
        public string $commandCategory,
        public string $commandType,
        public array  $commandData = [],
        public string $priority    = 'normal',
    ) {}

    public static function fromArray(array $data, int $deviceId, int $userId): self
    {
        return new self(
            deviceId:        $deviceId,
            createdBy:       $userId,
            commandCategory: $data['command_category'],
            commandType:     $data['command_type'],
            commandData:     $data['command_data'] ?? [],
            priority:        $data['priority']     ?? 'normal',
        );
    }
}

<?php

namespace App\Domain\App\DTOs;

final readonly class BlockAppDTO
{
    public function __construct(
        public int     $deviceId,
        public string  $packageName,
        public string  $appName,
        public string  $blockType    = 'permanent',
        public ?string $reason       = null,
        public ?string $blockedUntil = null,
    ) {}

    public static function fromArray(array $data, int $deviceId): self
    {
        return new self(
            deviceId:     $deviceId,
            packageName:  $data['package_name'],
            appName:      $data['app_name']      ?? '',
            blockType:    $data['block_type']    ?? 'permanent',
            reason:       $data['reason']        ?? null,
            blockedUntil: $data['blocked_until'] ?? null,
        );
    }
}

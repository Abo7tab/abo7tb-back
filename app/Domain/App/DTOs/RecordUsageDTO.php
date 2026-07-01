<?php

namespace App\Domain\App\DTOs;

final readonly class RecordUsageDTO
{
    public function __construct(
        public int    $deviceId,
        public string $date,
        public array  $usageData,
        public int    $totalScreenSec,
        public int    $screenOnSec,
        public int    $interactiveSec,
        public int    $unlockCount,
    ) {}

    public static function fromArray(array $data, int $deviceId): self
    {
        return new self(
            deviceId:       $deviceId,
            date:           $data['date'],
            usageData:      $data['usage'],
            totalScreenSec: (int) ($data['total_screen_sec'] ?? 0),
            screenOnSec:    (int) ($data['screen_on_sec']    ?? 0),
            interactiveSec: (int) ($data['interactive_sec']  ?? 0),
            unlockCount:    (int) ($data['unlock_count']     ?? 0),
        );
    }
}

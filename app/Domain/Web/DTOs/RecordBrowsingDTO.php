<?php

namespace App\Domain\Web\DTOs;

final readonly class RecordBrowsingDTO
{
    public function __construct(
        public int     $deviceId,
        public string  $url,
        public ?string $title       = null,
        public ?string $browserName = null,
    ) {}

    public static function fromArray(array $data, int $deviceId): self
    {
        return new self(
            deviceId:    $deviceId,
            url:         $data['url'],
            title:       $data['title']        ?? null,
            browserName: $data['browser_name'] ?? null,
        );
    }
}

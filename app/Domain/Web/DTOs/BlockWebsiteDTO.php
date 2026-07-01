<?php

namespace App\Domain\Web\DTOs;

final readonly class BlockWebsiteDTO
{
    public function __construct(
        public int     $deviceId,
        public string  $domain,
        public string  $blockType = 'domain',
        public ?string $category  = null,
    ) {}

    public static function fromArray(array $data, int $deviceId): self
    {
        $raw = $data['domain'] ?? $data['url'] ?? '';

        return new self(
            deviceId:  $deviceId,
            domain:    self::extractDomain($raw),
            blockType: $data['block_type'] ?? 'domain',
            category:  $data['category']   ?? null,
        );
    }

    private static function extractDomain(string $input): string
    {
        if (str_contains($input, '://')) {
            $parsed = parse_url($input);
            $host   = $parsed['host'] ?? $input;
        } else {
            $host = $input;
        }

        return str_starts_with($host, 'www.')
            ? substr($host, 4)
            : $host;
    }
}

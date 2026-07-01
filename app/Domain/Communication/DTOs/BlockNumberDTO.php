<?php

namespace App\Domain\Communication\DTOs;

final readonly class BlockNumberDTO
{
    public function __construct(
        public int     $deviceId,
        public string  $phoneNumber,
        public ?string $contactName = null,
        public bool    $blockCalls  = true,
        public bool    $blockSms    = true,
        public ?string $reason      = null,
    ) {}

    public static function fromArray(array $data, int $deviceId): self
    {
        return new self(
            deviceId:     $deviceId,
            phoneNumber:  $data['phone_number'],
            contactName:  $data['contact_name'] ?? null,
            blockCalls:   (bool) ($data['block_calls']  ?? true),
            blockSms:     (bool) ($data['block_sms']    ?? true),
            reason:       $data['reason']        ?? null,
        );
    }
}

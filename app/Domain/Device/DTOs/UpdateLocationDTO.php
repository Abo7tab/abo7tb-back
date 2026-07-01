<?php

namespace App\Domain\Device\DTOs;

final readonly class UpdateLocationDTO
{
    public function __construct(
        public float   $latitude,
        public float   $longitude,
        public ?float  $altitude = null,
        public ?float  $accuracy = null,
        public ?float  $speed    = null,
        public ?float  $bearing  = null,
        public ?string $address  = null,
        public ?string $city     = null,
        public ?string $country  = null,
        public ?string $provider = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            latitude:  (float) $data['latitude'],
            longitude: (float) $data['longitude'],
            altitude:  isset($data['altitude'])  ? (float) $data['altitude']  : null,
            accuracy:  isset($data['accuracy'])  ? (float) $data['accuracy']  : null,
            speed:     isset($data['speed'])     ? (float) $data['speed']     : null,
            bearing:   isset($data['bearing'])   ? (float) $data['bearing']   : null,
            address:   $data['address']          ?? null,
            city:      $data['city']             ?? null,
            country:   $data['country']          ?? null,
            provider:  $data['provider']         ?? null,
        );
    }
}

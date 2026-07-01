<?php

namespace App\Domain\Shared\ValueObjects;

use InvalidArgumentException;
use JsonSerializable;

final readonly class Coordinates implements JsonSerializable
{
    private float $latitude;
    private float $longitude;

    public function __construct(float $latitude, float $longitude)
    {
        if ($latitude < -90 || $latitude > 90) {
            throw new InvalidArgumentException("Invalid latitude: {$latitude}. Must be between -90 and 90");
        }

        if ($longitude < -180 || $longitude > 180) {
            throw new InvalidArgumentException("Invalid longitude: {$longitude}. Must be between -180 and 180");
        }

        $this->latitude = round($latitude, 8);
        $this->longitude = round($longitude, 8);
    }

    public function getLatitude(): float
    {
        return $this->latitude;
    }

    public function getLongitude(): float
    {
        return $this->longitude;
    }

    public function toArray(): array
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }

    public function __toString(): string
    {
        return "{$this->latitude},{$this->longitude}";
    }

    /**
     * Calculate distance to another coordinate in kilometers using Haversine formula
     */
    public function distanceTo(Coordinates $other): float
    {
        $earthRadius = 6371; // km

        $latFrom = deg2rad($this->latitude);
        $lonFrom = deg2rad($this->longitude);
        $latTo   = deg2rad($other->latitude);
        $lonTo   = deg2rad($other->longitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(
            pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)
        ));

        return round($angle * $earthRadius, 2);
    }

    /**
     * Calculate distance in meters
     */
    public function distanceToInMeters(Coordinates $other): float
    {
        return $this->distanceTo($other) * 1000;
    }

    /**
     * Check if coordinates are within radius (in meters)
     */
    public function isWithinRadius(Coordinates $center, float $radiusInMeters): bool
    {
        return $this->distanceToInMeters($center) <= $radiusInMeters;
    }

    public function equals(Coordinates $other): bool
    {
        return $this->latitude === $other->latitude && $this->longitude === $other->longitude;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['latitude'] ?? $data['lat'],
            $data['longitude'] ?? $data['lng'] ?? $data['lon']
        );
    }
}

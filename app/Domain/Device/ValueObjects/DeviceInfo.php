<?php

namespace App\Domain\Device\ValueObjects;

use JsonSerializable;

final readonly class DeviceInfo implements JsonSerializable
{
    public function __construct(
        private string $model,
        private string $brand,
        private string $osVersion,
        private string $appVersion,
        private ?string $serialNumber = null,
    ) {}

    public function getModel(): string
    {
        return $this->model;
    }

    public function getBrand(): string
    {
        return $this->brand;
    }

    public function getOsVersion(): string
    {
        return $this->osVersion;
    }

    public function getAppVersion(): string
    {
        return $this->appVersion;
    }

    public function getSerialNumber(): ?string
    {
        return $this->serialNumber;
    }

    public function getDisplayName(): string
    {
        return "{$this->brand} {$this->model}";
    }

    public function toArray(): array
    {
        return [
            'model'         => $this->model,
            'brand'         => $this->brand,
            'os_version'    => $this->osVersion,
            'app_version'   => $this->appVersion,
            'serial_number' => $this->serialNumber,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public static function fromArray(array $data): self
    {
        return new self(
            model:        $data['model'],
            brand:        $data['brand'],
            osVersion:    $data['os_version'],
            appVersion:   $data['app_version'],
            serialNumber: $data['serial_number'] ?? null,
        );
    }
}

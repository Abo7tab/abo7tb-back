<?php
namespace App\Domain\Device\Exceptions;
use RuntimeException;
class DeviceNotFoundException extends RuntimeException
{
    public static function withUuid(string $uuid): self
    {
        return new self("Device with UUID [{$uuid}] not found.");
    }
}

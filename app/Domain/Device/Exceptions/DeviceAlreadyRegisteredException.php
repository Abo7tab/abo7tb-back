<?php
namespace App\Domain\Device\Exceptions;
use RuntimeException;
class DeviceAlreadyRegisteredException extends RuntimeException
{
    public static function withToken(string $token): self
    {
        return new self("Device with token [{$token}] is already registered.");
    }
}

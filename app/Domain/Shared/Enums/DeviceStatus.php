<?php

namespace App\Domain\Shared\Enums;

enum DeviceStatus: string
{
    case ONLINE    = 'online';
    case OFFLINE   = 'offline';
    case LOCKED    = 'locked';
    case INACTIVE  = 'inactive';
    case SUSPENDED = 'suspended';

    public function label(): string
    {
        return match($this) {
            self::ONLINE    => 'متصل',
            self::OFFLINE   => 'غير متصل',
            self::LOCKED    => 'مقفل',
            self::INACTIVE  => 'غير نشط',
            self::SUSPENDED => 'معلق',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::ONLINE    => 'success',
            self::OFFLINE   => 'danger',
            self::LOCKED    => 'warning',
            self::INACTIVE  => 'secondary',
            self::SUSPENDED => 'dark',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::ONLINE    => 'check-circle',
            self::OFFLINE   => 'x-circle',
            self::LOCKED    => 'lock',
            self::INACTIVE  => 'moon',
            self::SUSPENDED => 'pause-circle',
        };
    }

    public function isActive(): bool
    {
        return $this === self::ONLINE;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

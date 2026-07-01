<?php

namespace App\Domain\Shared\Enums;

enum CommandType: string
{
    case LOCK        = 'lock';
    case UNLOCK      = 'unlock';
    case LOCATE      = 'locate';
    case SCREENSHOT  = 'screenshot';
    case RING        = 'ring';
    case RESTART     = 'restart';
    case WIPE        = 'wipe';
    case DISABLE_APP = 'disable_app';
    case ENABLE_APP  = 'enable_app';

    public function label(): string
    {
        return match($this) {
            self::LOCK        => 'قفل الجهاز',
            self::UNLOCK      => 'فتح القفل',
            self::LOCATE      => 'تحديد الموقع',
            self::SCREENSHOT  => 'لقطة شاشة',
            self::RING        => 'تشغيل الرنين',
            self::RESTART     => 'إعادة تشغيل',
            self::WIPE        => 'مسح البيانات',
            self::DISABLE_APP => 'تعطيل تطبيق',
            self::ENABLE_APP  => 'تفعيل تطبيق',
        };
    }

    public function requiresConfirmation(): bool
    {
        return match($this) {
            self::WIPE, self::RESTART => true,
            default => false,
        };
    }

    public function isDestructive(): bool
    {
        return match($this) {
            self::WIPE => true,
            default    => false,
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::LOCK        => 'lock',
            self::UNLOCK      => 'unlock',
            self::LOCATE      => 'map-pin',
            self::SCREENSHOT  => 'camera',
            self::RING        => 'volume-2',
            self::RESTART     => 'rotate-cw',
            self::WIPE        => 'trash-2',
            self::DISABLE_APP => 'x-square',
            self::ENABLE_APP  => 'check-square',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

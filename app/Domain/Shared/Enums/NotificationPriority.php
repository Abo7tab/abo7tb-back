<?php

namespace App\Domain\Shared\Enums;

enum NotificationPriority: string
{
    case LOW    = 'low';
    case MEDIUM = 'medium';
    case HIGH   = 'high';
    case URGENT = 'urgent';

    public function label(): string
    {
        return match($this) {
            self::LOW    => 'منخفض',
            self::MEDIUM => 'متوسط',
            self::HIGH   => 'مرتفع',
            self::URGENT => 'عاجل',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::LOW    => 'info',
            self::MEDIUM => 'primary',
            self::HIGH   => 'warning',
            self::URGENT => 'danger',
        };
    }

    public function level(): int
    {
        return match($this) {
            self::LOW    => 1,
            self::MEDIUM => 2,
            self::HIGH   => 3,
            self::URGENT => 4,
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

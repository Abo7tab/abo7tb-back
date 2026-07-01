<?php

namespace App\Domain\Shared\ValueObjects;

use InvalidArgumentException;
use JsonSerializable;

final readonly class PhoneNumber implements JsonSerializable
{
    private string $value;
    private string $countryCode;

    public function __construct(string $phoneNumber, string $countryCode = '+966')
    {
        $cleaned = preg_replace('/[^0-9+]/', '', $phoneNumber);

        // إذا لم يبدأ بـ +، أضف كود الدولة
        if (!str_starts_with($cleaned, '+')) {
            // إزالة الصفر الأول إذا وجد
            $cleaned = ltrim($cleaned, '0');
            $cleaned = $countryCode . $cleaned;
        }

        if (strlen($cleaned) < 10) {
            throw new InvalidArgumentException("Invalid phone number: {$phoneNumber}");
        }

        $this->value = $cleaned;
        $this->countryCode = $countryCode;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function getLocalNumber(): string
    {
        return str_replace($this->countryCode, '', $this->value);
    }

    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Format: +966 50 123 4567
     */
    public function getFormatted(): string
    {
        if (str_starts_with($this->value, '+966')) {
            return preg_replace('/(\+966)(\d{2})(\d{3})(\d{4})/', '$1 $2 $3 $4', $this->value);
        }

        return $this->value;
    }

    public function equals(PhoneNumber $other): bool
    {
        return $this->value === $other->value;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }

    public static function fromString(string $phoneNumber, string $countryCode = '+966'): self
    {
        return new self($phoneNumber, $countryCode);
    }
}

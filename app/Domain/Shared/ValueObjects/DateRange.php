<?php

namespace App\Domain\Shared\ValueObjects;

use Carbon\Carbon;
use InvalidArgumentException;
use JsonSerializable;

final readonly class DateRange implements JsonSerializable
{
    private Carbon $startDate;
    private Carbon $endDate;

    public function __construct(Carbon|string $startDate, Carbon|string $endDate)
    {
        $this->startDate = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $this->endDate   = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);

        if ($this->startDate->isAfter($this->endDate)) {
            throw new InvalidArgumentException("Start date must be before or equal to end date");
        }
    }

    public function getStartDate(): Carbon
    {
        return $this->startDate;
    }

    public function getEndDate(): Carbon
    {
        return $this->endDate;
    }

    public function getDays(): int
    {
        return $this->startDate->diffInDays($this->endDate) + 1;
    }

    public function getHours(): int
    {
        return $this->startDate->diffInHours($this->endDate);
    }

    public function contains(Carbon $date): bool
    {
        return $date->between($this->startDate, $this->endDate);
    }

    public function overlaps(DateRange $other): bool
    {
        return $this->startDate->lte($other->endDate) && $this->endDate->gte($other->startDate);
    }

    public function toArray(): array
    {
        return [
            'start_date' => $this->startDate->toDateString(),
            'end_date'   => $this->endDate->toDateString(),
            'days'       => $this->getDays(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public static function today(): self
    {
        $today = Carbon::today();
        return new self($today, $today);
    }

    public static function thisWeek(): self
    {
        return new self(Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek());
    }

    public static function thisMonth(): self
    {
        return new self(Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth());
    }

    public static function last7Days(): self
    {
        return new self(Carbon::now()->subDays(6), Carbon::now());
    }

    public static function last30Days(): self
    {
        return new self(Carbon::now()->subDays(29), Carbon::now());
    }
}

<?php

namespace App\Services\Cleaning;

use InvalidArgumentException;

class DayMask
{
    public const SUNDAY = 0;
    public const MONDAY = 1;
    public const TUESDAY = 2;
    public const WEDNESDAY = 3;
    public const THURSDAY = 4;
    public const FRIDAY = 5;
    public const SATURDAY = 6;

    private int $mask;

    public function __construct(int $mask)
    {
        $this->mask = $mask;
    }

    public static function fromNames(array $names): self
    {
        $dayMap = [
            'sunday' => self::SUNDAY,
            'monday' => self::MONDAY,
            'tuesday' => self::TUESDAY,
            'wednesday' => self::WEDNESDAY,
            'thursday' => self::THURSDAY,
            'friday' => self::FRIDAY,
            'saturday' => self::SATURDAY,
        ];

        $mask = 0;
        foreach ($names as $name) {
            $key = strtolower(trim($name));
            if (!isset($dayMap[$key])) {
                throw new InvalidArgumentException("Invalid day name: {$name}");
            }
            $mask |= (1 << $dayMap[$key]);
        }

        return new self($mask);
    }

    public static function fromIndex(int $index): self
    {
        if ($index < 0 || $index > 6) {
            throw new InvalidArgumentException("Day index must be 0-6, got {$index}");
        }

        return new self(1 << $index);
    }

    public static function none(): self
    {
        return new self(0);
    }

    public function setMaskFromNames(array $names): int
    {
        $dayMap = [
            'sunday' => self::SUNDAY,
            'monday' => self::MONDAY,
            'tuesday' => self::TUESDAY,
            'wednesday' => self::WEDNESDAY,
            'thursday' => self::THURSDAY,
            'friday' => self::FRIDAY,
            'saturday' => self::SATURDAY,
        ];

        $mask = 0;
        foreach ($names as $name) {
            $key = strtolower(trim($name));
            if (isset($dayMap[$key])) {
                $mask |= (1 << $dayMap[$key]);
            }
        }
        return $mask;
    }

    public function toIndex(): int
    {
        return $this->mask;
    }

    public function toNames(): array
    {
        $names = [];
        for ($i = 0; $i < 7; $i++) {
            if ($this->mask & (1 << $i)) {
                $names[] = self::INDEX_TO_NAME[$i];
            }
        }
        return $names;
    }

    public function toIndices(): array
    {
        $indices = [];
        for ($i = 0; $i < 7; $i++) {
            if ($this->mask & (1 << $i)) {
                $indices[] = $i;
            }
        }
        return $indices;
    }

    public function has(int $index): bool
    {
        return (bool) ($this->mask & (1 << $index));
    }

    public function add(int $index): self
    {
        return new self($this->mask | (1 << $index));
    }

    public function remove(int $index): self
    {
        return new self($this->mask & ~(1 << $index));
    }

    public function isEmpty(): bool
    {
        return $this->mask === 0;
    }

    private const INDEX_TO_NAME = [
        self::SUNDAY => 'sunday',
        self::MONDAY => 'monday',
        self::TUESDAY => 'tuesday',
        self::WEDNESDAY => 'wednesday',
        self::THURSDAY => 'thursday',
        self::FRIDAY => 'friday',
        self::SATURDAY => 'saturday',
    ];
}
